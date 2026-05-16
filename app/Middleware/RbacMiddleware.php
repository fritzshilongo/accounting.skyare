<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\Database;
use App\Core\RequestContext;

final class RbacMiddleware
{
    public function handle(Database $db, RequestContext $context): bool
    {
        $roleKey = (string) ($_SESSION['user']['role_key'] ?? '');
        if ($roleKey === '') {
            http_response_code(403);
            echo 'Access denied.';
            return false;
        }

        if (in_array($roleKey, ['admin', 'primary_admin'], true)) {
            return true;
        }

        $moduleKey = trim((string) ($_GET['m'] ?? $_POST['m'] ?? ''));
        if ($moduleKey === '') {
            $moduleKey = $this->moduleFromPath((string) parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH));
        }
        if ($moduleKey === '') {
            return true;
        }

        $companyId = (int) ($_SESSION['user']['company_id'] ?? 0);
        if ($companyId > 0 && $this->isAllowedByDb($db, $companyId, $roleKey, $moduleKey)) {
            return true;
        }

        $roleModules = require dirname(__DIR__, 2) . '/config/role-modules.php';
        $allowed = $roleModules[$roleKey] ?? [];
        if (in_array('*', $allowed, true) || in_array($moduleKey, $allowed, true)) {
            return true;
        }

        http_response_code(403);
        echo 'Access denied for this module.';
        return false;

    }

    private function moduleFromPath(string $path): string
    {
        $map = [
            '/invoices' => 'invoices',
            '/customers' => 'customers',
            '/products' => 'products',
            '/inventory' => 'inventory',
            '/users' => 'users',
            '/audit-trail' => 'audit_trail',
            '/sales' => 'sales',
            '/estimates' => 'estimates',
            '/credit-management' => 'credit_management',
            '/reports' => 'reports',
            '/companies' => 'companies',
            '/company-details' => 'company_details',
            '/exchange-rates' => 'exchange_rates',
        ];

        foreach ($map as $prefix => $moduleKey) {
            if (str_starts_with($path, $prefix)) {
                return $moduleKey;
            }
        }

        return '';
    }

    private function isAllowedByDb(Database $db, int $companyId, string $roleKey, string $moduleKey): bool
    {
        $stmt = $db->pdo()->prepare(
            'SELECT can_view
             FROM role_permissions
             WHERE company_id = :company_id AND role_key = :role_key AND module_key = :module_key
             LIMIT 1'
        );
        $stmt->execute([
            'company_id' => $companyId,
            'role_key' => $roleKey,
            'module_key' => $moduleKey,
        ]);
        $row = $stmt->fetch();

        return $row !== false && (int) ($row['can_view'] ?? 0) === 1;
    }
}
