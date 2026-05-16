<?php

namespace App\Http\Controllers;

use App\Core\Database;
use App\Core\RequestContext;
use App\Support\SchemaCompat;
use Illuminate\Http\Request;
use PDO;

class BackupController extends Controller
{
    private const BACKUP_DIR  = 'backups';
    private const MAX_BACKUPS = 20;
    private const FILENAME_RE = '/^(?:backup_\d{4}-\d{2}-\d{2}_\d{6}|tenant_\d+_backup_\d{4}-\d{2}-\d{2}_\d{6})(?:_uploaded)?\.sql$/';

    private function backupPath(string $file = ''): string
    {
        $dir = storage_path('app/' . self::BACKUP_DIR);
        return $file !== '' ? $dir . '/' . $file : $dir;
    }

    private function requireAdmin(): void
    {
        $role = strtolower((string) ($_SESSION['user']['role_key'] ?? ''));
        if (!in_array($role, ['admin', 'super_admin', 'superadmin'], true)) {
            abort(403, 'Admin access required.');
        }
    }

    public function index(RequestContext $context): \Illuminate\View\View
    {
        $this->requireAdmin();
        $backups = $this->listBackups();
        $company = $context->company();
        return view('settings.backups', compact('backups', 'company'));
    }

    public function store(Request $request, Database $db, RequestContext $context): \Illuminate\Http\RedirectResponse
    {
        $this->requireAdmin();

        $dir = $this->backupPath();
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $scope = (string) ($request->input('scope', 'global'));
        $companyId = (int) ($context->company()['company_id'] ?? 0);

        $filename = 'backup_' . date('Y-m-d_His') . '.sql';
        if ($scope === 'tenant') {
            if ($companyId <= 0) {
                return redirect('/settings/backups')
                    ->withErrors(['error' => 'Unable to determine tenant for tenant backup.']);
            }
            $filename = 'tenant_' . $companyId . '_backup_' . date('Y-m-d_His') . '.sql';
        }

        $filepath = $this->backupPath($filename);

        try {
            $sql = $scope === 'tenant'
                ? $this->generateTenantDump($db->pdo(), $companyId)
                : $this->generateDump($db->pdo());

            file_put_contents($filepath, $sql);
            $this->pruneOldBackups();

            $message = $scope === 'tenant'
                ? "Tenant backup created: {$filename} (" . $this->humanBytes(strlen($sql)) . ")"
                : "Backup created: {$filename} (" . $this->humanBytes(strlen($sql)) . ")";

            return redirect('/settings/backups')
                ->with('success', $message);
        } catch (\Throwable $e) {
            return redirect('/settings/backups')
                ->withErrors(['error' => 'Backup failed: ' . $e->getMessage()]);
        }
    }

    public function storeTenant(Request $request, Database $db, RequestContext $context): \Illuminate\Http\RedirectResponse
    {
        $request->merge(['scope' => 'tenant']);
        return $this->store($request, $db, $context);
    }

    public function restore(Request $request, Database $db, RequestContext $context): \Illuminate\Http\RedirectResponse
    {
        $this->requireAdmin();

        $filename = basename((string) $request->input('filename', ''));
        if ($filename === '' || !preg_match(self::FILENAME_RE, $filename)) {
            return redirect('/settings/backups')
                ->withErrors(['error' => 'Invalid backup file specified.']);
        }

        $filepath = $this->backupPath($filename);
        if (!file_exists($filepath)) {
            return redirect('/settings/backups')
                ->withErrors(['error' => 'Backup file not found.']);
        }

        $scope = (string) ($request->input('scope', 'global'));
        $companyId = (int) ($context->company()['company_id'] ?? 0);

        try {
            $pdo = $db->pdo();
            if ($scope === 'tenant' || $this->isTenantBackupFile($filename)) {
                $this->restoreTenantBackup($pdo, $filepath, $companyId, $filename);
                return redirect('/settings/backups')
                    ->with('success', "Tenant data successfully restored from: {$filename}");
            }

            $sql = file_get_contents($filepath);
            $pdo->exec('SET FOREIGN_KEY_CHECKS=0');
            foreach ($this->splitStatements($sql) as $stmt) {
                if (trim($stmt) !== '') {
                    $pdo->exec($stmt);
                }
            }
            $pdo->exec('SET FOREIGN_KEY_CHECKS=1');

            return redirect('/settings/backups')
                ->with('success', "Database successfully restored from: {$filename}");
        } catch (\Throwable $e) {
            return redirect('/settings/backups')
                ->withErrors(['error' => 'Restore failed: ' . $e->getMessage()]);
        }
    }

    public function restoreTenant(Request $request, Database $db, RequestContext $context): \Illuminate\Http\RedirectResponse
    {
        $request->merge(['scope' => 'tenant']);
        return $this->restore($request, $db, $context);
    }

    public function download(string $filename): \Symfony\Component\HttpFoundation\BinaryFileResponse
    {
        $this->requireAdmin();

        $filename = basename($filename);
        if (!preg_match(self::FILENAME_RE, $filename)) {
            abort(400, 'Invalid filename.');
        }

        $filepath = $this->backupPath($filename);
        if (!file_exists($filepath)) {
            abort(404, 'Backup not found.');
        }

        return response()->download($filepath);
    }

    public function destroy(Request $request): \Illuminate\Http\RedirectResponse
    {
        $this->requireAdmin();

        $filename = basename((string) $request->input('filename', ''));
        if ($filename === '' || !preg_match(self::FILENAME_RE, $filename)) {
            return redirect('/settings/backups')
                ->withErrors(['error' => 'Invalid file name.']);
        }

        $filepath = $this->backupPath($filename);
        if (file_exists($filepath)) {
            unlink($filepath);
        }

        return redirect('/settings/backups')
            ->with('success', "Backup deleted: {$filename}");
    }

    public function destroyAll(): \Illuminate\Http\RedirectResponse
    {
        $this->requireAdmin();

        $files = array_merge(
            glob($this->backupPath() . '/backup_*.sql') ?: [],
            glob($this->backupPath() . '/tenant_*_backup_*.sql') ?: []
        );

        foreach ($files as $file) {
            unlink($file);
        }

        return redirect('/settings/backups')
            ->with('success', 'All backups deleted.');
    }

    public function upload(Request $request, Database $db, RequestContext $context): \Illuminate\Http\RedirectResponse
    {
        $this->requireAdmin();

        if (!$request->hasFile('backup_file') || !$request->file('backup_file')->isValid()) {
            return redirect('/settings/backups')
                ->withErrors(['error' => 'No valid file was uploaded.']);
        }

        $file = $request->file('backup_file');

        // Validate extension and MIME type
        $ext = strtolower((string) $file->getClientOriginalExtension());
        if ($ext !== 'sql') {
            return redirect('/settings/backups')
                ->withErrors(['error' => 'Only .sql files are allowed.']);
        }

        // Max 100 MB
        if ($file->getSize() > 100 * 1024 * 1024) {
            return redirect('/settings/backups')
                ->withErrors(['error' => 'File exceeds the 100 MB upload limit.']);
        }

        $dir = $this->backupPath();
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        // Preserve valid backup naming when possible so tenant backup uploads are recognized.
        $originalName = basename((string) $file->getClientOriginalName());
        if ($this->isTenantBackupFile($originalName) || preg_match(self::FILENAME_RE, $originalName) === 1) {
            $filename = $originalName;
        } else {
            $filename = 'backup_' . date('Y-m-d_His') . '_uploaded.sql';
        }

        $filepath = $dir . '/' . $filename;

        // Move uploaded file into backup directory
        $file->move($dir, $filename);

        // Validate it looks like SQL (starts with -- or a SQL keyword)
        $handle = fopen($filepath, 'r');
        $preview = $handle ? fread($handle, 256) : '';
        if ($handle) {
            fclose($handle);
        }
        $preview = ltrim($preview);
        if ($preview !== '' && !preg_match('/^(--|SET|CREATE|INSERT|DROP|USE|BEGIN|LOCK|\x{FEFF})/u', $preview)) {
            unlink($filepath);
            return redirect('/settings/backups')
                ->withErrors(['error' => 'Uploaded file does not appear to be a valid SQL dump.']);
        }

        if (!$this->isValidBackupSql($filepath)) {
            unlink($filepath);
            return redirect('/settings/backups')
                ->withErrors(['error' => 'Uploaded file is not a valid Skyare backup file.']);
        }

        $this->pruneOldBackups();

        if ($request->boolean('restore_immediately')) {
            try {
                $companyId = (int) ($context->company()['company_id'] ?? 0);
                if ($this->isTenantBackupFile($filename)) {
                    $this->restoreTenantBackup($db->pdo(), $filepath, $companyId, $filename);
                } else {
                    $pdo = $db->pdo();
                    $sql = (string) file_get_contents($filepath);
                    $pdo->exec('SET FOREIGN_KEY_CHECKS=0');
                    foreach ($this->splitStatements($sql) as $stmt) {
                        if (trim($stmt) !== '') {
                            $pdo->exec($stmt);
                        }
                    }
                    $pdo->exec('SET FOREIGN_KEY_CHECKS=1');
                }

                return redirect('/settings/backups')
                    ->with('success', "File uploaded and database restored from: {$filename}");
            } catch (\Throwable $e) {
                return redirect('/settings/backups')
                    ->withErrors(['error' => 'File uploaded but restore failed: ' . $e->getMessage()]);
            }
        }

        $size = $this->humanBytes((int) filesize($filepath));
        return redirect('/settings/backups')
            ->with('success', "Backup uploaded successfully: {$filename} ({$size})");
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Helpers
    // ──────────────────────────────────────────────────────────────────────────

    public function listBackups(): array
    {
        $dir = $this->backupPath();
        if (!is_dir($dir)) {
            return [];
        }

        $backups = [];
        $files = array_merge(
            glob($dir . '/backup_*.sql') ?: [],
            glob($dir . '/tenant_*_backup_*.sql') ?: []
        );

        foreach ($files as $file) {
            $backups[] = [
                'filename'   => basename($file),
                'size'       => $this->humanBytes((int) filesize($file)),
                'size_bytes' => (int) filesize($file),
                'created_at' => date('Y-m-d H:i:s', (int) filemtime($file)),
            ];
        }

        usort($backups, fn ($a, $b) => strcmp($b['created_at'], $a['created_at']));
        return $backups;
    }

    public function generateDump(PDO $pdo): string
    {
        $lines = [];
        $lines[] = "-- Skyare Accounting Database Backup";
        $lines[] = "-- Generated: " . date('Y-m-d H:i:s');
        $lines[] = "-- -----------------------------------------------";
        $lines[] = "";
        $lines[] = "SET FOREIGN_KEY_CHECKS=0;";
        $lines[] = "SET SQL_MODE='NO_AUTO_VALUE_ON_ZERO';";
        $lines[] = "";

        $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
        if (!is_array($tables)) {
            $tables = [];
        }

        foreach ($tables as $table) {
            $table = (string) $table;

            // CREATE TABLE
            $row = $pdo->query("SHOW CREATE TABLE `{$table}`")->fetch(PDO::FETCH_ASSOC);
            $createSql = '';
            if (is_array($row)) {
                $createSql = (string) ($row['Create Table'] ?? end($row));
            }

            $lines[] = "-- Table: `{$table}`";
            $lines[] = "DROP TABLE IF EXISTS `{$table}`;";
            $lines[] = $createSql . ";";
            $lines[] = "";

            // Data
            $rows = $pdo->query("SELECT * FROM `{$table}`")->fetchAll(PDO::FETCH_ASSOC);
            if (!empty($rows)) {
                $cols = '`' . implode('`, `', array_keys($rows[0])) . '`';
                $chunks = array_chunk($rows, 200); // batch inserts
                foreach ($chunks as $chunk) {
                    $valueRows = [];
                    foreach ($chunk as $dataRow) {
                        $vals = array_map(static function ($val) use ($pdo) {
                            if ($val === null) {
                                return 'NULL';
                            }
                            return $pdo->quote((string) $val);
                        }, $dataRow);
                        $valueRows[] = '(' . implode(', ', $vals) . ')';
                    }
                    $lines[] = "INSERT INTO `{$table}` ({$cols}) VALUES";
                    $lines[] = implode(",\n", $valueRows) . ";";
                }
                $lines[] = "";
            }
        }

        $lines[] = "SET FOREIGN_KEY_CHECKS=1;";
        return implode("\n", $lines) . "\n";
    }

    private function isTenantBackupFile(string $filename): bool
    {
        return preg_match('/^tenant_\d+_backup_\d{4}-\d{2}-\d{2}_\d{6}(_uploaded)?\.sql$/', $filename) === 1;
    }

    private function extractTenantIdFromFilename(string $filename): ?int
    {
        if (preg_match('/^tenant_(\d+)_backup_/', $filename, $matches) === 1) {
            return (int) $matches[1];
        }

        return null;
    }

    private function restoreTenantBackup(PDO $pdo, string $filepath, int $companyId, string $filename): void
    {
        $backupTenantId = $this->extractTenantIdFromFilename($filename);
        if ($backupTenantId === null || $backupTenantId !== $companyId) {
            throw new \RuntimeException('Tenant backup file does not match current tenant.');
        }

        $sql = (string) file_get_contents($filepath);
        $pdo->exec('SET FOREIGN_KEY_CHECKS=0');
        $this->deleteTenantData($pdo, $companyId);
        foreach ($this->splitStatements($sql) as $stmt) {
            if (trim($stmt) !== '') {
                $pdo->exec($stmt);
            }
        }
        $pdo->exec('SET FOREIGN_KEY_CHECKS=1');
    }

    private function deleteTenantData(PDO $pdo, int $companyId): void
    {
        $tables = $pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
        if (!is_array($tables)) {
            return;
        }

        foreach ($tables as $table) {
            $table = (string) $table;
            if (!SchemaCompat::supportsCompany($table)) {
                continue;
            }

            $stmt = $pdo->prepare('DELETE FROM `' . $table . '` WHERE company_id = :cid');
            $stmt->execute(['cid' => $companyId]);
        }
    }

    private function generateTenantDump(PDO $pdo, int $companyId): string
    {
        $lines = [];
        $lines[] = "-- Skyare Accounting Tenant Backup";
        $lines[] = "-- Company ID: {$companyId}";
        $lines[] = "-- Generated: " . date('Y-m-d H:i:s');
        $lines[] = "-- -----------------------------------------------";
        $lines[] = "";
        $lines[] = "SET FOREIGN_KEY_CHECKS=0;";
        $lines[] = "";

        $tables = $pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
        if (!is_array($tables)) {
            $tables = [];
        }

        foreach ($tables as $table) {
            $table = (string) $table;
            if (!SchemaCompat::supportsCompany($table)) {
                continue;
            }

            $lines[] = "-- Tenant table: `{$table}`";
            $lines[] = "DELETE FROM `{$table}` WHERE company_id = {$companyId};";
            $lines[] = "";

            $stmt = $pdo->prepare('SELECT * FROM `' . $table . '` WHERE company_id = :cid');
            $stmt->execute(['cid' => $companyId]);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            if (!empty($rows)) {
                $cols = '`' . implode('`, `', array_keys($rows[0])) . '`';
                $chunks = array_chunk($rows, 200);
                foreach ($chunks as $chunk) {
                    $valueRows = [];
                    foreach ($chunk as $dataRow) {
                        $vals = array_map(static function ($val) use ($pdo) {
                            if ($val === null) {
                                return 'NULL';
                            }
                            return $pdo->quote((string) $val);
                        }, $dataRow);
                        $valueRows[] = '(' . implode(', ', $vals) . ')';
                    }
                    $lines[] = "INSERT INTO `{$table}` ({$cols}) VALUES";
                    $lines[] = implode(",\n", $valueRows) . ";";
                    $lines[] = "";
                }
            }
        }

        $lines[] = "SET FOREIGN_KEY_CHECKS=1;";
        return implode("\n", $lines) . "\n";
    }

    private function splitStatements(string $sql): array
    {
        $sql = $this->cleanupSql($sql);

        $statements = [];
        $buffer = '';
        $len = strlen($sql);
        $inSingleQuote = false;
        $inDoubleQuote = false;
        $inBacktick = false;
        $inLineComment = false;
        $inBlockComment = false;
        $escape = false;

        for ($i = 0; $i < $len; $i++) {
            $char = $sql[$i];
            $next = $i + 1 < $len ? $sql[$i + 1] : '';

            if ($inLineComment) {
                if ($char === "\n") {
                    $inLineComment = false;
                    $buffer .= $char;
                }
                continue;
            }

            if ($inBlockComment) {
                if ($char === '*' && $next === '/') {
                    $inBlockComment = false;
                    $i++;
                }
                continue;
            }

            if ($escape) {
                $buffer .= $char;
                $escape = false;
                continue;
            }

            if ($inSingleQuote) {
                if ($char === "\\") {
                    $escape = true;
                } elseif ($char === "'") {
                    $inSingleQuote = false;
                }
                $buffer .= $char;
                continue;
            }

            if ($inDoubleQuote) {
                if ($char === "\\") {
                    $escape = true;
                } elseif ($char === '"') {
                    $inDoubleQuote = false;
                }
                $buffer .= $char;
                continue;
            }

            if ($inBacktick) {
                if ($char === '`') {
                    $inBacktick = false;
                }
                $buffer .= $char;
                continue;
            }

            if ($char === '-' && $next === '-') {
                $inLineComment = true;
                $i++;
                continue;
            }

            if ($char === '#' && !$inSingleQuote && !$inDoubleQuote && !$inBacktick) {
                $inLineComment = true;
                continue;
            }

            if ($char === '/' && $next === '*') {
                $inBlockComment = true;
                $i++;
                continue;
            }

            if ($char === "'") {
                $inSingleQuote = true;
                $buffer .= $char;
                continue;
            }

            if ($char === '"') {
                $inDoubleQuote = true;
                $buffer .= $char;
                continue;
            }

            if ($char === '`') {
                $inBacktick = true;
                $buffer .= $char;
                continue;
            }

            if ($char === ';') {
                $statement = trim($buffer);
                if ($statement !== '') {
                    $statements[] = $statement;
                }
                $buffer = '';
                continue;
            }

            $buffer .= $char;
        }

        $last = trim($buffer);
        if ($last !== '') {
            $statements[] = $last;
        }

        return $statements;
    }

    private function cleanupSql(string $sql): string
    {
        $sql = str_replace(["\r\n", "\r"], "\n", $sql);
        $sql = ltrim($sql, "\xEF\xBB\xBF");

        $lines = explode("\n", $sql);
        $cleanLines = [];

        foreach ($lines as $line) {
            $trimmed = trim($line);
            if ($trimmed === '') {
                continue;
            }

            if (preg_match('/^(--|#)/', $trimmed)) {
                continue;
            }

            if (preg_match('/^[\-\s]+$/', $trimmed)) {
                continue;
            }

            if (preg_match('/^[-\s]{3,}[^\w]?/', $trimmed)) {
                continue;
            }

            $cleanLines[] = $line;
        }

        return implode("\n", $cleanLines);
    }

    private function pruneOldBackups(): void
    {
        $files = array_merge(
            glob($this->backupPath() . '/backup_*.sql') ?: [],
            glob($this->backupPath() . '/tenant_*_backup_*.sql') ?: []
        );
        usort($files, static fn ($a, $b) => filemtime((string) $b) - filemtime((string) $a));
        foreach (array_slice($files, self::MAX_BACKUPS) as $old) {
            @unlink((string) $old);
        }
    }

    private function isValidBackupSql(string $filepath): bool
    {
        $contents = (string) file_get_contents($filepath);
        $contents = ltrim($contents);

        return preg_match('/^--\s+Skyare Accounting/i', $contents) === 1
            || preg_match('/^--\s+Skyare Accounting Tenant Backup/i', $contents) === 1;
    }

    private function humanBytes(int $bytes): string
    {
        if ($bytes >= 1_048_576) {
            return round($bytes / 1_048_576, 2) . ' MB';
        }
        if ($bytes >= 1_024) {
            return round($bytes / 1_024, 1) . ' KB';
        }
        return $bytes . ' B';
    }
}
