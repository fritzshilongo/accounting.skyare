<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Database;
use App\Core\RequestContext;
use App\Core\View;
use App\Models\Company;

final class DashboardController
{
    public static function index(Database $db, RequestContext $context): void
    {
        $companyId = (int) ($context->company()['company_id'] ?? 0);
        $companyModel = new Company($db->pdo());
        $stats = [
            'sales' => 0.0,
            'expenses' => 0.0,
            'journal_entries' => 0,
            'customers' => 0,
            'invoices' => 0,
            'products' => 0,
            'estimates' => 0,
        ];

        if ($companyId > 0) {
            $queries = [
                'sales' => "SELECT COALESCE(SUM(amount),0) AS c FROM invoices WHERE company_id = :company_id AND status = 'paid'",
                'expenses' => 'SELECT COALESCE(SUM(amount),0) AS c FROM expenses WHERE company_id = :company_id',
                'journal_entries' => 'SELECT COUNT(*) AS c FROM journal_entries WHERE company_id = :company_id',
                'customers' => 'SELECT COUNT(*) AS c FROM customers WHERE company_id = :company_id',
                'invoices' => 'SELECT COUNT(*) AS c FROM invoices WHERE company_id = :company_id',
                'products' => 'SELECT COUNT(*) AS c FROM products WHERE company_id = :company_id',
                'estimates' => 'SELECT COUNT(*) AS c FROM estimates WHERE company_id = :company_id',
            ];

            foreach ($queries as $key => $sql) {
                $stmt = $db->pdo()->prepare($sql);
                $stmt->execute(['company_id' => $companyId]);
                $stats[$key] = (float) (($stmt->fetch()['c'] ?? 0));
            }
        }

        $companies = array_map(
            static fn (array $row): array => [
                'company_id' => (int) ($row['company_id'] ?? 0),
                'company_name' => (string) ($row['company_name'] ?? ''),
                'subdomain' => (string) ($row['subdomain'] ?? ''),
                'tenant_url' => self::buildCompanyUrl((string) ($row['subdomain'] ?? ''), $context),
            ],
            $companyModel->listActive()
        );
        $_SESSION['available_companies'] = $companies;

        View::render('dashboard/index', [
            'company' => $context->company(),
            'available_companies' => $companies,
            'user' => $_SESSION['user'] ?? null,
            'stats' => $stats,
        ]);
    }

    private static function buildCompanyUrl(string $subdomain, RequestContext $context): string
    {
        $subdomain = strtolower(trim($subdomain));
        if ($subdomain === '') {
            return '#';
        }

        $host = strtolower(trim($context->host()));
        $baseDomain = strtolower(trim((string) ($context->appConfig()['base_domain'] ?? '')));

        if ($host === 'localhost' || $host === '127.0.0.1') {
            $tenantHost = $subdomain . '.localhost';
        } elseif ($baseDomain !== '') {
            $tenantHost = $subdomain . '.' . $baseDomain;
        } elseif ($host !== '' && str_contains($host, '.')) {
            $parts = explode('.', $host);
            if (count($parts) > 2) {
                array_shift($parts);
            }
            $tenantHost = $subdomain . '.' . implode('.', $parts);
        } else {
            $tenantHost = $subdomain . '.skyare.space';
        }

        $https = strtolower((string) ($_SERVER['HTTPS'] ?? ''));
        $forwardedProto = strtolower((string) ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? ''));
        $scheme = ($https === 'on' || $https === '1' || $forwardedProto === 'https') ? 'https' : 'http';

        return $scheme . '://' . $tenantHost . '/dashboard';
    }
}
