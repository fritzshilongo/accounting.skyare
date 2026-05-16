<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Core\RequestContext;
use App\Core\Database;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Support\SchemaCompat;

class ExportsController extends Controller
{
    private function companyId(RequestContext $context): int
    {
        return (int) ($context->company()['company_id'] ?? 0) ?: 1;
    }

    /* ------------------------------------------------------------------ */
    /*  Sales                                                              */
    /* ------------------------------------------------------------------ */

    public function salesCsv(Request $request, RequestContext $context, Database $db)
    {
        $companyId = $this->companyId($context);
        $pdo = $db->pdo();
        $invoiceNoColumn = SchemaCompat::invoiceNoColumn();
        $invoiceClientNameColumn = SchemaCompat::invoiceClientNameColumn();
        $invoiceAmountSql = SchemaCompat::invoiceAmountSql();
        $issueDateColumn = SchemaCompat::firstExisting('invoices', ['issue_date', 'created_at'], null);
        $statusExpr = SchemaCompat::hasColumn('invoices', 'status') ? 'status' : "''";

        try {
            $stmt = $pdo->prepare('SELECT '
                . ($invoiceNoColumn !== null ? $invoiceNoColumn . ' AS invoice_no' : "'' AS invoice_no") . ', '
                . ($issueDateColumn !== null ? $issueDateColumn . ' AS issue_date' : 'NULL AS issue_date') . ', '
                . ($invoiceClientNameColumn !== null ? $invoiceClientNameColumn . ' AS client_name' : "'' AS client_name") . ', '
                . $invoiceAmountSql . ' AS amount, '
                . $statusExpr . ' AS status '
                . 'FROM invoices WHERE company_id = ? '
                . ($issueDateColumn !== null ? ('ORDER BY ' . $issueDateColumn . ' DESC') : 'ORDER BY invoice_id DESC'));
            $stmt->execute([$companyId]);
            $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];
        } catch (\Throwable $e) {
            $rows = [];
        }

        $data = [['Invoice #', 'Date', 'Client', 'Amount', 'Status']];
        foreach ($rows as $r) {
            $data[] = [$r['invoice_no'], $r['issue_date'], $r['client_name'], $r['amount'], $r['status']];
        }

        return $this->outputCsv('sales.csv', $data);
    }

    public function salesPdf(Request $request, RequestContext $context, Database $db)
    {
        $companyId = $this->companyId($context);
        $pdo = $db->pdo();
        $invoiceNoColumn = SchemaCompat::invoiceNoColumn();
        $invoiceClientNameColumn = SchemaCompat::invoiceClientNameColumn();
        $invoiceAmountSql = SchemaCompat::invoiceAmountSql();
        $issueDateColumn = SchemaCompat::firstExisting('invoices', ['issue_date', 'created_at'], null);
        $statusExpr = SchemaCompat::hasColumn('invoices', 'status') ? 'status' : "''";

        try {
            $stmt = $pdo->prepare('SELECT '
                . ($invoiceNoColumn !== null ? $invoiceNoColumn . ' AS invoice_no' : "'' AS invoice_no") . ', '
                . ($issueDateColumn !== null ? $issueDateColumn . ' AS issue_date' : 'NULL AS issue_date') . ', '
                . ($invoiceClientNameColumn !== null ? $invoiceClientNameColumn . ' AS client_name' : "'' AS client_name") . ', '
                . $invoiceAmountSql . ' AS amount, '
                . $statusExpr . ' AS status '
                . 'FROM invoices WHERE company_id = ? '
                . ($issueDateColumn !== null ? ('ORDER BY ' . $issueDateColumn . ' DESC') : 'ORDER BY invoice_id DESC'));
            $stmt->execute([$companyId]);
            $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];
        } catch (\Throwable $e) {
            $rows = [];
        }

        return $this->outputPdfTable('sales.pdf', 'Sales Report', $context->company(), ['Invoice #', 'Date', 'Client', 'Amount', 'Status'], $rows);
    }

    /* ------------------------------------------------------------------ */
    /*  General Ledger                                                     */
    /* ------------------------------------------------------------------ */

    public function generalLedgerCsv(Request $request, RequestContext $context, Database $db)
    {
        $companyId = $this->companyId($context);
        $pdo = $db->pdo();

        $accountCol = SchemaCompat::firstExisting('journal_entries', ['account_code', 'account_name'], 'account_code') ?? 'account_code';
        $descriptionCol = SchemaCompat::firstExisting('journal_entries', ['description', 'memo'], 'description') ?? 'description';
        $debitCol = SchemaCompat::firstExisting('journal_entries', ['debit_amount', 'debit'], 'debit_amount') ?? 'debit_amount';
        $creditCol = SchemaCompat::firstExisting('journal_entries', ['credit_amount', 'credit'], 'credit_amount') ?? 'credit_amount';
        $orderCol = SchemaCompat::firstExisting('journal_entries', ['date', 'entry_date', 'created_at'], null);

        try {
            $sql = 'SELECT '
                . $accountCol . ' AS account_code, '
                . $descriptionCol . ' AS description, '
                . $debitCol . ' AS debit_amount, '
                . $creditCol . ' AS credit_amount '
                . 'FROM journal_entries WHERE company_id = ?';

            if ($orderCol !== null) {
                $sql .= ' ORDER BY ' . $orderCol . ' DESC';
            }

            $stmt = $pdo->prepare($sql);
            $stmt->execute([$companyId]);
            $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];
        } catch (\Throwable $e) {
            $rows = [];
        }

        $data = [['Account', 'Description', 'Debit', 'Credit']];
        foreach ($rows as $r) {
            $data[] = [$r['account_code'], $r['description'], $r['debit_amount'], $r['credit_amount']];
        }

        return $this->outputCsv('general-ledger.csv', $data);
    }

    public function generalLedgerPdf(Request $request, RequestContext $context, Database $db)
    {
        $companyId = $this->companyId($context);
        $pdo = $db->pdo();

        $accountCol = SchemaCompat::firstExisting('journal_entries', ['account_code', 'account_name'], 'account_code') ?? 'account_code';
        $descriptionCol = SchemaCompat::firstExisting('journal_entries', ['description', 'memo'], 'description') ?? 'description';
        $debitCol = SchemaCompat::firstExisting('journal_entries', ['debit_amount', 'debit'], 'debit_amount') ?? 'debit_amount';
        $creditCol = SchemaCompat::firstExisting('journal_entries', ['credit_amount', 'credit'], 'credit_amount') ?? 'credit_amount';
        $orderCol = SchemaCompat::firstExisting('journal_entries', ['date', 'entry_date', 'created_at'], null);

        try {
            $sql = 'SELECT '
                . $accountCol . ' AS account_code, '
                . $descriptionCol . ' AS description, '
                . $debitCol . ' AS debit_amount, '
                . $creditCol . ' AS credit_amount '
                . 'FROM journal_entries WHERE company_id = ?';

            if ($orderCol !== null) {
                $sql .= ' ORDER BY ' . $orderCol . ' DESC';
            }

            $stmt = $pdo->prepare($sql);
            $stmt->execute([$companyId]);
            $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];
        } catch (\Throwable $e) {
            $rows = [];
        }

        return $this->outputPdfTable('general-ledger.pdf', 'General Ledger', $context->company(), ['Account', 'Description', 'Debit', 'Credit'], $rows);
    }

    /* ------------------------------------------------------------------ */
    /*  Credits                                                            */
    /* ------------------------------------------------------------------ */

    public function creditsCsv(Request $request, RequestContext $context, Database $db)
    {
        $companyId = $this->companyId($context);
        $pdo = $db->pdo();

        $creditNoExpr = SchemaCompat::hasColumn('credits', 'credit_no') ? 'c.credit_no' : "CAST(c.credit_id AS CHAR)";
        $customerExpr = SchemaCompat::hasColumn('credits', 'customer_name') ? 'c.customer_name' : "''";
        $amountExpr = SchemaCompat::hasColumn('credits', 'amount') ? 'c.amount' : (SchemaCompat::hasColumn('credits', 'total_amount') ? 'c.total_amount' : '0');
        $statusExpr = SchemaCompat::hasColumn('credits', 'status') ? 'c.status' : "'ACTIVE'";
        $issueDateExpr = SchemaCompat::firstExisting('credits', ['issue_date', 'created_at'], null);
        $hasCustomerId = SchemaCompat::hasColumn('credits', 'customer_id');
        $hasIdNumber = SchemaCompat::hasColumn('customers', 'id_number');
        $idNumberExpr = ($hasCustomerId && $hasIdNumber) ? 'cust.id_number' : "''";
        $joinExpr = $hasCustomerId ? ' LEFT JOIN customers cust ON cust.customer_id = c.customer_id AND cust.company_id = c.company_id' : '';

        try {
            $stmt = $pdo->prepare(
                'SELECT ' . $creditNoExpr . ' AS credit_no, '
                . $customerExpr . ' AS customer_name, '
                . $idNumberExpr . ' AS id_number, '
                . $amountExpr . ' AS amount, '
                . $statusExpr . ' AS status, '
                . ($issueDateExpr !== null ? 'c.' . $issueDateExpr : 'NULL') . ' AS issue_date '
                . 'FROM credits c' . $joinExpr . ' WHERE c.company_id = ? '
                . ($issueDateExpr !== null ? ('ORDER BY c.' . $issueDateExpr . ' DESC') : 'ORDER BY c.credit_id DESC')
            );
            $stmt->execute([$companyId]);
            $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];
        } catch (\Throwable $e) {
            $rows = [];
        }

        $data = [['Credit #', 'Client', 'ID Number', 'Amount', 'Status', 'Date']];
        foreach ($rows as $r) {
            $data[] = [$r['credit_no'], $r['customer_name'], $r['id_number'] ?? '', $r['amount'], $r['status'], $r['issue_date']];
        }

        return $this->outputCsv('credits.csv', $data);
    }

    public function creditsPdf(Request $request, RequestContext $context, Database $db)
    {
        $companyId = $this->companyId($context);
        $pdo = $db->pdo();

        $creditNoExpr = SchemaCompat::hasColumn('credits', 'credit_no') ? 'c.credit_no' : "CAST(c.credit_id AS CHAR)";
        $customerExpr = SchemaCompat::hasColumn('credits', 'customer_name') ? 'c.customer_name' : "''";
        $amountExpr = SchemaCompat::hasColumn('credits', 'amount') ? 'c.amount' : (SchemaCompat::hasColumn('credits', 'total_amount') ? 'c.total_amount' : '0');
        $statusExpr = SchemaCompat::hasColumn('credits', 'status') ? 'c.status' : "'ACTIVE'";
        $issueDateExpr = SchemaCompat::firstExisting('credits', ['issue_date', 'created_at'], null);
        $hasCustomerId = SchemaCompat::hasColumn('credits', 'customer_id');
        $hasIdNumber = SchemaCompat::hasColumn('customers', 'id_number');
        $idNumberExpr = ($hasCustomerId && $hasIdNumber) ? 'cust.id_number' : "''";
        $joinExpr = $hasCustomerId ? ' LEFT JOIN customers cust ON cust.customer_id = c.customer_id AND cust.company_id = c.company_id' : '';

        try {
            $stmt = $pdo->prepare(
                'SELECT ' . $creditNoExpr . ' AS credit_no, '
                . $customerExpr . ' AS customer_name, '
                . $idNumberExpr . ' AS id_number, '
                . $amountExpr . ' AS amount, '
                . $statusExpr . ' AS status, '
                . ($issueDateExpr !== null ? 'c.' . $issueDateExpr : 'NULL') . ' AS issue_date '
                . 'FROM credits c' . $joinExpr . ' WHERE c.company_id = ? '
                . ($issueDateExpr !== null ? ('ORDER BY c.' . $issueDateExpr . ' DESC') : 'ORDER BY c.credit_id DESC')
            );
            $stmt->execute([$companyId]);
            $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];
        } catch (\Throwable $e) {
            $rows = [];
        }

        $tableRows = array_map(static fn ($r) => [$r['credit_no'], $r['customer_name'], $r['id_number'] ?? '', $r['amount'], $r['status'], $r['issue_date']], $rows);
        return $this->outputPdfTable('credits.pdf', 'Credits Report', $context->company(), ['Credit #', 'Client', 'ID Number', 'Amount', 'Status', 'Date'], $tableRows);
    }

    /* ------------------------------------------------------------------ */
    /*  Inventory                                                          */
    /* ------------------------------------------------------------------ */

    public function inventoryCsv(Request $request, RequestContext $context, Database $db)
    {
        $companyId = $this->companyId($context);
        $pdo = $db->pdo();
        $nameColumn = SchemaCompat::productNameColumn();
        $priceColumn = SchemaCompat::productPriceColumn();
        $skuColumn = SchemaCompat::productSkuColumn();
        $stockQtyColumn = SchemaCompat::productStockQtyColumn() ?? '0';

        try {
            $stmt = $pdo->prepare('SELECT ' . $nameColumn . ' AS name, '
                . ($skuColumn !== null ? $skuColumn . ' AS sku' : "'' AS sku") . ', '
                . $stockQtyColumn . ' AS stock_qty, '
                . $priceColumn . ' AS price FROM products WHERE company_id = ? ORDER BY ' . $nameColumn);
            $stmt->execute([$companyId]);
            $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];
        } catch (\Throwable $e) {
            $rows = [];
        }

        $data = [['Product', 'SKU', 'Quantity', 'Unit Cost', 'Total Value']];
        foreach ($rows as $r) {
            $data[] = [$r['name'], $r['sku'] ?? '', $r['stock_qty'], $r['price'], round((float) $r['stock_qty'] * (float) $r['price'], 2)];
        }

        return $this->outputCsv('inventory.csv', $data);
    }

    public function inventoryPdf(Request $request, RequestContext $context, Database $db)
    {
        $companyId = $this->companyId($context);
        $pdo = $db->pdo();
        $nameColumn = SchemaCompat::productNameColumn();
        $priceColumn = SchemaCompat::productPriceColumn();
        $skuColumn = SchemaCompat::productSkuColumn();
        $stockQtyColumn = SchemaCompat::productStockQtyColumn() ?? '0';

        try {
            $stmt = $pdo->prepare('SELECT ' . $nameColumn . ' AS name, '
                . ($skuColumn !== null ? $skuColumn . ' AS sku' : "'' AS sku") . ', '
                . $stockQtyColumn . ' AS stock_qty, '
                . $priceColumn . ' AS price FROM products WHERE company_id = ? ORDER BY ' . $nameColumn);
            $stmt->execute([$companyId]);
            $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];
        } catch (\Throwable $e) {
            $rows = [];
        }

        // Compute total value for each row
        foreach ($rows as &$r) {
            $r['total_value'] = round((float) $r['stock_qty'] * (float) $r['price'], 2);
        }
        unset($r);

        return $this->outputPdfTable('inventory.pdf', 'Inventory Report', $context->company(), ['Product', 'SKU', 'Quantity', 'Unit Cost', 'Total Value'], array_map(fn ($r) => [$r['name'], $r['sku'] ?? '', $r['stock_qty'], $r['price'], $r['total_value']], $rows));
    }

    /* ------------------------------------------------------------------ */
    /*  Inventory Audit                                                    */
    /* ------------------------------------------------------------------ */

    public function inventoryAuditCsv(Request $request, RequestContext $context, Database $db)
    {
        $companyId = $this->companyId($context);
        $pdo = $db->pdo();

        $productNameCol = SchemaCompat::productNameColumn();

        try {
            $stmt = $pdo->prepare(
                'SELECT im.created_at, p.' . $productNameCol . ' AS product, im.movement_type, im.quantity, im.created_by
                 FROM inventory_movements im
                 LEFT JOIN products p ON p.product_id = im.product_id
                 WHERE im.company_id = ?
                 ORDER BY im.created_at DESC'
            );
            $stmt->execute([$companyId]);
            $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];
        } catch (\Throwable $e) {
            $rows = [];
        }

        $data = [['Date', 'Product', 'Type', 'Quantity', 'User']];
        foreach ($rows as $r) {
            $data[] = [$r['created_at'], $r['product'] ?? '', $r['movement_type'], $r['quantity'], $r['created_by'] ?? ''];
        }

        return $this->outputCsv('inventory-audit.csv', $data);
    }

    public function inventoryAuditPdf(Request $request, RequestContext $context, Database $db)
    {
        $companyId = $this->companyId($context);
        $pdo = $db->pdo();

        $productNameCol = SchemaCompat::productNameColumn();

        try {
            $stmt = $pdo->prepare(
                'SELECT im.created_at, p.' . $productNameCol . ' AS product, im.movement_type, im.quantity, im.created_by
                 FROM inventory_movements im
                 LEFT JOIN products p ON p.product_id = im.product_id
                 WHERE im.company_id = ?
                 ORDER BY im.created_at DESC'
            );
            $stmt->execute([$companyId]);
            $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];
        } catch (\Throwable $e) {
            $rows = [];
        }

        return $this->outputPdfTable('inventory-audit.pdf', 'Inventory Audit Report', $context->company(), ['Date', 'Product', 'Type', 'Quantity', 'User'], array_map(fn ($r) => [$r['created_at'], $r['product'] ?? '', $r['movement_type'], $r['quantity'], $r['created_by'] ?? ''], $rows));
    }

    /* ------------------------------------------------------------------ */
    /*  Report: Sales                                                      */
    /* ------------------------------------------------------------------ */

    public function reportSalesCsv(Request $request, RequestContext $context, Database $db)
    {
        $companyId = $this->companyId($context);
        $pdo = $db->pdo();
        $from = $request->query('from', date('Y-01-01'));
        $to = $request->query('to', date('Y-m-d'));

        $invoiceNoColumn = SchemaCompat::invoiceNoColumn();
        $invoiceClientNameColumn = SchemaCompat::invoiceClientNameColumn();
        $invoiceAmountSql = SchemaCompat::invoiceAmountSql();
        $issueDateColumn = SchemaCompat::firstExisting('invoices', ['issue_date', 'created_at'], null);
        $statusExpr = SchemaCompat::hasColumn('invoices', 'status') ? 'status' : "''";

        try {
            $stmt = $pdo->prepare(
                'SELECT '
                . ($invoiceNoColumn !== null ? $invoiceNoColumn : "''") . ' AS invoice_no, '
                . ($issueDateColumn !== null ? $issueDateColumn : 'NULL') . ' AS issue_date, '
                . ($invoiceClientNameColumn !== null ? $invoiceClientNameColumn : "''") . ' AS client_name, '
                . $invoiceAmountSql . ' AS total, '
                . $statusExpr . ' AS status '
                . 'FROM invoices WHERE company_id = ? '
                . ($issueDateColumn !== null ? ('AND ' . $issueDateColumn . ' BETWEEN ? AND ? ORDER BY ' . $issueDateColumn . ' DESC') : 'ORDER BY invoice_id DESC')
            );

            $params = [$companyId];
            if ($issueDateColumn !== null) {
                $params[] = $from;
                $params[] = $to;
            }

            $stmt->execute($params);
            $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];
        } catch (\Throwable $e) {
            $rows = [];
        }

        $data = [['Invoice #', 'Date', 'Client', 'Amount', 'Status']];
        foreach ($rows as $r) {
            $data[] = [$r['invoice_no'], $r['issue_date'], $r['client_name'], $r['total'], $r['status']];
        }

        return $this->outputCsv("sales-report-{$from}-{$to}.csv", $data);
    }

    public function reportSalesPdf(Request $request, RequestContext $context, Database $db)
    {
        $companyId = $this->companyId($context);
        $pdo = $db->pdo();
        $from = $request->query('from', date('Y-01-01'));
        $to = $request->query('to', date('Y-m-d'));

        $invoiceNoColumn = SchemaCompat::invoiceNoColumn();
        $invoiceClientNameColumn = SchemaCompat::invoiceClientNameColumn();
        $invoiceAmountSql = SchemaCompat::invoiceAmountSql();
        $issueDateColumn = SchemaCompat::firstExisting('invoices', ['issue_date', 'created_at'], null);
        $statusExpr = SchemaCompat::hasColumn('invoices', 'status') ? 'status' : "''";

        try {
            $stmt = $pdo->prepare(
                'SELECT '
                . ($invoiceNoColumn !== null ? $invoiceNoColumn : "''") . ' AS invoice_no, '
                . ($issueDateColumn !== null ? $issueDateColumn : 'NULL') . ' AS issue_date, '
                . ($invoiceClientNameColumn !== null ? $invoiceClientNameColumn : "''") . ' AS client_name, '
                . $invoiceAmountSql . ' AS total, '
                . $statusExpr . ' AS status '
                . 'FROM invoices WHERE company_id = ? '
                . ($issueDateColumn !== null ? ('AND ' . $issueDateColumn . ' BETWEEN ? AND ? ORDER BY ' . $issueDateColumn . ' DESC') : 'ORDER BY invoice_id DESC')
            );

            $params = [$companyId];
            if ($issueDateColumn !== null) {
                $params[] = $from;
                $params[] = $to;
            }

            $stmt->execute($params);
            $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];
        } catch (\Throwable $e) {
            $rows = [];
        }

        return $this->outputPdfTable("sales-report-{$from}-{$to}.pdf", "Sales Report ({$from} to {$to})", $context->company(), ['Invoice #', 'Date', 'Client', 'Amount', 'Status'], $rows);
    }

    /* ------------------------------------------------------------------ */
    /*  Report: Revenue                                                    */
    /* ------------------------------------------------------------------ */

    public function reportRevenueCsv(Request $request, RequestContext $context, Database $db)
    {
        $companyId = $this->companyId($context);
        $pdo = $db->pdo();
        $from = $request->query('from', date('Y-01-01'));
        $to = $request->query('to', date('Y-m-d'));

        // Monthly revenue
        $stmt = $pdo->prepare(
            "SELECT DATE_FORMAT(p.payment_date, '%Y-%m') AS month,
                    COALESCE(SUM(p.amount), 0) AS revenue
             FROM payments p
             INNER JOIN invoices i ON i.invoice_id = p.invoice_id
             WHERE i.company_id = ? AND p.payment_date BETWEEN ? AND ?
             GROUP BY month ORDER BY month"
        );
        $stmt->execute([$companyId, $from, $to]);
        $revRows = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        // Monthly expenses
        $stmt = $pdo->prepare(
            "SELECT DATE_FORMAT(date, '%Y-%m') AS month,
                    COALESCE(SUM(amount), 0) AS expenses
             FROM expenses
             WHERE company_id = ? AND date BETWEEN ? AND ?
             GROUP BY month ORDER BY month"
        );
        $stmt->execute([$companyId, $from, $to]);
        $expRows = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        $revMap = array_column($revRows, 'revenue', 'month');
        $expMap = array_column($expRows, 'expenses', 'month');
        $months = array_unique(array_merge(array_keys($revMap), array_keys($expMap)));
        sort($months);

        $data = [['Month', 'Revenue', 'Expenses', 'Net']];
        foreach ($months as $m) {
            $r = (float) ($revMap[$m] ?? 0);
            $e = (float) ($expMap[$m] ?? 0);
            $data[] = [$m, number_format($r, 2), number_format($e, 2), number_format($r - $e, 2)];
        }

        return $this->outputCsv("revenue-report-{$from}-{$to}.csv", $data);
    }

    public function reportRevenuePdf(Request $request, RequestContext $context, Database $db)
    {
        $companyId = $this->companyId($context);
        $pdo = $db->pdo();
        $from = $request->query('from', date('Y-01-01'));
        $to = $request->query('to', date('Y-m-d'));

        $stmt = $pdo->prepare(
            "SELECT DATE_FORMAT(p.payment_date, '%Y-%m') AS month,
                    COALESCE(SUM(p.amount), 0) AS revenue
             FROM payments p
             INNER JOIN invoices i ON i.invoice_id = p.invoice_id
             WHERE i.company_id = ? AND p.payment_date BETWEEN ? AND ?
             GROUP BY month ORDER BY month"
        );
        $stmt->execute([$companyId, $from, $to]);
        $revRows = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        $stmt = $pdo->prepare(
            "SELECT DATE_FORMAT(date, '%Y-%m') AS month,
                    COALESCE(SUM(amount), 0) AS expenses
             FROM expenses
             WHERE company_id = ? AND date BETWEEN ? AND ?
             GROUP BY month ORDER BY month"
        );
        $stmt->execute([$companyId, $from, $to]);
        $expRows = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        $revMap = array_column($revRows, 'revenue', 'month');
        $expMap = array_column($expRows, 'expenses', 'month');
        $months = array_unique(array_merge(array_keys($revMap), array_keys($expMap)));
        sort($months);

        $rows = [];
        foreach ($months as $m) {
            $r = (float) ($revMap[$m] ?? 0);
            $e = (float) ($expMap[$m] ?? 0);
            $rows[] = [$m, number_format($r, 2), number_format($e, 2), number_format($r - $e, 2)];
        }

        return $this->outputPdfTable("revenue-report-{$from}-{$to}.pdf", "Revenue Report ({$from} to {$to})", $context->company(), ['Month', 'Revenue', 'Expenses', 'Net'], $rows);
    }

    /* ------------------------------------------------------------------ */
    /*  Report: Expenses                                                   */
    /* ------------------------------------------------------------------ */

    public function reportExpensesCsv(Request $request, RequestContext $context, Database $db)
    {
        $companyId = $this->companyId($context);
        $pdo = $db->pdo();
        $from = $request->query('from', date('Y-01-01'));
        $to = $request->query('to', date('Y-m-d'));

        $stmt = $pdo->prepare('SELECT date, category, description, amount FROM expenses WHERE company_id = ? AND date BETWEEN ? AND ? ORDER BY date DESC');
        $stmt->execute([$companyId, $from, $to]);
        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        $data = [['Date', 'Category', 'Description', 'Amount']];
        foreach ($rows as $r) {
            $data[] = [$r['date'], $r['category'], $r['description'], $r['amount']];
        }

        return $this->outputCsv("expenses-report-{$from}-{$to}.csv", $data);
    }

    public function reportExpensesPdf(Request $request, RequestContext $context, Database $db)
    {
        $companyId = $this->companyId($context);
        $pdo = $db->pdo();
        $from = $request->query('from', date('Y-01-01'));
        $to = $request->query('to', date('Y-m-d'));

        $stmt = $pdo->prepare('SELECT date, category, description, amount FROM expenses WHERE company_id = ? AND date BETWEEN ? AND ? ORDER BY date DESC');
        $stmt->execute([$companyId, $from, $to]);
        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        return $this->outputPdfTable("expenses-report-{$from}-{$to}.pdf", "Expense Report ({$from} to {$to})", $context->company(), ['Date', 'Category', 'Description', 'Amount'], $rows);
    }

    /* ------------------------------------------------------------------ */
    /*  Report: Balance Sheet                                              */
    /* ------------------------------------------------------------------ */

    public function reportBalancePdf(Request $request, RequestContext $context, Database $db)
    {
        $companyId = $this->companyId($context);
        $pdo = $db->pdo();
        $asOf = $request->query('as_of', date('Y-m-d'));
        $company = $context->company();

        try {
            $stmt = $pdo->prepare('SELECT COALESCE(SUM(p.amount), 0) FROM payments p INNER JOIN invoices i ON i.invoice_id = p.invoice_id WHERE i.company_id = ? AND p.payment_date <= ?');
            $stmt->execute([$companyId, $asOf]);
            $cash = (float) $stmt->fetchColumn();

            $stmt = $pdo->prepare('SELECT COALESCE(SUM(' . SchemaCompat::invoiceAmountSql() . '), 0) FROM invoices WHERE company_id = ? AND issue_date <= ?');
            $stmt->execute([$companyId, $asOf]);
            $invoiced = (float) $stmt->fetchColumn();
            $ar = max(0, $invoiced - $cash);

            $stmt = $pdo->prepare('SELECT COALESCE(SUM(' . (SchemaCompat::productStockQtyColumn() ?? '0') . ' * ' . SchemaCompat::productPriceColumn() . '), 0) FROM products WHERE company_id = ?');
            $stmt->execute([$companyId]);
            $inv = (float) $stmt->fetchColumn();

            $stmt = $pdo->prepare('SELECT COALESCE(SUM(amount), 0) FROM expenses WHERE company_id = ? AND date <= ?');
            $stmt->execute([$companyId, $asOf]);
            $exp = (float) $stmt->fetchColumn();
        } catch (\Throwable $e) {
            $cash = $ar = $inv = $exp = 0;
        }

        $totalAssets = $cash + $ar + $inv;
        $equity = $cash - $exp;

        $rows = [
            ['ASSETS', '', ''],
            ['Cash Received', '', number_format($cash, 2)],
            ['Accounts Receivable', '', number_format($ar, 2)],
            ['Inventory', '', number_format($inv, 2)],
            ['Total Assets', '', number_format($totalAssets, 2)],
            ['', '', ''],
            ['LIABILITIES', '', ''],
            ['Operating Expenses', '', number_format($exp, 2)],
            ['Total Liabilities', '', number_format($exp, 2)],
            ['', '', ''],
            ['EQUITY', '', ''],
            ['Retained Earnings', '', number_format($equity, 2)],
            ['Total Equity', '', number_format($equity, 2)],
        ];

        return $this->outputPdfTable("balance-sheet-{$asOf}.pdf", "Balance Sheet as of {$asOf}", $company, ['Account', '', 'Amount'], $rows);
    }

    /* ------------------------------------------------------------------ */
    /*  Financial Statement                                                */
    /* ------------------------------------------------------------------ */

    public function financialStatementCsv(Request $request, RequestContext $context, Database $db)
    {
        $companyId = $this->companyId($context);
        $pdo = $db->pdo();

        try {
            $stmt = $pdo->prepare('SELECT COALESCE(SUM(' . SchemaCompat::invoiceAmountSql() . '), 0) FROM invoices WHERE company_id = ?');
            $stmt->execute([$companyId]);
            $revenue = (float) $stmt->fetchColumn();

            $stmt = $pdo->prepare('SELECT COALESCE(SUM(amount), 0) FROM expenses WHERE company_id = ?');
            $stmt->execute([$companyId]);
            $expenses = (float) $stmt->fetchColumn();
        } catch (\Throwable $e) {
            $revenue = $expenses = 0;
        }

        $net = $revenue - $expenses;
        $data = [
            ['Line Item', 'Amount'],
            ['Gross Revenue', number_format($revenue, 2)],
            ['Operating Expenses', number_format($expenses, 2)],
            ['Net Position', number_format($net, 2)],
        ];

        return $this->outputCsv('financial-statement.csv', $data);
    }

    public function financialStatementPdf(Request $request, RequestContext $context, Database $db)
    {
        $companyId = $this->companyId($context);
        $pdo = $db->pdo();
        $company = $context->company();

        try {
            $stmt = $pdo->prepare('SELECT COALESCE(SUM(' . SchemaCompat::invoiceAmountSql() . '), 0) FROM invoices WHERE company_id = ?');
            $stmt->execute([$companyId]);
            $revenue = (float) $stmt->fetchColumn();

            $stmt = $pdo->prepare('SELECT COALESCE(SUM(amount), 0) FROM expenses WHERE company_id = ?');
            $stmt->execute([$companyId]);
            $expenses = (float) $stmt->fetchColumn();
        } catch (\Throwable $e) {
            $revenue = $expenses = 0;
        }

        $net = $revenue - $expenses;
        $rows = [
            ['Gross Revenue', number_format($revenue, 2)],
            ['Operating Expenses', number_format($expenses, 2)],
            ['', ''],
            ['Net Position', number_format($net, 2)],
        ];

        return $this->outputPdfTable('financial-statement.pdf', 'Financial Statement', $company, ['Line Item', 'Amount'], $rows);
    }

    /* ------------------------------------------------------------------ */
    /*  Helpers                                                            */
    /* ------------------------------------------------------------------ */

    private function outputCsv(string $filename, array $data)
    {
        $fp = fopen('php://temp', 'r+');
        foreach ($data as $row) {
            fputcsv($fp, $row);
        }
        rewind($fp);
        $content = stream_get_contents($fp) ?: '';
        fclose($fp);

        return response($content, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            'Pragma' => 'no-cache',
            'Expires' => '0',
        ]);
    }

    private function outputPdfTable(string $filename, string $title, ?array $company, array $headers, array $rows)
    {
        $companyName = $company['company_name'] ?? $company['name'] ?? 'Company';

        $html = '<html><head><style>body{font-family:sans-serif;font-size:12px}h1{font-size:18px}table{width:100%;border-collapse:collapse;margin-top:10px}th,td{border:1px solid #ccc;padding:6px 8px;text-align:left}th{background:#f5f5f5}</style></head><body>';
        $html .= '<h1>' . htmlspecialchars($companyName, ENT_QUOTES, 'UTF-8') . ' &mdash; ' . htmlspecialchars($title, ENT_QUOTES, 'UTF-8') . '</h1>';
        $html .= '<p>Generated: ' . date('Y-m-d H:i') . '</p>';
        $html .= '<table><thead><tr>';
        foreach ($headers as $h) {
            $html .= '<th>' . htmlspecialchars((string) $h, ENT_QUOTES, 'UTF-8') . '</th>';
        }
        $html .= '</tr></thead><tbody>';
        foreach ($rows as $row) {
            $html .= '<tr>';
            $values = is_array($row) && !array_is_list($row) ? array_values($row) : (array) $row;
            foreach ($values as $cell) {
                $html .= '<td>' . htmlspecialchars((string) ($cell ?? ''), ENT_QUOTES, 'UTF-8') . '</td>';
            }
            $html .= '</tr>';
        }
        if (empty($rows)) {
            $html .= '<tr><td colspan="' . count($headers) . '" style="text-align:center">No data available</td></tr>';
        }
        $html .= '</tbody></table></body></html>';

        $pdf = Pdf::loadHTML($html)->setPaper('a4', 'landscape');
        return $pdf->download($filename);
    }
}
