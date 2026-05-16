<?php

namespace App\Console\Commands;

use App\Core\Database;
use App\Http\Controllers\BackupController;
use Illuminate\Console\Command;

class DatabaseBackupCommand extends Command
{
    protected $signature   = 'db:backup {--prune : Only delete old backups, do not create a new one}';
    protected $description = 'Create a scheduled database backup and prune old ones';

    public function handle(Database $db): int
    {
        $dir = storage_path('app/backups');
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        if ($this->option('prune')) {
            $this->pruneOldBackups($dir);
            $this->info('Old backups pruned.');
            return self::SUCCESS;
        }

        $filename = 'backup_' . date('Y-m-d_His') . '.sql';
        $filepath = $dir . '/' . $filename;

        try {
            $controller = new BackupController();
            $sql = $controller->generateDump($db->pdo());
            file_put_contents($filepath, $sql);

            $kb = round(strlen($sql) / 1024, 1);
            $this->info("Backup created: {$filename} ({$kb} KB)");

            $this->pruneOldBackups($dir);
            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->error('Backup failed: ' . $e->getMessage());
            return self::FAILURE;
        }
    }

    private function pruneOldBackups(string $dir): void
    {
        $files = glob($dir . '/backup_*.sql') ?: [];
        usort($files, static fn ($a, $b) => filemtime((string) $b) - filemtime((string) $a));
        foreach (array_slice($files, 20) as $old) {
            @unlink((string) $old);
            $this->line('  Deleted old backup: ' . basename((string) $old));
        }
    }
}
