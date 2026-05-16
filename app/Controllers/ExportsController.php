<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Database;
use App\Core\RequestContext;
use App\Core\View;
use App\Models\AuditLog;
use App\Models\Company;
use App\Models\Credit;
use App\Models\Customer;
use App\Models\Estimate;
use App\Models\InventoryMovement;
use App\Models\Invoice;
use App\Models\Product;

final class ExportsController
{
    public static function auditTrailCsv(Database $db, RequestContext $context): void
    {
        $companyId = self::companyId($context);
        if ($companyId === null) { self::deny(); return; }
        $rows = (new AuditLog($db->pdo()))->listByCompany($companyId, 10000);
        self::streamCsv('audit-trail-' . date('Y-m-d') . '.csv', ['Audit ID', 'User ID', 'Action', 'Entity', 'Entity ID', 'Details', 'Date'], static function () use ($rows): iterable {
            foreach ($rows as $r) {
                yield [
                    (string) $r['audit_id'],
                    (string) ($r['user_id'] ?? ''),
                    (string) $r['action_key'],
                    (string) $r['entity_type'],
                    (string) ($r['entity_id'] ?? ''),
                    (string) ($r['details'] ?? ''),
                    (string) $r['created_at'],
                ];
            }
        });
    }

    public static function auditTrailPdf(Database $db, RequestContext $context): void
    {
        $companyId = self::companyId($context);
        if ($companyId === null) { self::deny(); return; }
        $rows = (new AuditLog($db->pdo()))->listByCompany($companyId, 10000);
        View::render('exports/print_table', [
            'title' => 'Audit Trail Export',
            'company' => self::currentCompany($db, $context, $companyId),
            'columns' => ['Audit ID', 'User ID', 'Action', 'Entity', 'Entity ID', 'Details', 'Date'],
            'rows' => array_map(static fn(array $r): array => [
                (string) $r['audit_id'],
                (string) ($r['user_id'] ?? ''),
                (string) $r['action_key'],
                (string) $r['entity_type'],
                (string) ($r['entity_id'] ?? ''),
                (string) ($r['details'] ?? ''),
                (string) $r['created_at'],
            ], $rows),
        ]);
    }

    public static function companyDetailsCsv(Database $db, RequestContext $context): void
    {
        $companyId = self::companyId($context);
        if ($companyId === null) { self::deny(); return; }
        $d = (new Company($db->pdo()))->findById($companyId) ?? [];
        self::streamCsv('company-details-' . date('Y-m-d') . '.csv', ['Field', 'Value'], static function () use ($d): iterable {
            $fields = [
                'Company Name'   => $d['company_name'] ?? '',
                'Subdomain'      => $d['subdomain'] ?? '',
                'Phone'          => $d['phone'] ?? '',
                'Email'          => $d['email'] ?? '',
                'Address'        => $d['address'] ?? '',
                'City'           => $d['city'] ?? '',
                'Province'       => $d['province'] ?? '',
                'Postal Code'    => $d['postal_code'] ?? '',
                'Country'        => $d['country'] ?? '',
                'Tax Number'     => $d['tax_number'] ?? '',
                'VAT Number'     => $d['vat_number'] ?? '',
                'Bank Name'      => $d['bank_name'] ?? '',
                'Account Holder' => $d['bank_account_holder'] ?? '',
                'Account Number' => $d['bank_account_number'] ?? '',
                'Routing Number' => $d['bank_routing_number'] ?? '',
                'SWIFT Code'     => $d['bank_swift_code'] ?? '',
                'IBAN'           => $d['bank_iban'] ?? '',
            ];
            foreach ($fields as $field => $value) {
                yield [(string) $field, (string) $value];
            }
        });
    }

    public static function companyDetailsPdf(Database $db, RequestContext $context): void
    {
        $companyId = self::companyId($context);
        if ($companyId === null) { self::deny(); return; }
        $d = (new Company($db->pdo()))->findById($companyId) ?? [];
        $fields = [
            'Company Name'   => $d['company_name'] ?? '',
            'Subdomain'      => $d['subdomain'] ?? '',
            'Phone'          => $d['phone'] ?? '',
            'Email'          => $d['email'] ?? '',
            'Address'        => $d['address'] ?? '',
            'City'           => $d['city'] ?? '',
            'Province'       => $d['province'] ?? '',
            'Postal Code'    => $d['postal_code'] ?? '',
            'Country'        => $d['country'] ?? '',
            'Tax Number'     => $d['tax_number'] ?? '',
            'VAT Number'     => $d['vat_number'] ?? '',
            'Bank Name'      => $d['bank_name'] ?? '',
            'Account Holder' => $d['bank_account_holder'] ?? '',
            'Account Number' => $d['bank_account_number'] ?? '',
            'Routing Number' => $d['bank_routing_number'] ?? '',
            'SWIFT Code'     => $d['bank_swift_code'] ?? '',
            'IBAN'           => $d['bank_iban'] ?? '',
        ];
        View::render('exports/print_table', [
            'title'   => 'Company Details Export',
            'company' => self::currentCompany($db, $context, $companyId),
            'columns' => ['Field', 'Value'],
            'rows'    => array_map(static fn(string $k, string $v): array => [$k, $v], array_keys($fields), array_values($fields)),
        ]);
    }

    public static function invoicesCsv(Database $db, RequestContext $context): void
    {
        $companyId = self::companyId($context);
        if ($companyId === null) { self::deny(); return; }
        $rows = (new Invoice($db->pdo()))->listByCompany($companyId, 10000);
        $rows = self::filterInvoiceRows($rows);
        self::streamCsv('invoices-' . date('Y-m-d') . '.csv', ['Invoice ID', 'Client', 'Amount', 'Issue Date', 'Due Date', 'Status', 'Created At'], static function () use ($rows): iterable {
            foreach ($rows as $r) {
                yield [
                    (string) $r['invoice_id'],
                    (string) $r['client_name'],
                    (string) $r['amount'],
                    (string) $r['issue_date'],
                    (string) $r['due_date'],
                    (string) $r['status'],
                    (string) $r['created_at'],
                ];
            }
        });
    }

    public static function estimatesCsv(Database $db, RequestContext $context): void
    {
        $companyId = self::companyId($context);
        if ($companyId === null) { self::deny(); return; }
        $rows = (new Estimate($db->pdo()))->listByCompany($companyId, 10000);
        $rows = self::filterEstimateRows($rows);
        self::streamCsv('estimates-' . date('Y-m-d') . '.csv', ['Estimate ID', 'Client', 'Amount', 'Estimate Date', 'Expiry Date', 'Status', 'Created At'], static function () use ($rows): iterable {
            foreach ($rows as $r) {
                yield [
                    (string) $r['estimate_id'],
                    (string) $r['client_name'],
                    (string) $r['amount'],
                    (string) $r['estimate_date'],
                    (string) $r['expiry_date'],
                    (string) $r['status'],
                    (string) $r['created_at'],
                ];
            }
        });
    }

    public static function salesCsv(Database $db, RequestContext $context): void
    {
        $companyId = self::companyId($context);
        if ($companyId === null) { self::deny(); return; }

        $stmt = $db->pdo()->prepare('SELECT invoice_id, client_name, amount, status, issue_date, due_date, created_at FROM invoices WHERE company_id = :company_id ORDER BY invoice_id DESC');
        $stmt->execute(['company_id' => $companyId]);
        $rows = $stmt->fetchAll() ?: [];

        self::streamCsv('sales-' . date('Y-m-d') . '.csv', ['Invoice ID', 'Client', 'Amount', 'Status', 'Issue Date', 'Due Date', 'Created At'], static function () use ($rows): iterable {
            foreach ($rows as $r) {
                yield [
                    (string) $r['invoice_id'],
                    (string) $r['client_name'],
                    (string) $r['amount'],
                    (string) $r['status'],
                    (string) $r['issue_date'],
                    (string) $r['due_date'],
                    (string) $r['created_at'],
                ];
            }
        });
    }

    public static function customersCsv(Database $db, RequestContext $context): void
    {
        $companyId = self::companyId($context);
        if ($companyId === null) { self::deny(); return; }
        $rows = (new Customer($db->pdo()))->listByCompany($companyId, 10000);
        $rows = self::filterCustomerRows($rows);
        self::streamCsv('customers-' . date('Y-m-d') . '.csv', ['Customer ID', 'Name', 'Email', 'Phone', 'Credit Limit', 'Status', 'Created At'], static function () use ($rows): iterable {
            foreach ($rows as $r) {
                yield [
                    (string) $r['customer_id'],
                    (string) $r['customer_name'],
                    (string) ($r['email'] ?? ''),
                    (string) ($r['phone'] ?? ''),
                    (string) $r['credit_limit'],
                    ((int) $r['is_active'] === 1) ? 'active' : 'inactive',
                    (string) $r['created_at'],
                ];
            }
        });
    }

    public static function customersPdf(Database $db, RequestContext $context): void
    {
        $companyId = self::companyId($context);
        if ($companyId === null) { self::deny(); return; }
        $rows = (new Customer($db->pdo()))->listByCompany($companyId, 10000);
        $rows = self::filterCustomerRows($rows);
        View::render('exports/print_table', [
            'title' => 'Customers Export',
            'company' => self::currentCompany($db, $context, $companyId),
            'columns' => ['Customer ID', 'Name', 'Email', 'Phone', 'Credit Limit', 'Status'],
            'rows' => array_map(static fn(array $r): array => [(string) $r['customer_id'], (string) $r['customer_name'], (string) ($r['email'] ?? ''), (string) ($r['phone'] ?? ''), (string) $r['credit_limit'], ((int) $r['is_active'] === 1) ? 'active' : 'inactive'], $rows),
        ]);
    }

    public static function productsCsv(Database $db, RequestContext $context): void
    {
        $companyId = self::companyId($context);
        if ($companyId === null) { self::deny(); return; }
        $rows = (new Product($db->pdo()))->listByCompany($companyId, 10000);
        self::streamCsv('products-' . date('Y-m-d') . '.csv', ['Product ID', 'SKU', 'Name', 'Unit Price', 'Stock', 'Status', 'Created At'], static function () use ($rows): iterable {
            foreach ($rows as $r) {
                yield [
                    (string) $r['product_id'],
                    (string) ($r['sku'] ?? ''),
                    (string) $r['product_name'],
                    (string) $r['unit_price'],
                    (string) $r['stock_qty'],
                    ((int) $r['is_active'] === 1) ? 'active' : 'inactive',
                    (string) $r['created_at'],
                ];
            }
        });
    }

    public static function productsPdf(Database $db, RequestContext $context): void
    {
        $companyId = self::companyId($context);
        if ($companyId === null) { self::deny(); return; }
        $rows = (new Product($db->pdo()))->listByCompany($companyId, 10000);
        View::render('exports/print_table', [
            'title' => 'Products Export',
            'company' => self::currentCompany($db, $context, $companyId),
            'columns' => ['Product ID', 'SKU', 'Name', 'Unit Price', 'Stock', 'Status'],
            'rows' => array_map(static fn(array $r): array => [(string) $r['product_id'], (string) ($r['sku'] ?? ''), (string) $r['product_name'], (string) $r['unit_price'], (string) $r['stock_qty'], ((int) $r['is_active'] === 1) ? 'active' : 'inactive'], $rows),
        ]);
    }

    public static function inventoryCsv(Database $db, RequestContext $context): void
    {
        $companyId = self::companyId($context);
        if ($companyId === null) { self::deny(); return; }
        $rows = (new InventoryMovement($db->pdo()))->listByCompany($companyId, 10000);
        self::streamCsv('inventory-movements-' . date('Y-m-d') . '.csv',
            ['Movement ID', 'Product', 'SKU', 'Type', 'Quantity', 'Stock Before', 'Stock After', 'Note', 'By', 'Created At'],
            static function () use ($rows): iterable {
                foreach ($rows as $r) {
                    yield [
                        (string) $r['movement_id'],
                        (string) $r['product_name'],
                        (string) ($r['sku']        ?? ''),
                        (string) $r['movement_type'],
                        (string) $r['quantity'],
                        (string) ($r['qty_before'] ?? ''),
                        (string) ($r['qty_after']  ?? ''),
                        (string) ($r['note']       ?? ''),
                        (string) ($r['actor_name'] ?? ''),
                        (string) $r['created_at'],
                    ];
                }
            }
        );
    }

    public static function inventoryPdf(Database $db, RequestContext $context): void
    {
        $companyId = self::companyId($context);
        if ($companyId === null) { self::deny(); return; }
        $rows = (new InventoryMovement($db->pdo()))->listByCompany($companyId, 10000);
        $typeMap = [
            'in' => 'Stock In', 'out' => 'Stock Out', 'sold' => 'Sold',
            'returned' => 'Returned', 'destroyed' => 'Destroyed', 'adjustment' => 'Adjustment',
        ];
        View::render('exports/print_table', [
            'title'   => 'Inventory Movements Export',
            'company' => self::currentCompany($db, $context, $companyId),
            'columns' => ['ID', 'Product', 'SKU', 'Type', 'Qty', 'Before', 'After', 'Note', 'By', 'Created At'],
            'rows'    => array_map(static fn(array $r): array => [
                (string) $r['movement_id'],
                (string) $r['product_name'],
                (string) ($r['sku']        ?? ''),
                (string) ($typeMap[$r['movement_type']] ?? $r['movement_type']),
                (string) $r['quantity'],
                (string) ($r['qty_before'] ?? ''),
                (string) ($r['qty_after']  ?? ''),
                (string) ($r['note']       ?? ''),
                (string) ($r['actor_name'] ?? ''),
                (string) $r['created_at'],
            ], $rows),
        ]);
    }

    public static function inventoryAuditCsv(Database $db, RequestContext $context): void
    {
        $companyId = self::companyId($context);
        if ($companyId === null) { self::deny(); return; }

        $productId    = (int)    ($_GET['product_id']    ?? 0);
        $movementType = trim((string) ($_GET['movement_type'] ?? ''));
        $fromDate     = trim((string) ($_GET['from']          ?? ''));
        $toDate       = trim((string) ($_GET['to']            ?? ''));
        $search       = trim((string) ($_GET['q']             ?? ''));

        $rows = (new InventoryMovement($db->pdo()))->listFiltered(
            $companyId, $productId, $movementType, $fromDate, $toDate, $search, 10000
        );

        $label = 'inventory-audit'
            . ($fromDate !== '' ? '-from-' . $fromDate : '')
            . ($toDate   !== '' ? '-to-'   . $toDate   : '')
            . '-' . date('Y-m-d') . '.csv';

        self::streamCsv($label,
            ['Movement ID', 'Date', 'Product', 'SKU', 'Type', 'Quantity', 'Stock Before', 'Stock After', 'Note', 'By'],
            static function () use ($rows): iterable {
                foreach ($rows as $r) {
                    yield [
                        (string) $r['movement_id'],
                        (string) $r['created_at'],
                        (string) $r['product_name'],
                        (string) ($r['sku']        ?? ''),
                        (string) $r['movement_type'],
                        (string) $r['quantity'],
                        (string) ($r['qty_before'] ?? ''),
                        (string) ($r['qty_after']  ?? ''),
                        (string) ($r['note']       ?? ''),
                        (string) ($r['actor_name'] ?? ''),
                    ];
                }
            }
        );
    }

    public static function inventoryAuditPdf(Database $db, RequestContext $context): void
    {
        $companyId = self::companyId($context);
        if ($companyId === null) { self::deny(); return; }

        $productId    = (int)    ($_GET['product_id']    ?? 0);
        $movementType = trim((string) ($_GET['movement_type'] ?? ''));
        $fromDate     = trim((string) ($_GET['from']          ?? ''));
        $toDate       = trim((string) ($_GET['to']            ?? ''));
        $search       = trim((string) ($_GET['q']             ?? ''));

        $rows = (new InventoryMovement($db->pdo()))->listFiltered(
            $companyId, $productId, $movementType, $fromDate, $toDate, $search, 10000
        );

        $title = 'Stock Audit';
        if ($fromDate !== '' || $toDate !== '') {
            $title .= ' (' . ($fromDate ?: '...') . ' to ' . ($toDate ?: 'now') . ')';
        }

        $typeMap = [
            'in' => 'Stock In', 'out' => 'Stock Out', 'sold' => 'Sold',
            'returned' => 'Returned', 'destroyed' => 'Destroyed', 'adjustment' => 'Adjustment',
        ];

        View::render('exports/print_table', [
            'title'   => $title,
            'company' => self::currentCompany($db, $context, $companyId),
            'columns' => ['ID', 'Date', 'Product', 'SKU', 'Type', 'Qty', 'Before', 'After', 'Note', 'By'],
            'rows'    => array_map(static fn(array $r): array => [
                (string) $r['movement_id'],
                (string) $r['created_at'],
                (string) $r['product_name'],
                (string) ($r['sku']        ?? ''),
                (string) ($typeMap[$r['movement_type']] ?? $r['movement_type']),
                (string) $r['quantity'],
                (string) ($r['qty_before'] ?? ''),
                (string) ($r['qty_after']  ?? ''),
                (string) ($r['note']       ?? ''),
                (string) ($r['actor_name'] ?? ''),
            ], $rows),
        ]);
    }

    public static function creditsCsv(Database $db, RequestContext $context): void
    {
        $companyId = self::companyId($context);
        if ($companyId === null) { self::deny(); return; }
        $creditModel = new Credit($db->pdo());
        $creditModel->reconcileByCompany($companyId);
        $rows = $creditModel->listByCompany($companyId);
        $rows = self::filterCreditRows($rows);
        self::streamCsv('credits-' . date('Y-m-d') . '.csv', ['Credit ID', 'Credit No', 'Customer', 'Amount Issued', 'Interest %', 'Interest Amount', 'Total Owed', 'Amount Paid', 'Outstanding', 'Due Date', 'Status', 'Reason'], static function () use ($rows): iterable {
            foreach ($rows as $r) {
                yield [
                    (string) $r['credit_id'],
                    (string) $r['credit_no'],
                    (string) $r['customer_name'],
                    (string) $r['amount'],
                    (string) $r['interest_percent'],
                    (string) $r['interest_amount'],
                    (string) $r['total_amount'],
                    (string) $r['amount_paid'],
                    (string) $r['outstanding'],
                    (string) ($r['due_date'] ?? ''),
                    (string) $r['status'],
                    (string) ($r['reason'] ?? ''),
                ];
            }
        });
    }

    public static function creditsPdf(Database $db, RequestContext $context): void
    {
        $companyId = self::companyId($context);
        if ($companyId === null) { self::deny(); return; }
        $creditModel = new Credit($db->pdo());
        $creditModel->reconcileByCompany($companyId);
        $rows = $creditModel->listByCompany($companyId);
        $rows = self::filterCreditRows($rows);
        View::render('exports/print_table', [
            'title' => 'Credit Management Export',
            'company' => self::currentCompany($db, $context, $companyId),
            'columns' => ['Credit No', 'Customer', 'Amount Issued', 'Interest %', 'Interest Amount', 'Total Owed', 'Paid', 'Outstanding', 'Due Date', 'Status'],
            'rows' => array_map(static fn(array $r): array => [(string) $r['credit_no'], (string) $r['customer_name'], (string) $r['amount'], (string) $r['interest_percent'], (string) $r['interest_amount'], (string) $r['total_amount'], (string) $r['amount_paid'], (string) $r['outstanding'], (string) ($r['due_date'] ?? ''), (string) $r['status']], $rows),
        ]);
    }

    public static function companiesCsv(Database $db, RequestContext $context): void
    {
        $rows = $db->pdo()->query('SELECT company_id, company_name, subdomain, phone, email, tax_number, vat_number, status, created_at FROM companies ORDER BY company_id DESC')->fetchAll() ?: [];
        self::streamCsv('companies-' . date('Y-m-d') . '.csv', ['Company ID', 'Company Name', 'Subdomain', 'Phone', 'Email', 'Tax Number', 'VAT Number', 'Status', 'Created At'], static function () use ($rows): iterable {
            foreach ($rows as $r) {
                yield [
                    (string) $r['company_id'],
                    (string) $r['company_name'],
                    (string) $r['subdomain'],
                    (string) ($r['phone'] ?? ''),
                    (string) ($r['email'] ?? ''),
                    (string) ($r['tax_number'] ?? ''),
                    (string) ($r['vat_number'] ?? ''),
                    (string) $r['status'],
                    (string) $r['created_at'],
                ];
            }
        });
    }

    public static function invoicesPdf(Database $db, RequestContext $context): void
    {
        $companyId = self::companyId($context);
        if ($companyId === null) { self::deny(); return; }
        $rows = (new Invoice($db->pdo()))->listByCompany($companyId, 10000);
        $rows = self::filterInvoiceRows($rows);
        View::render('exports/print_table', [
            'title' => 'Invoices Export',
            'company' => self::currentCompany($db, $context, $companyId),
            'columns' => ['Invoice ID', 'Client', 'Amount', 'Issue Date', 'Due Date', 'Status'],
            'rows' => array_map(static fn(array $r): array => [(string) $r['invoice_id'], (string) $r['client_name'], (string) $r['amount'], (string) $r['issue_date'], (string) $r['due_date'], (string) $r['status']], $rows),
        ]);
    }

    public static function estimatesPdf(Database $db, RequestContext $context): void
    {
        $companyId = self::companyId($context);
        if ($companyId === null) { self::deny(); return; }
        $rows = (new Estimate($db->pdo()))->listByCompany($companyId, 10000);
        $rows = self::filterEstimateRows($rows);
        View::render('exports/print_table', [
            'title' => 'Estimates Export',
            'company' => self::currentCompany($db, $context, $companyId),
            'columns' => ['Estimate ID', 'Client', 'Amount', 'Estimate Date', 'Expiry Date', 'Status'],
            'rows' => array_map(static fn(array $r): array => [(string) $r['estimate_id'], (string) $r['client_name'], (string) $r['amount'], (string) $r['estimate_date'], (string) $r['expiry_date'], (string) $r['status']], $rows),
        ]);
    }

    public static function salesPdf(Database $db, RequestContext $context): void
    {
        $companyId = self::companyId($context);
        if ($companyId === null) { self::deny(); return; }
        $stmt = $db->pdo()->prepare('SELECT invoice_id, client_name, amount, status, issue_date, due_date FROM invoices WHERE company_id = :company_id ORDER BY invoice_id DESC');
        $stmt->execute(['company_id' => $companyId]);
        $rows = $stmt->fetchAll() ?: [];
        View::render('exports/print_table', [
            'title' => 'Sales Export',
            'company' => self::currentCompany($db, $context, $companyId),
            'columns' => ['Invoice ID', 'Client', 'Amount', 'Status', 'Issue Date', 'Due Date'],
            'rows' => array_map(static fn(array $r): array => [(string) $r['invoice_id'], (string) $r['client_name'], (string) $r['amount'], (string) $r['status'], (string) $r['issue_date'], (string) $r['due_date']], $rows),
        ]);
    }

    public static function financialStatementCsv(Database $db, RequestContext $context): void
    {
        $companyId = self::companyId($context);
        if ($companyId === null) { self::deny(); return; }

        $fromDate = trim((string) ($_GET['from'] ?? date('Y-01-01')));
        $toDate = trim((string) ($_GET['to'] ?? date('Y-m-d')));

        $incomeStmt = $db->pdo()->prepare(
            'SELECT amount, status FROM invoices WHERE company_id = :company_id AND issue_date BETWEEN :from AND :to'
        );
        $incomeStmt->execute(['company_id' => $companyId, 'from' => $fromDate, 'to' => $toDate]);
        $incomeRows = $incomeStmt->fetchAll() ?: [];

        $totalInvoiced = 0.0;
        $totalPaid = 0.0;
        foreach ($incomeRows as $row) {
            $amount = (float) ($row['amount'] ?? 0);
            $totalInvoiced += $amount;
            if ((string) ($row['status'] ?? '') === 'paid') {
                $totalPaid += $amount;
            }
        }

        $expenseStmt = $db->pdo()->prepare(
            'SELECT COALESCE(SUM(amount), 0) AS total_expenses FROM expenses WHERE company_id = :company_id AND expense_date BETWEEN :from AND :to'
        );
        $expenseStmt->execute(['company_id' => $companyId, 'from' => $fromDate, 'to' => $toDate]);
        $baseExpenses = (float) (($expenseStmt->fetch()['total_expenses'] ?? 0));
        $totalExpenses = $baseExpenses + self::journalExpenseTotal($db, $companyId, $fromDate, $toDate);

        $netIncome = $totalPaid - $totalExpenses;

        self::streamCsv('financial-statement-' . $fromDate . '-to-' . $toDate . '.csv', ['Metric', 'Value'], static function () use ($fromDate, $toDate, $totalInvoiced, $totalPaid, $totalExpenses, $netIncome): iterable {
            yield ['Period From', $fromDate];
            yield ['Period To', $toDate];
            yield ['Total Invoiced', number_format($totalInvoiced, 2, '.', '')];
            yield ['Total Collected (Paid)', number_format($totalPaid, 2, '.', '')];
            yield ['Total Expenses', number_format($totalExpenses, 2, '.', '')];
            yield ['Net Income (Collected - Expenses)', number_format($netIncome, 2, '.', '')];
        });
    }

    public static function financialStatementPdf(Database $db, RequestContext $context): void
    {
        $companyId = self::companyId($context);
        if ($companyId === null) { self::deny(); return; }

        $fromDate = trim((string) ($_GET['from'] ?? date('Y-01-01')));
        $toDate = trim((string) ($_GET['to'] ?? date('Y-m-d')));

        $incomeStmt = $db->pdo()->prepare(
            'SELECT amount, status FROM invoices WHERE company_id = :company_id AND issue_date BETWEEN :from AND :to'
        );
        $incomeStmt->execute(['company_id' => $companyId, 'from' => $fromDate, 'to' => $toDate]);
        $incomeRows = $incomeStmt->fetchAll() ?: [];

        $totalInvoiced = 0.0;
        $totalPaid = 0.0;
        foreach ($incomeRows as $row) {
            $amount = (float) ($row['amount'] ?? 0);
            $totalInvoiced += $amount;
            if ((string) ($row['status'] ?? '') === 'paid') {
                $totalPaid += $amount;
            }
        }

        $expenseStmt = $db->pdo()->prepare(
            'SELECT COALESCE(SUM(amount), 0) AS total_expenses FROM expenses WHERE company_id = :company_id AND expense_date BETWEEN :from AND :to'
        );
        $expenseStmt->execute(['company_id' => $companyId, 'from' => $fromDate, 'to' => $toDate]);
        $baseExpenses = (float) (($expenseStmt->fetch()['total_expenses'] ?? 0));
        $totalExpenses = $baseExpenses + self::journalExpenseTotal($db, $companyId, $fromDate, $toDate);
        $netIncome = $totalPaid - $totalExpenses;

        View::render('exports/print_table', [
            'title' => 'Financial Statement (' . $fromDate . ' to ' . $toDate . ')',
            'company' => (new Company($db->pdo()))->findById($companyId) ?? $context->company(),
            'back_href' => '/sales/financial-statement?from=' . urlencode($fromDate) . '&to=' . urlencode($toDate),
            'columns' => ['Metric', 'Value'],
            'rows' => [
                ['Period From', $fromDate],
                ['Period To', $toDate],
                ['Total Invoiced', 'N$ ' . number_format($totalInvoiced, 2)],
                ['Total Collected (Paid)', 'N$ ' . number_format($totalPaid, 2)],
                ['Total Expenses', 'N$ ' . number_format($totalExpenses, 2)],
                ['Net Income (Collected - Expenses)', 'N$ ' . number_format($netIncome, 2)],
            ],
        ]);
    }

    public static function generalLedgerCsv(Database $db, RequestContext $context): void
    {
        $companyId = self::companyId($context);
        if ($companyId === null) { self::deny(); return; }

        $fromDate = trim((string) ($_GET['from'] ?? date('Y-01-01')));
        $toDate = trim((string) ($_GET['to'] ?? date('Y-m-d')));

        $invoiceStmt = $db->pdo()->prepare(
            'SELECT issue_date AS txn_date, invoice_no AS ref_no, client_name AS description, amount
             FROM invoices
             WHERE company_id = :company_id AND issue_date BETWEEN :from AND :to
             ORDER BY issue_date ASC, invoice_id ASC'
        );
        $invoiceStmt->execute(['company_id' => $companyId, 'from' => $fromDate, 'to' => $toDate]);
        $invoices = $invoiceStmt->fetchAll() ?: [];

        $expenseStmt = $db->pdo()->prepare(
            'SELECT expense_date AS txn_date, expense_id, category, description, amount
             FROM expenses
             WHERE company_id = :company_id AND expense_date BETWEEN :from AND :to
             ORDER BY expense_date ASC, expense_id ASC'
        );
        $expenseStmt->execute(['company_id' => $companyId, 'from' => $fromDate, 'to' => $toDate]);
        $expenses = $expenseStmt->fetchAll() ?: [];

        $journalStmtCsv = $db->pdo()->prepare(
            'SELECT entry_date AS txn_date, entry_id, account, reference, description, debit, credit
             FROM journal_entries
             WHERE company_id = :company_id AND entry_date BETWEEN :from AND :to
             ORDER BY entry_date ASC, entry_id ASC'
        );
        $journalStmtCsv->execute(['company_id' => $companyId, 'from' => $fromDate, 'to' => $toDate]);
        $journalRowsCsv = $journalStmtCsv->fetchAll() ?: [];

        self::streamCsv('general-ledger-' . $fromDate . '-to-' . $toDate . '.csv', ['Date', 'Account', 'Reference', 'Description', 'Debit', 'Credit'], static function () use ($invoices, $expenses, $journalRowsCsv): iterable {
            foreach ($invoices as $row) {
                $amount = (float) ($row['amount'] ?? 0);
                yield [
                    (string) ($row['txn_date'] ?? ''),
                    'Sales Revenue',
                    (string) ($row['ref_no'] ?? ''),
                    (string) ($row['description'] ?? 'Invoice'),
                    '0.00',
                    number_format($amount, 2, '.', ''),
                ];
            }
            foreach ($expenses as $row) {
                $amount = (float) ($row['amount'] ?? 0);
                yield [
                    (string) ($row['txn_date'] ?? ''),
                    (string) ($row['category'] ?? 'Expense'),
                    'EXP-' . (string) ($row['expense_id'] ?? ''),
                    (string) ($row['description'] ?? 'Expense entry'),
                    number_format($amount, 2, '.', ''),
                    '0.00',
                ];
            }
            foreach ($journalRowsCsv as $row) {
                $debit  = (float) ($row['debit']  ?? 0);
                $credit = (float) ($row['credit'] ?? 0);
                yield [
                    (string) ($row['txn_date']    ?? ''),
                    (string) ($row['account']     ?? 'Journal Entry'),
                    'JNL-' . (string) ($row['entry_id'] ?? ''),
                    (string) ($row['description'] ?? ''),
                    number_format($debit,  2, '.', ''),
                    number_format($credit, 2, '.', ''),
                ];
            }
        });
    }

    public static function generalLedgerPdf(Database $db, RequestContext $context): void
    {
        $companyId = self::companyId($context);
        if ($companyId === null) { self::deny(); return; }

        $fromDate = trim((string) ($_GET['from'] ?? date('Y-01-01')));
        $toDate = trim((string) ($_GET['to'] ?? date('Y-m-d')));

        $invoiceStmt = $db->pdo()->prepare(
            'SELECT issue_date AS txn_date, invoice_no AS ref_no, client_name AS description, amount
             FROM invoices
             WHERE company_id = :company_id AND issue_date BETWEEN :from AND :to
             ORDER BY issue_date ASC, invoice_id ASC'
        );
        $invoiceStmt->execute(['company_id' => $companyId, 'from' => $fromDate, 'to' => $toDate]);
        $invoices = $invoiceStmt->fetchAll() ?: [];

        $expenseStmt = $db->pdo()->prepare(
            'SELECT expense_date AS txn_date, expense_id, category, description, amount
             FROM expenses
             WHERE company_id = :company_id AND expense_date BETWEEN :from AND :to
             ORDER BY expense_date ASC, expense_id ASC'
        );
        $expenseStmt->execute(['company_id' => $companyId, 'from' => $fromDate, 'to' => $toDate]);
        $expenses = $expenseStmt->fetchAll() ?: [];

        $journalStmtPdf = $db->pdo()->prepare(
            'SELECT entry_date AS txn_date, entry_id, account, reference, description, debit, credit
             FROM journal_entries
             WHERE company_id = :company_id AND entry_date BETWEEN :from AND :to
             ORDER BY entry_date ASC, entry_id ASC'
        );
        $journalStmtPdf->execute(['company_id' => $companyId, 'from' => $fromDate, 'to' => $toDate]);
        $journalRowsPdf = $journalStmtPdf->fetchAll() ?: [];

        $rows = [];
        foreach ($invoices as $row) {
            $rows[] = [
                (string) ($row['txn_date'] ?? ''),
                'Sales Revenue',
                (string) ($row['ref_no'] ?? ''),
                (string) ($row['description'] ?? 'Invoice'),
                '0.00',
                'N$ ' . number_format((float) ($row['amount'] ?? 0), 2),
            ];
        }
        foreach ($expenses as $row) {
            $rows[] = [
                (string) ($row['txn_date'] ?? ''),
                (string) ($row['category'] ?? 'Expense'),
                'EXP-' . (string) ($row['expense_id'] ?? ''),
                (string) ($row['description'] ?? 'Expense entry'),
                'N$ ' . number_format((float) ($row['amount'] ?? 0), 2),
                '0.00',
            ];
        }
        foreach ($journalRowsPdf as $row) {
            $debit  = (float) ($row['debit']  ?? 0);
            $credit = (float) ($row['credit'] ?? 0);
            $rows[] = [
                (string) ($row['txn_date']    ?? ''),
                (string) ($row['account']     ?? 'Journal Entry'),
                'JNL-' . (string) ($row['entry_id'] ?? ''),
                (string) ($row['description'] ?? ''),
                $debit  > 0 ? 'N$ ' . number_format($debit,  2) : '0.00',
                $credit > 0 ? 'N$ ' . number_format($credit, 2) : '0.00',
            ];
        }

        usort($rows, static fn(array $a, array $b): int => strcmp((string) $a[0], (string) $b[0]));

        View::render('exports/print_table', [
            'title' => 'General Ledger (' . $fromDate . ' to ' . $toDate . ')',
            'company' => (new Company($db->pdo()))->findById($companyId) ?? $context->company(),
            'columns' => ['Date', 'Account', 'Reference', 'Description', 'Debit', 'Credit'],
            'rows' => $rows,
        ]);
    }

    public static function journalEntriesCsv(Database $db, RequestContext $context): void
    {
        $companyId = self::companyId($context);
        if ($companyId === null) { self::deny(); return; }

        $fromDate = trim((string) ($_GET['from'] ?? date('Y-01-01')));
        $toDate   = trim((string) ($_GET['to']   ?? date('Y-m-d')));

        $stmt = $db->pdo()->prepare(
            'SELECT entry_id, entry_date, account, reference, description, debit, credit, created_at
             FROM journal_entries
             WHERE company_id = :company_id AND entry_date BETWEEN :from AND :to
             ORDER BY entry_date ASC, entry_id ASC'
        );
        $stmt->execute(['company_id' => $companyId, 'from' => $fromDate, 'to' => $toDate]);
        $jeRows = $stmt->fetchAll() ?: [];

        self::streamCsv('journal-entries-' . $fromDate . '-to-' . $toDate . '.csv',
            ['ID', 'Date', 'Account', 'Reference', 'Description', 'Debit', 'Credit', 'Posted At'],
            static function () use ($jeRows): iterable {
                foreach ($jeRows as $r) {
                    yield [
                        (string) $r['entry_id'],
                        (string) $r['entry_date'],
                        (string) $r['account'],
                        (string) ($r['reference']   ?? ''),
                        (string) ($r['description'] ?? ''),
                        number_format((float) $r['debit'],  2, '.', ''),
                        number_format((float) $r['credit'], 2, '.', ''),
                        (string) ($r['created_at']  ?? ''),
                    ];
                }
            }
        );
    }

    public static function journalEntriesPdf(Database $db, RequestContext $context): void
    {
        $companyId = self::companyId($context);
        if ($companyId === null) { self::deny(); return; }

        $fromDate = trim((string) ($_GET['from'] ?? date('Y-01-01')));
        $toDate   = trim((string) ($_GET['to']   ?? date('Y-m-d')));

        $stmt = $db->pdo()->prepare(
            'SELECT entry_id, entry_date, account, reference, description, debit, credit, created_at
             FROM journal_entries
             WHERE company_id = :company_id AND entry_date BETWEEN :from AND :to
             ORDER BY entry_date ASC, entry_id ASC'
        );
        $stmt->execute(['company_id' => $companyId, 'from' => $fromDate, 'to' => $toDate]);
        $jeRows = $stmt->fetchAll() ?: [];

        View::render('exports/print_table', [
            'title'   => 'Journal Entries (' . $fromDate . ' to ' . $toDate . ')',
            'company' => self::currentCompany($db, $context, $companyId),
            'columns' => ['ID', 'Date', 'Account', 'Reference', 'Description', 'Debit', 'Credit'],
            'rows'    => array_map(static fn(array $r): array => [
                (string) $r['entry_id'],
                (string) $r['entry_date'],
                (string) $r['account'],
                (string) ($r['reference']   ?? ''),
                (string) ($r['description'] ?? ''),
                (float) $r['debit']  > 0 ? 'N$ ' . number_format((float) $r['debit'],  2) : '0.00',
                (float) $r['credit'] > 0 ? 'N$ ' . number_format((float) $r['credit'], 2) : '0.00',
            ], $jeRows),
        ]);
    }

    public static function companiesPdf(Database $db, RequestContext $context): void
    {
        $rows = $db->pdo()->query('SELECT company_id, company_name, subdomain, phone, email, tax_number, vat_number, status, created_at FROM companies ORDER BY company_id DESC')->fetchAll() ?: [];
        View::render('exports/print_table', [
            'title' => 'Companies Export',
            'company' => self::currentCompany($db, $context, (int) ($context->company()['company_id'] ?? 0)),
            'columns' => ['Company ID', 'Name', 'Subdomain', 'Phone', 'Email', 'Tax', 'VAT', 'Status', 'Created'],
            'rows' => array_map(static fn(array $r): array => [(string) $r['company_id'], (string) $r['company_name'], (string) $r['subdomain'], (string) ($r['phone'] ?? ''), (string) ($r['email'] ?? ''), (string) ($r['tax_number'] ?? ''), (string) ($r['vat_number'] ?? ''), (string) $r['status'], (string) $r['created_at']], $rows),
        ]);
    }

    private static function journalExpenseTotal(Database $db, int $companyId, string $fromDate, string $toDate): float
    {
        $stmt = $db->pdo()->prepare(
            'SELECT account, debit, credit
             FROM journal_entries
             WHERE company_id = :company_id AND entry_date BETWEEN :from AND :to'
        );
        $stmt->execute(['company_id' => $companyId, 'from' => $fromDate, 'to' => $toDate]);
        $rows = $stmt->fetchAll() ?: [];

        $total = 0.0;
        foreach ($rows as $row) {
            if (!in_array((string) ($row['account'] ?? ''), self::expenseAccounts(), true)) {
                continue;
            }

            $amount = (float) ($row['debit'] ?? 0) - (float) ($row['credit'] ?? 0);
            if ($amount > 0) {
                $total += $amount;
            }
        }

        return $total;
    }

    private static function expenseAccounts(): array
    {
        return [
            'Rent',
            'Salaries',
            'Utilities',
            'Transport',
            'Office Supplies',
            'Marketing',
            'Maintenance',
            'Software',
            'Professional Fees',
            'Interest Expense',
            'Depreciation',
            'Bad Debt Expense',
            'Other Expense',
            'Other',
        ];
    }

    private static function currentCompany(Database $db, RequestContext $context, int $companyId): array
    {
        if ($companyId > 0) {
            $fresh = (new Company($db->pdo()))->findById($companyId);
            if ($fresh !== null) {
                return $fresh;
            }
        }

        return $context->company();
    }

    private static function streamCsv(string $filename, array $header, callable $rowsGenerator): void
    {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=' . $filename);

        $out = fopen('php://output', 'wb');
        if ($out === false) {
            http_response_code(500);
            echo 'Unable to stream CSV.';
            return;
        }

        fputcsv($out, $header);
        foreach ($rowsGenerator() as $row) {
            fputcsv($out, $row);
        }
        fclose($out);
    }

    public static function invoicesOverduePdf(Database $db, RequestContext $context): void
    {
        $companyId = self::companyId($context);
        if ($companyId === null) { self::deny(); return; }

        $stmt = $db->pdo()->prepare(
            'SELECT invoice_id, invoice_no, client_name, amount, issue_date, due_date, status
             FROM invoices
             WHERE company_id = :company_id
               AND status NOT IN (\'paid\', \'cancelled\')
               AND due_date < CURDATE()
             ORDER BY due_date ASC'
        );
        $stmt->execute(['company_id' => $companyId]);
        $rows = $stmt->fetchAll() ?: [];
        $rows = self::filterOverdueInvoiceRows($rows);

        View::render('exports/print_table', [
            'title'   => 'Overdue Invoices - ' . date('Y-m-d'),
            'company' => (new Company($db->pdo()))->findById($companyId) ?? $context->company(),
            'columns' => ['ID', 'Invoice No', 'Client', 'Amount', 'Issue Date', 'Due Date', 'Status'],
            'rows'    => array_map(static fn(array $r): array => [
                (string) $r['invoice_id'],
                (string) ($r['invoice_no'] ?? ''),
                (string) $r['client_name'],
                'N$ ' . number_format((float) $r['amount'], 2),
                (string) $r['issue_date'],
                (string) $r['due_date'],
                strtoupper((string) $r['status']),
            ], $rows),
        ]);
    }

    private static function filterOverdueInvoiceRows(array $rows): array
    {
        $q = trim((string) ($_GET['q'] ?? ''));
        $status = trim((string) ($_GET['status'] ?? ''));
        $from = trim((string) ($_GET['from'] ?? ''));
        $to = trim((string) ($_GET['to'] ?? ''));

        if ($q !== '') {
            $needle = mb_strtolower($q);
            $rows = array_values(array_filter($rows, static function (array $row) use ($needle): bool {
                $hay = mb_strtolower(
                    (string) ($row['invoice_no'] ?? '') . ' ' .
                    (string) ($row['client_name'] ?? '') . ' ' .
                    (string) ($row['status'] ?? '') . ' ' .
                    (string) ($row['issue_date'] ?? '') . ' ' .
                    (string) ($row['due_date'] ?? '')
                );
                return str_contains($hay, $needle);
            }));
        }

        // Keep overdue endpoint purpose-specific, but apply status when explicitly passed.
        if ($status !== '') {
            $rows = array_values(array_filter($rows, static fn(array $row): bool => mb_strtolower((string) ($row['status'] ?? '')) === mb_strtolower($status)));
        }

        if ($from !== '' || $to !== '') {
            $rows = array_values(array_filter($rows, static function (array $row) use ($from, $to): bool {
                $dueDate = (string) ($row['due_date'] ?? '');
                if ($dueDate === '') {
                    return false;
                }
                if ($from !== '' && $dueDate < $from) {
                    return false;
                }
                if ($to !== '' && $dueDate > $to) {
                    return false;
                }
                return true;
            }));
        }

        return $rows;
    }

    public static function customerStatementPdf(Database $db, RequestContext $context): void
    {
        $companyId  = self::companyId($context);
        if ($companyId === null) { self::deny(); return; }

        $customerId = (int) ($_GET['customer_id'] ?? 0);
        $customer   = null;
        $rows       = [];

        if ($customerId > 0) {
            $customer = (new Customer($db->pdo()))->findByIdForCompany($customerId, $companyId);
            if ($customer !== null) {
                $stmt = $db->pdo()->prepare(
                    'SELECT invoice_id, invoice_no, amount, status, issue_date, due_date
                     FROM invoices
                     WHERE company_id = :company_id AND customer_id = :customer_id
                     ORDER BY issue_date'
                );
                $stmt->execute(['company_id' => $companyId, 'customer_id' => $customerId]);
                $rows = $stmt->fetchAll() ?: [];
            }
        }

        $title = 'Customer Statement' . ($customer ? ' – ' . $customer['customer_name'] : '');
        View::render('exports/print_table', [
            'title'   => $title,
            'company' => (new Company($db->pdo()))->findById($companyId) ?? $context->company(),
            'columns' => ['ID', 'Invoice No', 'Amount', 'Status', 'Issue Date', 'Due Date'],
            'rows'    => array_map(static fn(array $r): array => [
                (string) $r['invoice_id'],
                (string) ($r['invoice_no'] ?? ''),
                'N$ ' . number_format((float) $r['amount'], 2),
                (string) $r['status'],
                (string) $r['issue_date'],
                (string) $r['due_date'],
            ], $rows),
        ]);
    }

    private static function companyId(RequestContext $context): ?int
    {
        $cid = (int) ($context->company()['company_id'] ?? 0);
        $sid = (int) ($_SESSION['user']['company_id'] ?? 0);
        return ($cid > 0 && $sid > 0 && $cid === $sid) ? $cid : null;
    }

    private static function filterInvoiceRows(array $rows): array
    {
        $q = trim((string) ($_GET['q'] ?? ''));
        $status = trim((string) ($_GET['status'] ?? ''));
        $from = trim((string) ($_GET['from'] ?? ''));
        $to = trim((string) ($_GET['to'] ?? ''));

        if ($q !== '') {
            $needle = mb_strtolower($q);
            $rows = array_values(array_filter($rows, static function (array $row) use ($needle): bool {
                $hay = mb_strtolower(
                    (string) ($row['invoice_no'] ?? '') . ' ' .
                    (string) ($row['client_name'] ?? '') . ' ' .
                    (string) ($row['status'] ?? '') . ' ' .
                    (string) ($row['issue_date'] ?? '') . ' ' .
                    (string) ($row['due_date'] ?? '')
                );
                return str_contains($hay, $needle);
            }));
        }

        if ($status !== '') {
            $rows = array_values(array_filter($rows, static fn(array $row): bool => (string) ($row['status'] ?? '') === $status));
        }

        if ($from !== '' || $to !== '') {
            $rows = array_values(array_filter($rows, static function (array $row) use ($from, $to): bool {
                $date = (string) ($row['issue_date'] ?? '');
                if ($date === '') {
                    return false;
                }
                if ($from !== '' && $date < $from) {
                    return false;
                }
                if ($to !== '' && $date > $to) {
                    return false;
                }
                return true;
            }));
        }

        return $rows;
    }

    private static function filterEstimateRows(array $rows): array
    {
        $q = trim((string) ($_GET['q'] ?? ''));
        $status = trim((string) ($_GET['status'] ?? ''));
        $from = trim((string) ($_GET['from'] ?? ''));
        $to = trim((string) ($_GET['to'] ?? ''));

        if ($q !== '') {
            $needle = mb_strtolower($q);
            $rows = array_values(array_filter($rows, static function (array $row) use ($needle): bool {
                $hay = mb_strtolower(
                    (string) ($row['estimate_id'] ?? '') . ' ' .
                    (string) ($row['client_name'] ?? '') . ' ' .
                    (string) ($row['status'] ?? '') . ' ' .
                    (string) ($row['estimate_date'] ?? '') . ' ' .
                    (string) ($row['expiry_date'] ?? '')
                );
                return str_contains($hay, $needle);
            }));
        }

        if ($status !== '') {
            $rows = array_values(array_filter($rows, static fn(array $row): bool => (string) ($row['status'] ?? '') === $status));
        }

        if ($from !== '' || $to !== '') {
            $rows = array_values(array_filter($rows, static function (array $row) use ($from, $to): bool {
                $date = (string) ($row['estimate_date'] ?? '');
                if ($date === '') {
                    return false;
                }
                if ($from !== '' && $date < $from) {
                    return false;
                }
                if ($to !== '' && $date > $to) {
                    return false;
                }
                return true;
            }));
        }

        return $rows;
    }

    private static function filterCustomerRows(array $rows): array
    {
        $q = trim((string) ($_GET['q'] ?? ''));
        $status = trim((string) ($_GET['status'] ?? ''));
        $from = trim((string) ($_GET['from'] ?? ''));
        $to = trim((string) ($_GET['to'] ?? ''));

        if ($q !== '') {
            $needle = mb_strtolower($q);
            $rows = array_values(array_filter($rows, static function (array $row) use ($needle): bool {
                $hay = mb_strtolower(
                    (string) ($row['customer_name'] ?? '') . ' ' .
                    (string) ($row['company_name'] ?? '') . ' ' .
                    (string) ($row['email'] ?? '') . ' ' .
                    (string) ($row['phone'] ?? '') . ' ' .
                    (string) ($row['id_number'] ?? '')
                );
                return str_contains($hay, $needle);
            }));
        }

        if ($status !== '' && in_array($status, ['active', 'inactive'], true)) {
            $rows = array_values(array_filter($rows, static function (array $row) use ($status): bool {
                $isActive = ((int) ($row['is_active'] ?? 0) === 1);
                return $status === 'active' ? $isActive : !$isActive;
            }));
        }

        if ($from !== '' || $to !== '') {
            $rows = array_values(array_filter($rows, static function (array $row) use ($from, $to): bool {
                $date = substr((string) ($row['created_at'] ?? ''), 0, 10);
                if ($date === '') {
                    return false;
                }
                if ($from !== '' && $date < $from) {
                    return false;
                }
                if ($to !== '' && $date > $to) {
                    return false;
                }
                return true;
            }));
        }

        return $rows;
    }

    private static function filterCreditRows(array $rows): array
    {
        $q = trim((string) ($_GET['q'] ?? ''));
        $status = trim((string) ($_GET['status'] ?? ''));
        $from = trim((string) ($_GET['from'] ?? ''));
        $to = trim((string) ($_GET['to'] ?? ''));

        if ($q !== '') {
            $needle = mb_strtolower($q);
            $rows = array_values(array_filter($rows, static function (array $row) use ($needle): bool {
                $hay = mb_strtolower(
                    (string) ($row['credit_no'] ?? '') . ' ' .
                    (string) ($row['customer_name'] ?? '') . ' ' .
                    (string) ($row['status'] ?? '') . ' ' .
                    (string) ($row['due_date'] ?? '') . ' ' .
                    (string) ($row['reason'] ?? '')
                );
                return str_contains($hay, $needle);
            }));
        }

        if ($status !== '') {
            $rows = array_values(array_filter($rows, static fn(array $row): bool => mb_strtolower((string) ($row['status'] ?? '')) === mb_strtolower($status)));
        }

        if ($from !== '' || $to !== '') {
            $rows = array_values(array_filter($rows, static function (array $row) use ($from, $to): bool {
                $date = substr((string) ($row['created_at'] ?? ''), 0, 10);
                if ($date === '') {
                    return false;
                }
                if ($from !== '' && $date < $from) {
                    return false;
                }
                if ($to !== '' && $date > $to) {
                    return false;
                }
                return true;
            }));
        }

        return $rows;
    }

    private static function deny(): void
    {
        http_response_code(403);
        echo 'Tenant context is invalid.';
    }
}
