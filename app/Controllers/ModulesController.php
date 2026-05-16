<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Database;
use App\Core\RequestContext;
use App\Core\View;

final class ModulesController
{
    public static function index(Database $db, RequestContext $context): void
    {
        $roleModules = require dirname(__DIR__, 2) . '/config/role-modules.php';
        $roleKey = (string) ($_SESSION['user']['role_key'] ?? '');

        $nativeCards = [
            ['key' => 'invoices', 'name' => 'Invoices', 'path' => '/invoices', 'status' => 'live', 'description' => 'Invoice create, list, edit, and delete.'],
            ['key' => 'expenses', 'name' => 'Expenses', 'path' => '/expenses', 'status' => 'live', 'description' => 'Expense capture and listing for the tenant.'],
            ['key' => 'journal_entries', 'name' => 'Journal Entries', 'path' => '/journal-entries', 'status' => 'live', 'description' => 'Post manual debit and credit entries to the General Ledger.'],
            ['key' => 'customers', 'name' => 'Customers', 'path' => '/customers', 'status' => 'live', 'description' => 'Customer list, create, edit, and delete.'],
            ['key' => 'products', 'name' => 'Products', 'path' => '/products', 'status' => 'live', 'description' => 'Product catalog with pricing and status.'],
            ['key' => 'inventory', 'name' => 'Inventory', 'path' => '/inventory', 'status' => 'live', 'description' => 'Stock movements with audit logging.'],
            ['key' => 'users', 'name' => 'Users', 'path' => '/users', 'status' => 'live', 'description' => 'User and role administration.'],
            ['key' => 'audit_trail', 'name' => 'Audit Trail', 'path' => '/audit-trail', 'status' => 'live', 'description' => 'Company audit events and actions.'],
            ['key' => 'sales', 'name' => 'Sales', 'path' => '/sales', 'status' => 'live', 'description' => 'Sales performance and invoice flow overview.'],
            ['key' => 'customer_statement', 'name' => 'Customer Statement', 'path' => '/customer-statement', 'status' => 'live', 'description' => 'Per-customer invoice statements.'],
            ['key' => 'estimates', 'name' => 'Estimates', 'path' => '/estimates', 'status' => 'live', 'description' => 'Estimate create, list, edit, and delete.'],
            ['key' => 'credit_management', 'name' => 'Credit Management', 'path' => '/credit-management', 'status' => 'live', 'description' => 'Overdue and receivables credit tracking.'],
            ['key' => 'reports', 'name' => 'Reports', 'path' => '/reports', 'status' => 'live', 'description' => 'Cross-module KPI and summary reporting.'],
            ['key' => 'companies', 'name' => 'Companies', 'path' => '/companies', 'status' => 'live', 'description' => 'Company records overview.'],
            ['key' => 'company_details', 'name' => 'Company Details', 'path' => '/company-details', 'status' => 'live', 'description' => 'Company profile maintenance.'],
            ['key' => 'exchange_rates', 'name' => 'Exchange Rates', 'path' => '/exchange-rates', 'status' => 'live', 'description' => 'Manage currency exchange rates.'],
            ['key' => 'setup', 'name' => 'Setup', 'path' => '/setup', 'status' => 'live', 'description' => 'Runtime setup and environment view.'],
            ['key' => 'change_password', 'name' => 'Change Password', 'path' => '/change-password', 'status' => 'live', 'description' => 'Secure password update workflow.'],
        ];

        $cards = [];
        foreach ($nativeCards as $card) {
            if (!self::canAccess($roleKey, $card['key'], $roleModules)) {
                continue;
            }

            unset($card['key']);
            $cards[] = $card;
        }

        View::render('modules/index', [
            'company' => $context->company(),
            'modules' => $cards,
            'role_key' => $roleKey,
        ]);
    }

    public static function accounting(Database $db, RequestContext $context): void
    {
        View::render('modules/placeholder', [
            'company' => $context->company(),
            'module_name' => 'Accounting',
            'summary' => 'Chart of accounts, journal entries, and trial balance are queued for migration.',
        ]);
    }

    public static function payroll(Database $db, RequestContext $context): void
    {
        View::render('modules/placeholder', [
            'company' => $context->company(),
            'module_name' => 'Payroll',
            'summary' => 'Employee profiles, payslips, and statutory deductions are queued for migration.',
        ]);
    }

    private static function canAccess(string $roleKey, string $moduleKey, array $roleModules): bool
    {
        if ($roleKey === '') {
            return false;
        }

        $allowed = $roleModules[$roleKey] ?? [];
        if (in_array('*', $allowed, true)) {
            return true;
        }

        return in_array($moduleKey, $allowed, true);
    }
}
