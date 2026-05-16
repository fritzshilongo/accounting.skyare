<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\AuditLogger;
use App\Core\Database;
use App\Core\RequestContext;
use App\Core\View;
use App\Models\Company;
use App\Models\Credit;
use App\Models\Customer;
use App\Models\Estimate;
use App\Models\ExchangeRate;
use App\Models\InventoryMovement;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\License;
use App\Models\Product;

final class OperationsController
{
    public static function sales(Database $db, RequestContext $context): void
    {
        $companyId = self::companyId($context);
        if ($companyId === null) { self::deny(); return; }

        $stmt = $db->pdo()->prepare(
            'SELECT
                COALESCE(SUM(CASE WHEN status IN (\'paid\', \'issued\', \'overdue\') THEN amount ELSE 0 END), 0) AS total_sales,
                COALESCE(SUM(CASE WHEN status = \'paid\' THEN amount ELSE 0 END), 0) AS paid_sales,
                COALESCE(SUM(CASE WHEN status IN (\'issued\', \'overdue\') THEN amount ELSE 0 END), 0) AS outstanding_sales,
                COUNT(*) AS total_invoices
             FROM invoices
             WHERE company_id = :company_id'
        );
        $stmt->execute(['company_id' => $companyId]);
        $summary = $stmt->fetch() ?: [];

        $latest = $db->pdo()->prepare('SELECT invoice_id, client_name, amount, status, issue_date, due_date FROM invoices WHERE company_id = :company_id ORDER BY invoice_id DESC LIMIT 30');
        $latest->execute(['company_id' => $companyId]);

        View::render('ops/sales', ['company' => $context->company(), 'summary' => $summary, 'rows' => $latest->fetchAll() ?: []]);
    }

    public static function financialStatement(Database $db, RequestContext $context): void
    {
        $companyId = self::companyId($context);
        if ($companyId === null) { self::deny(); return; }

        $fromDate = trim((string) ($_GET['from'] ?? date('Y-01-01')));
        $toDate   = trim((string) ($_GET['to'] ?? date('Y-m-d')));

        // Income: paid invoices in period
        $incomeStmt = $db->pdo()->prepare(
            'SELECT invoice_id, invoice_no, client_name, amount, issue_date, status
             FROM invoices
             WHERE company_id = :company_id AND issue_date BETWEEN :from AND :to
             ORDER BY issue_date'
        );
        $incomeStmt->execute(['company_id' => $companyId, 'from' => $fromDate, 'to' => $toDate]);
        $incomeRows = $incomeStmt->fetchAll() ?: [];

        $totalIncome  = 0.0;
        $totalPaid    = 0.0;
        foreach ($incomeRows as $r) {
            $totalIncome += (float) $r['amount'];
            if ($r['status'] === 'paid') { $totalPaid += (float) $r['amount']; }
        }

        // Expenses in period, including manual journal postings to expense accounts.
        $expStmt = $db->pdo()->prepare(
            'SELECT expense_id, category, description, amount, expense_date
             FROM expenses
             WHERE company_id = :company_id AND expense_date BETWEEN :from AND :to
             ORDER BY expense_date'
        );
        $expStmt->execute(['company_id' => $companyId, 'from' => $fromDate, 'to' => $toDate]);
        $expenseRows = $expStmt->fetchAll() ?: [];
        $journalExpenseRows = self::journalExpenseRows($db, $companyId, $fromDate, $toDate);
        $expenseRows = array_merge($expenseRows, $journalExpenseRows);
        usort($expenseRows, static function (array $left, array $right): int {
            $dateCompare = strcmp((string) ($left['expense_date'] ?? ''), (string) ($right['expense_date'] ?? ''));
            if ($dateCompare !== 0) {
                return $dateCompare;
            }

            return strcmp((string) ($left['description'] ?? ''), (string) ($right['description'] ?? ''));
        });
        $totalExpenses = array_reduce(
            $expenseRows,
            static fn(float $carry, array $row): float => $carry + (float) ($row['amount'] ?? 0),
            0.0
        );

        $netIncome = $totalPaid - $totalExpenses;

        View::render('ops/financial_statement', [
            'company'       => (new Company($db->pdo()))->findById($companyId) ?? $context->company(),
            'from_date'     => $fromDate,
            'to_date'       => $toDate,
            'income_rows'   => $incomeRows,
            'expense_rows'  => $expenseRows,
            'total_income'  => $totalIncome,
            'total_paid'    => $totalPaid,
            'total_expenses'=> $totalExpenses,
            'net_income'    => $netIncome,
        ]);
    }

    public static function customerStatement(Database $db, RequestContext $context): void
    {
        $companyId = self::companyId($context);
        if ($companyId === null) { self::deny(); return; }

        $selectedId = (int) ($_GET['customer_id'] ?? 0);

        $customerModel = new Customer($db->pdo());
        $customers = $customerModel->listByCompany($companyId);

        $rows = [];
        $totals = ['paid' => 0.0, 'outstanding' => 0.0, 'total' => 0.0];
        $selectedCustomer = null;

        if ($selectedId > 0) {
            $selectedCustomer = $customerModel->findByIdForCompany($selectedId, $companyId);
            if ($selectedCustomer !== null) {
                $stmt = $db->pdo()->prepare(
                    'SELECT invoice_id, invoice_no, amount, status, issue_date, due_date
                     FROM invoices
                     WHERE company_id = :company_id AND customer_id = :customer_id
                     ORDER BY issue_date DESC'
                );
                $stmt->execute(['company_id' => $companyId, 'customer_id' => $selectedId]);
                $rows = $stmt->fetchAll() ?: [];
                foreach ($rows as $r) {
                    $amt = (float) $r['amount'];
                    $totals['total'] += $amt;
                    if ($r['status'] === 'paid') {
                        $totals['paid'] += $amt;
                    } else {
                        $totals['outstanding'] += $amt;
                    }
                }
            }
        }

        View::render('ops/customer_statement', [
            'company'           => $context->company(),
            'customers'         => $customers,
            'selected_id'       => $selectedId,
            'selected_customer' => $selectedCustomer,
            'rows'              => $rows,
            'totals'            => $totals,
        ]);
    }

    public static function estimates(Database $db, RequestContext $context): void
    {
        $companyId = self::companyId($context);
        if ($companyId === null) { self::deny(); return; }

        $model = new Estimate($db->pdo());
        $search = trim((string) ($_GET['q'] ?? ''));
        $statusFilter = trim((string) ($_GET['status'] ?? ''));
        $fromDate = trim((string) ($_GET['from'] ?? ''));
        $toDate = trim((string) ($_GET['to'] ?? ''));
        $rows = $model->listByCompany($companyId);
        if ($search !== '') {
            $needle = mb_strtolower($search);
            $rows = array_values(array_filter($rows, static function (array $row) use ($needle): bool {
                $haystack = mb_strtolower(
                    (string) ($row['estimate_id'] ?? '') . ' ' .
                    (string) ($row['client_name'] ?? '') . ' ' .
                    (string) ($row['status'] ?? '') . ' ' .
                    (string) ($row['estimate_date'] ?? '') . ' ' .
                    (string) ($row['expiry_date'] ?? '')
                );
                return str_contains($haystack, $needle);
            }));
        }

        if ($statusFilter !== '' && in_array($statusFilter, ['draft', 'sent', 'accepted', 'rejected', 'expired'], true)) {
            $rows = array_values(array_filter($rows, static fn(array $row): bool => (string) ($row['status'] ?? '') === $statusFilter));
        }

        if ($fromDate !== '' || $toDate !== '') {
            $rows = array_values(array_filter($rows, static function (array $row) use ($fromDate, $toDate): bool {
                $estimateDate = (string) ($row['estimate_date'] ?? '');
                if ($estimateDate === '') {
                    return false;
                }
                if ($fromDate !== '' && $estimateDate < $fromDate) {
                    return false;
                }
                if ($toDate !== '' && $estimateDate > $toDate) {
                    return false;
                }
                return true;
            }));
        }

        View::render('ops/estimates', [
            'company'   => $context->company(),
            'rows'      => $rows,
            'search'    => $search,
            'status_filter' => $statusFilter,
            'from_date' => $fromDate,
            'to_date' => $toDate,
            'customers' => (new Customer($db->pdo()))->listByCompany($companyId),
            'products'  => (new Product($db->pdo()))->listActiveByCompany($companyId),
            'token'     => \App\Middleware\CsrfMiddleware::token(),
            'errors'    => [],
            'old'       => ['customer_id' => '', 'client_name' => '', 'product_id' => '', 'quantity' => '1', 'amount' => '0.00',
                            'estimate_date' => date('Y-m-d'), 'expiry_date' => date('Y-m-d'), 'status' => 'draft'],
        ]);
    }

    public static function estimatesStore(Database $db, RequestContext $context): void
    {
        $companyId = self::companyId($context); if ($companyId === null) { self::deny(); return; }
        [$customerId, $productId, $quantity, $unitPrice, $lineDescription, $clientName, $amount, $estimateDate, $expiryDate, $status, $errors] = self::estimateInput($db, $companyId);
        $model = new Estimate($db->pdo());
        if ($errors !== []) {
            http_response_code(422);
            View::render('ops/estimates', [
                'company'   => $context->company(),
                'rows'      => $model->listByCompany($companyId),
                'customers' => (new Customer($db->pdo()))->listByCompany($companyId),
                'products'  => (new Product($db->pdo()))->listActiveByCompany($companyId),
                'token'     => \App\Middleware\CsrfMiddleware::token(),
                'errors'    => $errors,
                'old'       => ['customer_id' => $customerId, 'client_name' => $clientName, 'product_id' => $productId, 'quantity' => trim((string) ($_POST['quantity'] ?? '1')), 'amount' => (string) $amount,
                                'product_description' => $lineDescription ?? '', 'estimate_date' => $estimateDate, 'expiry_date' => $expiryDate, 'status' => $status],
            ]);
            return;
        }
        $id = $model->createForCompany($companyId, $customerId, $productId, $quantity, $unitPrice, $clientName, $amount, $estimateDate, $expiryDate, $status, $lineDescription);
        AuditLogger::log($db, $context, 'estimate.create', 'estimate', (string) $id, 'Created estimate for ' . $clientName);
        header('Location: /estimates');
    }

    public static function estimatesEdit(Database $db, RequestContext $context): void
    {
        $companyId = self::companyId($context); if ($companyId === null) { self::deny(); return; }
        $id = (int) ($_GET['estimate_id'] ?? 0);
        $model = new Estimate($db->pdo());
        $row = $model->findByIdForCompany($id, $companyId);
        if ($row === null) { http_response_code(404); echo 'Estimate not found.'; return; }
        $row = self::normalizeEstimateLine($db, $companyId, $row);

        View::render('ops/estimates_edit', [
            'company'   => $context->company(),
            'customers' => (new Customer($db->pdo()))->listByCompany($companyId),
            'products'  => (new Product($db->pdo()))->listActiveByCompany($companyId),
            'row'       => $row,
            'token'     => \App\Middleware\CsrfMiddleware::token(),
            'errors'    => [],
        ]);
    }

    public static function estimatesView(Database $db, RequestContext $context): void
    {
        $companyId = self::companyId($context); if ($companyId === null) { self::deny(); return; }
        $id = (int) ($_GET['estimate_id'] ?? 0);
        $model = new Estimate($db->pdo());
        $row = $model->findByIdForCompany($id, $companyId);
        if ($row === null) { http_response_code(404); echo 'Estimate not found.'; return; }
        $row = self::normalizeEstimateLine($db, $companyId, $row);

        View::render('ops/estimates_view', ['company' => $context->company(), 'row' => $row, 'token' => \App\Middleware\CsrfMiddleware::token()]);
    }

    public static function estimatesPrint(Database $db, RequestContext $context): void
    {
        $companyId = self::companyId($context); if ($companyId === null) { self::deny(); return; }
        $id = (int) ($_GET['estimate_id'] ?? 0);
        $model = new Estimate($db->pdo());
        $row = $model->findByIdForCompany($id, $companyId);
        if ($row === null) { http_response_code(404); echo 'Estimate not found.'; return; }
        $row = self::normalizeEstimateLine($db, $companyId, $row);

        $customerModel = new Customer($db->pdo());
        $customer = null;
        if ((int) ($row['customer_id'] ?? 0) > 0) {
            $customer = $customerModel->findByIdForCompany((int) $row['customer_id'], $companyId);
        }
        if ($customer === null && trim((string) ($row['client_name'] ?? '')) !== '') {
            $customer = $customerModel->findByNameForCompany((string) $row['client_name'], $companyId);
        }

        $company = (new Company($db->pdo()))->findById($companyId) ?: $context->company();

        View::render('ops/estimates_print', ['company' => $company, 'row' => $row, 'customer' => $customer]);
    }

    public static function estimatesUpdate(Database $db, RequestContext $context): void
    {
        $companyId = self::companyId($context); if ($companyId === null) { self::deny(); return; }
        $id = (int) ($_POST['estimate_id'] ?? 0);
        [$customerId, $productId, $quantity, $unitPrice, $lineDescription, $clientName, $amount, $estimateDate, $expiryDate, $status, $errors] = self::estimateInput($db, $companyId);
        $model = new Estimate($db->pdo());
        if ($errors !== []) {
            http_response_code(422);
            View::render('ops/estimates_edit', [
                'company'   => $context->company(),
                'customers' => (new Customer($db->pdo()))->listByCompany($companyId),
                'products'  => (new Product($db->pdo()))->listActiveByCompany($companyId),
                'row'       => ['estimate_id' => $id, 'customer_id' => $customerId, 'client_name' => $clientName,
                                'product_id' => $productId, 'product_name' => '', 'line_description' => $lineDescription, 'product_description' => $lineDescription, 'quantity' => trim((string) ($_POST['quantity'] ?? '')), 'unit_price' => $unitPrice, 'amount' => $amount, 'estimate_date' => $estimateDate,
                                'expiry_date' => $expiryDate, 'status' => $status],
                'token'     => \App\Middleware\CsrfMiddleware::token(),
                'errors'    => $errors,
            ]);
            return;
        }
        $model->updateForCompany($id, $companyId, $customerId, $productId, $quantity, $unitPrice, $clientName, $amount, $estimateDate, $expiryDate, $status, $lineDescription);
        AuditLogger::log($db, $context, 'estimate.update', 'estimate', (string) $id, 'Updated estimate for ' . $clientName);
        header('Location: /estimates/view?estimate_id=' . $id);
    }

    public static function estimatesConvertToInvoice(Database $db, RequestContext $context): void
    {
        $companyId = self::companyId($context); if ($companyId === null) { self::deny(); return; }
        $estimateId = (int) ($_POST['estimate_id'] ?? 0);
        if ($estimateId <= 0) {
            http_response_code(422);
            echo 'Estimate id is required.';
            return;
        }

        $estimateModel = new Estimate($db->pdo());
        $estimate = $estimateModel->findByIdForCompany($estimateId, $companyId);
        if ($estimate === null) {
            http_response_code(404);
            echo 'Estimate not found.';
            return;
        }

        $estimate = self::normalizeEstimateLine($db, $companyId, $estimate);

        if ((string) ($estimate['status'] ?? '') !== 'accepted') {
            http_response_code(422);
            echo 'Only accepted estimates can be converted to invoices.';
            return;
        }

        if ((int) ($estimate['converted_invoice_id'] ?? 0) > 0) {
            header('Location: /invoices/view?invoice_id=' . (int) $estimate['converted_invoice_id']);
            return;
        }

        $productId = (int) ($estimate['product_id'] ?? 0);
        $quantity = (float) ($estimate['quantity'] ?? 0);
        $amount = (float) ($estimate['amount'] ?? 0);
        $clientName = trim((string) ($estimate['client_name'] ?? ''));
        $customerId = (int) ($estimate['customer_id'] ?? 0);
        $issueDate = date('Y-m-d');
        $dueDate = trim((string) ($estimate['expiry_date'] ?? ''));
        if ($dueDate === '' || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $dueDate)) {
            $dueDate = $issueDate;
        }

        $productModel = new Product($db->pdo());
        $invoiceModel = new Invoice($db->pdo());
        $invoiceItemModel = new InvoiceItem($db->pdo());
        $movementModel = new InventoryMovement($db->pdo());
        $product = $productId > 0 ? $productModel->findByIdForCompany($productId, $companyId) : null;

        if ($clientName === '' || $amount <= 0) {
            http_response_code(422);
            echo 'Estimate is missing required billing details.';
            return;
        }

        if ($productId > 0 && $product === null) {
            http_response_code(422);
            echo 'Estimate product could not be found.';
            return;
        }

        if ($product !== null && $quantity > 0 && !$productModel->hasAvailableStock($productId, $companyId, $quantity)) {
            http_response_code(422);
            echo 'Insufficient stock to convert this estimate into an invoice.';
            return;
        }

        $lineQuantity = $quantity > 0 ? $quantity : 1.0;
        $lineUnitPrice = (float) ($estimate['unit_price'] ?? 0);
        if ($lineUnitPrice <= 0) {
            $lineUnitPrice = $lineQuantity > 0 ? round($amount / $lineQuantity, 2) : $amount;
        }
        $lineDescription = trim((string) ($estimate['line_description'] ?? ''));
        if ($lineDescription === '') {
            $lineDescription = trim((string) ($estimate['product_description'] ?? ''));
        }
        if ($lineDescription === '') {
            $lineDescription = trim((string) ($estimate['product_name'] ?? ''));
        }
        if ($lineDescription === '') {
            $lineDescription = 'Estimate #' . $estimateId;
        }

        $db->pdo()->beginTransaction();
        try {
            $invoiceId = $invoiceModel->createForCompany(
                $companyId,
                $clientName,
                $amount,
                $issueDate,
                $dueDate,
                'issued',
                $customerId > 0 ? $customerId : null,
                0.0,
                0.0,
                'Converted from estimate #' . $estimateId
            );

            $invoiceItemModel->createForInvoice(
                $invoiceId,
                $companyId,
                $productId > 0 ? $productId : null,
                $lineDescription,
                $lineQuantity,
                $lineUnitPrice
            );

            if ($product !== null && (string) ($product['stock_control_type'] ?? 'STOCK_CONTROLLED') === 'STOCK_CONTROLLED') {
                $qtyBefore = (float) ($product['stock_qty'] ?? 0.0);
                if (!$productModel->applyStockDelta($productId, $companyId, -$lineQuantity)) {
                    throw new \RuntimeException('Unable to update stock for converted invoice.');
                }

                $movementModel->createForCompany(
                    $companyId,
                    $productId,
                    'out',
                    $lineQuantity,
                    $qtyBefore,
                    $qtyBefore - $lineQuantity,
                    'Converted from estimate #' . $estimateId . ' to invoice #' . $invoiceId,
                    (int) ($_SESSION['user']['user_id'] ?? 0)
                );
            }

            if (!$estimateModel->markConvertedToInvoice($estimateId, $companyId, $invoiceId)) {
                throw new \RuntimeException('Unable to link estimate to invoice.');
            }

            $db->pdo()->commit();
            AuditLogger::log($db, $context, 'estimate.convert_to_invoice', 'estimate', (string) $estimateId, 'Converted estimate to invoice #' . $invoiceId);
        } catch (\Throwable $e) {
            if ($db->pdo()->inTransaction()) {
                $db->pdo()->rollBack();
            }
            http_response_code(500);
            echo 'Unable to convert estimate to invoice.';
            return;
        }

        header('Location: /invoices/view?invoice_id=' . $invoiceId);
    }

    public static function estimatesDelete(Database $db, RequestContext $context): void
    {
        $companyId = self::companyId($context); if ($companyId === null) { self::deny(); return; }
        $id = (int) ($_POST['estimate_id'] ?? 0);
        (new Estimate($db->pdo()))->deleteForCompany($id, $companyId);
        AuditLogger::log($db, $context, 'estimate.delete', 'estimate', (string) $id, 'Deleted estimate');
        header('Location: /estimates');
    }

    public static function creditManagement(Database $db, RequestContext $context): void
    {
        $companyId = self::companyId($context); if ($companyId === null) { self::deny(); return; }
        $model = new Credit($db->pdo());
        $model->reconcileByCompany($companyId);
        $payCreditId = (int) ($_GET['pay_credit_id'] ?? 0);
        $search = trim((string) ($_GET['q'] ?? ''));
        $statusFilter = trim((string) ($_GET['status'] ?? ''));
        $fromDate = trim((string) ($_GET['from'] ?? ''));
        $toDate = trim((string) ($_GET['to'] ?? ''));
        $allRows = $model->listByCompany($companyId);
        $rows = $allRows;
        if ($search !== '') {
            $needle = mb_strtolower($search);
            $rows = array_values(array_filter($rows, static function (array $row) use ($needle): bool {
                $haystack = mb_strtolower(
                    (string) ($row['credit_no'] ?? '') . ' ' .
                    (string) ($row['customer_name'] ?? '') . ' ' .
                    (string) ($row['status'] ?? '') . ' ' .
                    (string) ($row['due_date'] ?? '') . ' ' .
                    (string) ($row['reason'] ?? '')
                );
                return str_contains($haystack, $needle);
            }));
        }

        if ($statusFilter !== '') {
            $rows = array_values(array_filter($rows, static function (array $row) use ($statusFilter): bool {
                return mb_strtolower((string) ($row['status'] ?? '')) === mb_strtolower($statusFilter);
            }));
        }

        if ($fromDate !== '' || $toDate !== '') {
            $rows = array_values(array_filter($rows, static function (array $row) use ($fromDate, $toDate): bool {
                $date = substr((string) ($row['created_at'] ?? ''), 0, 10);
                if ($date === '') {
                    return false;
                }
                if ($fromDate !== '' && $date < $fromDate) {
                    return false;
                }
                if ($toDate !== '' && $date > $toDate) {
                    return false;
                }
                return true;
            }));
        }
        $payments = $model->paymentListByCompany($companyId);
        $customers = (new Customer($db->pdo()))->listByCompany($companyId);

        $summary = ['issued' => 0.0, 'paid' => 0.0, 'outstanding' => 0.0];
        foreach ($allRows as $row) {
            $summary['issued'] += (float) ($row['amount'] ?? 0);
            $summary['paid'] += (float) ($row['amount_paid'] ?? 0);
            $summary['outstanding'] += (float) ($row['outstanding'] ?? 0);
        }

        View::render('ops/credit', [
            'company'   => $context->company(),
            'rows'      => $rows,
            'pay_credit_id' => $payCreditId,
            'payment_error' => (string) ($_GET['payment_error'] ?? ''),
            'search'    => $search,
            'status_filter' => $statusFilter,
            'from_date' => $fromDate,
            'to_date' => $toDate,
            'payments'  => $payments,
            'summary'   => $summary,
            'customers' => $customers,
            'token'     => \App\Middleware\CsrfMiddleware::token(),
            'errors'    => [],
        ]);
    }

    public static function creditIssue(Database $db, RequestContext $context): void
    {
        $companyId = self::companyId($context); if ($companyId === null) { self::deny(); return; }
        $customerId  = (int) ($_POST['customer_id'] ?? 0);
        $amountRaw   = trim((string) ($_POST['amount'] ?? '0'));
        $applyInterest = ((string) ($_POST['apply_interest'] ?? '1') === '1');
        $interestType = trim((string) ($_POST['interest_type'] ?? 'flat'));
        $interestRaw = trim((string) ($_POST['interest_percent'] ?? '0'));
        $dueDate     = trim((string) ($_POST['due_date'] ?? ''));
        $reason      = trim((string) ($_POST['reason'] ?? ''));

        $customerModel = new Customer($db->pdo());
        $customer = null;
        $errors = [];

        if ($customerId <= 0) {
            $errors[] = 'Please select a customer.';
        } else {
            $customer = $customerModel->findByIdForCompany($customerId, $companyId);
            if ($customer === null) { $errors[] = 'Selected customer does not belong to this company.'; }
        }

        if (!is_numeric($amountRaw) || (float) $amountRaw <= 0) { $errors[] = 'Amount must be greater than 0.'; }
        if (!in_array($interestType, ['flat', 'monthly', 'daily'], true)) { $errors[] = 'Invalid interest type.'; }
        if (!$applyInterest) {
            $interestRaw = '0';
        }
        if (!is_numeric($interestRaw) || (float) $interestRaw < 0 || (float) $interestRaw > 100) { $errors[] = 'Interest must be between 0 and 100.'; }
        if ($reason === '') { $errors[] = 'Reason is required.'; }
        if ($dueDate !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $dueDate)) { $errors[] = 'Due date must be YYYY-MM-DD.'; }

        if ($errors !== []) {
            http_response_code(422);
            $model = new Credit($db->pdo());
            View::render('ops/credit', [
                'company'   => $context->company(),
                'rows'      => $model->listByCompany($companyId),
                'payments'  => $model->paymentListByCompany($companyId),
                'summary'   => ['issued' => 0.0, 'paid' => 0.0, 'outstanding' => 0.0],
                'customers' => $customerModel->listByCompany($companyId),
                'token'     => \App\Middleware\CsrfMiddleware::token(),
                'errors'    => $errors,
            ]);
            return;
        }

        $customerName = (string) $customer['customer_name'];
        $id = (new Credit($db->pdo()))->create($companyId, $customerId, $customerName, (float) $amountRaw, $interestType, (float) $interestRaw, $reason, $dueDate !== '' ? $dueDate : null);
        AuditLogger::log($db, $context, 'credit.issue', 'credit', (string) $id, 'Issued credit for ' . $customerName);
        header('Location: /credit-management');
    }

    public static function creditPayment(Database $db, RequestContext $context): void
    {
        $companyId = self::companyId($context); if ($companyId === null) { self::deny(); return; }
        $creditId = (int) ($_POST['credit_id'] ?? 0);
        $amountRaw = trim((string) ($_POST['amount'] ?? '0'));
        $paymentDate = trim((string) ($_POST['payment_date'] ?? ''));
        $paymentMethod = trim((string) ($_POST['payment_method'] ?? ''));
        $reference = trim((string) ($_POST['reference'] ?? ''));

        if ($creditId <= 0 || !is_numeric($amountRaw) || (float) $amountRaw <= 0 || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $paymentDate)) {
            http_response_code(422);
            echo 'Invalid payment input.';
            return;
        }

        $ok = (new Credit($db->pdo()))->recordPayment($companyId, $creditId, (float) $amountRaw, $paymentDate, $paymentMethod, $reference);
        if ($ok) {
            AuditLogger::log($db, $context, 'credit.payment', 'credit', (string) $creditId, 'Recorded credit payment');
            header('Location: /credit-management');
            return;
        }
        header('Location: /credit-management?payment_error=1&pay_credit_id=' . $creditId);
    }

    public static function creditAgreement(Database $db, RequestContext $context): void
    {
        $companyId = self::companyId($context); if ($companyId === null) { self::deny(); return; }
        $creditId = (int) ($_GET['credit_id'] ?? 0);
        if ($creditId <= 0) {
            http_response_code(422);
            echo 'Credit id is required.';
            return;
        }

        $credit = (new Credit($db->pdo()))->findAgreementByIdForCompany($creditId, $companyId);
        if ($credit === null) {
            http_response_code(404);
            echo 'Credit agreement not found.';
            return;
        }

        $company = (new Company($db->pdo()))->findById($companyId) ?? $context->company();
        View::render('ops/credit_agreement', [
            'company' => $company,
            'credit' => $credit,
        ]);
    }

    public static function creditView(Database $db, RequestContext $context): void
    {
        $companyId = self::companyId($context); if ($companyId === null) { self::deny(); return; }
        $creditId = (int) ($_GET['credit_id'] ?? 0);
        if ($creditId <= 0) {
            http_response_code(422);
            echo 'Credit id is required.';
            return;
        }

        $model = new Credit($db->pdo());
        $model->reconcileByCompany($companyId);
        $credit = $model->findAgreementByIdForCompany($creditId, $companyId);
        if ($credit === null) {
            http_response_code(404);
            echo 'Credit record not found.';
            return;
        }

        $payments = $model->paymentListByCreditForCompany($companyId, $creditId);
        $runningBalance = (float) ($credit['total_amount'] ?? 0);
        foreach ($payments as &$payment) {
            $runningBalance -= (float) ($payment['amount'] ?? 0);
            if ($runningBalance < 0) {
                $runningBalance = 0;
            }
            $payment['running_balance'] = $runningBalance;
        }
        unset($payment);

        $company = (new Company($db->pdo()))->findById($companyId) ?? $context->company();
        View::render('ops/credit_view', [
            'company' => $company,
            'credit' => $credit,
            'payments' => $payments,
        ]);
    }

    public static function creditWriteOff(Database $db, RequestContext $context): void
    {
        $companyId = self::companyId($context); if ($companyId === null) { self::deny(); return; }
        $creditId = (int) ($_POST['credit_id'] ?? 0);
        $reason = trim((string) ($_POST['write_off_reason'] ?? ''));
        $actor = (string) ($_SESSION['user']['email'] ?? 'system');
        if ($creditId > 0) {
            (new Credit($db->pdo()))->writeOff($companyId, $creditId, $reason, $actor);
            AuditLogger::log($db, $context, 'credit.write_off', 'credit', (string) $creditId, 'Marked credit as bad debt');
        }
        header('Location: /credit-management');
    }

    public static function creditReopen(Database $db, RequestContext $context): void
    {
        $companyId = self::companyId($context); if ($companyId === null) { self::deny(); return; }
        $creditId = (int) ($_POST['credit_id'] ?? 0);
        if ($creditId > 0) {
            (new Credit($db->pdo()))->reopen($companyId, $creditId);
            AuditLogger::log($db, $context, 'credit.reopen', 'credit', (string) $creditId, 'Reopened bad debt credit');
        }
        header('Location: /credit-management');
    }

    public static function reports(Database $db, RequestContext $context): void
    {
        $companyId = self::companyId($context); if ($companyId === null) { self::deny(); return; }
        $queries = [
            'invoices' => 'SELECT COUNT(*) c FROM invoices WHERE company_id = :company_id',
            'customers' => 'SELECT COUNT(*) c FROM customers WHERE company_id = :company_id',
            'products' => 'SELECT COUNT(*) c FROM products WHERE company_id = :company_id',
            'expenses' => 'SELECT COALESCE(SUM(amount),0) c FROM expenses WHERE company_id = :company_id',
            'sales' => 'SELECT COALESCE(SUM(amount),0) c FROM invoices WHERE company_id = :company_id AND status = \'paid\'',
        ];
        $stats = [];
        foreach ($queries as $k => $q) {
            $s = $db->pdo()->prepare($q);
            $s->execute(['company_id' => $companyId]);
            $stats[$k] = (float) (($s->fetch()['c'] ?? 0));
        }

        View::render('ops/reports', ['company' => $context->company(), 'stats' => $stats]);
    }

    public static function companies(Database $db, RequestContext $context): void
    {
        $rows = $db->pdo()->query('SELECT company_id, company_name, subdomain, phone, email, tax_number, vat_number, status, created_at FROM companies ORDER BY company_id DESC')->fetchAll() ?: [];
        View::render('ops/companies', ['company' => $context->company(), 'rows' => $rows]);
    }

    public static function companyDetails(Database $db, RequestContext $context): void
    {
        $companyId = self::companyId($context); if ($companyId === null) { self::deny(); return; }
        $row = (new Company($db->pdo()))->findById($companyId);
        View::render('ops/company_details', [
            'company' => $context->company(),
            'company_details' => $row ?: $context->company(),
            'token' => \App\Middleware\CsrfMiddleware::token(),
            'errors' => [],
        ]);
    }

    public static function companyDetailsUpdate(Database $db, RequestContext $context): void
    {
        $companyId = self::companyId($context); if ($companyId === null) { self::deny(); return; }
        $name = trim((string) ($_POST['company_name'] ?? ''));
        $logoData = trim((string) ($_POST['logo_data'] ?? ''));
        if ($name === '') {
            http_response_code(422);
            View::render('ops/company_details', ['company' => $context->company(), 'company_details' => $context->company(), 'token' => \App\Middleware\CsrfMiddleware::token(), 'errors' => ['Company name is required.']]);
            return;
        }

        $payload = [
            'company_name' => $name,
            'registration_number' => trim((string) ($_POST['registration_number'] ?? '')),
            'phone' => trim((string) ($_POST['phone'] ?? '')),
            'email' => trim((string) ($_POST['email'] ?? '')),
            'address' => trim((string) ($_POST['address'] ?? '')),
            'city' => trim((string) ($_POST['city'] ?? '')),
            'province' => trim((string) ($_POST['province'] ?? '')),
            'postal_code' => trim((string) ($_POST['postal_code'] ?? '')),
            'country' => trim((string) ($_POST['country'] ?? '')),
            'tax_number' => trim((string) ($_POST['tax_number'] ?? '')),
            'vat_number' => trim((string) ($_POST['vat_number'] ?? '')),
            'bank_name' => trim((string) ($_POST['bank_name'] ?? '')),
            'bank_account_holder' => trim((string) ($_POST['bank_account_holder'] ?? '')),
            'bank_account_number' => trim((string) ($_POST['bank_account_number'] ?? '')),
            'bank_routing_number' => trim((string) ($_POST['bank_routing_number'] ?? '')),
            'bank_swift_code' => trim((string) ($_POST['bank_swift_code'] ?? '')),
            'bank_iban' => trim((string) ($_POST['bank_iban'] ?? '')),
        ];

        if ($logoData !== '') {
            $payload['logo_data'] = $logoData;
        } else {
            $existing = (new Company($db->pdo()))->findById($companyId);
            $payload['logo_data'] = $existing['logo_data'] ?? null;
        }

        (new Company($db->pdo()))->updateProfile($companyId, $payload);
        AuditLogger::log($db, $context, 'company.update', 'company', (string) $companyId, 'Updated company details');
        header('Location: /company-details');
    }

    public static function exchangeRates(Database $db, RequestContext $context): void
    {
        $companyId = self::companyId($context); if ($companyId === null) { self::deny(); return; }
        $rows = (new ExchangeRate($db->pdo()))->listByCompany($companyId);
        View::render('ops/exchange_rates', ['company' => $context->company(), 'rows' => $rows, 'token' => \App\Middleware\CsrfMiddleware::token(), 'errors' => []]);
    }

    public static function exchangeRatesStore(Database $db, RequestContext $context): void
    {
        $companyId = self::companyId($context); if ($companyId === null) { self::deny(); return; }
        $code = strtoupper(trim((string) ($_POST['currency_code'] ?? '')));
        $rateRaw = trim((string) ($_POST['rate_to_nad'] ?? ''));
        $date = trim((string) ($_POST['effective_date'] ?? ''));

        $errors = [];
        if ($code === '' || !preg_match('/^[A-Z]{3,10}$/', $code)) { $errors[] = 'Currency code must be 3-10 uppercase letters.'; }
        if (!is_numeric($rateRaw) || (float) $rateRaw <= 0) { $errors[] = 'Rate must be greater than 0.'; }
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) { $errors[] = 'Effective date must be YYYY-MM-DD.'; }

        $model = new ExchangeRate($db->pdo());
        if ($errors !== []) {
            http_response_code(422);
            View::render('ops/exchange_rates', ['company' => $context->company(), 'rows' => $model->listByCompany($companyId), 'token' => \App\Middleware\CsrfMiddleware::token(), 'errors' => $errors]);
            return;
        }

        $id = $model->createForCompany($companyId, $code, (float) $rateRaw, $date);
        AuditLogger::log($db, $context, 'exchange_rate.create', 'exchange_rate', (string) $id, 'Added rate for ' . $code);
        header('Location: /exchange-rates');
    }

    public static function exchangeRatesDelete(Database $db, RequestContext $context): void
    {
        $companyId = self::companyId($context); if ($companyId === null) { self::deny(); return; }
        $id = (int) ($_POST['rate_id'] ?? 0);
        (new ExchangeRate($db->pdo()))->deleteForCompany($id, $companyId);
        AuditLogger::log($db, $context, 'exchange_rate.delete', 'exchange_rate', (string) $id, 'Deleted exchange rate');
        header('Location: /exchange-rates');
    }

    public static function setup(Database $db, RequestContext $context): void
    {
        $app = $context->appConfig();
        View::render('ops/setup', ['company' => $context->company(), 'app' => $app]);
    }

    public static function licenseManager(Database $db, RequestContext $context): void
    {
        $companyId = self::companyId($context); if ($companyId === null) { self::deny(); return; }
        $rows = (new License($db->pdo()))->listByCompany($companyId);
        View::render('ops/license', [
            'company' => $context->company(),
            'rows' => $rows,
            'errors' => [],
        ]);
    }

    public static function licenseActivation(Database $db, RequestContext $context): void
    {
        $companyId = self::companyId($context); if ($companyId === null) { self::deny(); return; }
        View::render('ops/license_activation', [
            'company' => $context->company(),
            'token' => \App\Middleware\CsrfMiddleware::token(),
            'errors' => [],
        ]);
    }

    public static function licenseActivationStore(Database $db, RequestContext $context): void
    {
        $companyId = self::companyId($context); if ($companyId === null) { self::deny(); return; }

        $licenseKey = strtoupper(trim((string) ($_POST['license_key'] ?? '')));
        $domain = strtolower(trim((string) ($_POST['domain'] ?? '')));
        $expiryDate = trim((string) ($_POST['expiry_date'] ?? ''));
        $errors = [];

        if ($licenseKey === '') { $errors[] = 'License key is required.'; }
        if ($domain === '') { $errors[] = 'Domain is required.'; }
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $expiryDate)) { $errors[] = 'Expiry date must be YYYY-MM-DD.'; }

        if ($errors !== []) {
            http_response_code(422);
            View::render('ops/license_activation', [
                'company' => $context->company(),
                'token' => \App\Middleware\CsrfMiddleware::token(),
                'errors' => $errors,
            ]);
            return;
        }

        try {
            $licenseId = (new License($db->pdo()))->createForCompany($companyId, $licenseKey, $domain, $expiryDate);
            AuditLogger::log($db, $context, 'license.activate', 'license', (string) $licenseId, 'Activated license for ' . $domain);
        } catch (\Throwable $e) {
            http_response_code(422);
            View::render('ops/license_activation', [
                'company' => $context->company(),
                'token' => \App\Middleware\CsrfMiddleware::token(),
                'errors' => ['Unable to activate license. The key may already exist or the data is invalid.'],
            ]);
            return;
        }

        header('Location: /license-manager');
    }

    public static function changePassword(Database $db, RequestContext $context): void
    {
        View::render('ops/change_password', ['company' => $context->company(), 'token' => \App\Middleware\CsrfMiddleware::token(), 'errors' => []]);
    }

    public static function changePasswordUpdate(Database $db, RequestContext $context): void
    {
        $companyId = self::companyId($context); if ($companyId === null) { self::deny(); return; }
        $userId = (int) ($_SESSION['user']['user_id'] ?? 0);

        $current = (string) ($_POST['current_password'] ?? '');
        $new = (string) ($_POST['new_password'] ?? '');
        $confirm = (string) ($_POST['confirm_password'] ?? '');

        $errors = [];
        if (mb_strlen($new) < 8) { $errors[] = 'New password must be at least 8 characters.'; }
        if ($new !== $confirm) { $errors[] = 'Password confirmation does not match.'; }

        $stmt = $db->pdo()->prepare('SELECT password_hash FROM users WHERE user_id = :user_id AND company_id = :company_id LIMIT 1');
        $stmt->execute(['user_id' => $userId, 'company_id' => $companyId]);
        $row = $stmt->fetch();
        if (!$row || !password_verify($current, (string) $row['password_hash'])) {
            $errors[] = 'Current password is incorrect.';
        }

        if ($errors !== []) {
            http_response_code(422);
            View::render('ops/change_password', ['company' => $context->company(), 'token' => \App\Middleware\CsrfMiddleware::token(), 'errors' => $errors]);
            return;
        }

        $up = $db->pdo()->prepare('UPDATE users SET password_hash = :password_hash WHERE user_id = :user_id AND company_id = :company_id');
        $up->execute(['password_hash' => password_hash($new, PASSWORD_DEFAULT), 'user_id' => $userId, 'company_id' => $companyId]);
        AuditLogger::log($db, $context, 'user.change_password', 'user', (string) $userId, 'Changed password');
        header('Location: /dashboard');
    }

    private static function estimateInput(Database $db, int $companyId): array
    {
        $customerIdRaw = (int) ($_POST['customer_id'] ?? 0);
        $productIdRaw  = (int) ($_POST['product_id'] ?? 0);
        $quantityRaw   = trim((string) ($_POST['quantity'] ?? '1'));
        $clientName    = trim((string) ($_POST['client_name'] ?? ''));
        $amountRaw     = trim((string) ($_POST['amount'] ?? ''));
        $estimateDate  = trim((string) ($_POST['estimate_date'] ?? ''));
        $expiryDate    = trim((string) ($_POST['expiry_date'] ?? ''));
        $status        = trim((string) ($_POST['status'] ?? 'draft'));

        // Auto-fill client name from selected customer
        $customerId = null;
        $productId = null;
        $quantity = null;
        $unitPrice = null;
        $lineDescription = null;
        if ($customerIdRaw > 0) {
            $customer = (new Customer($db->pdo()))->findByIdForCompany($customerIdRaw, $companyId);
            if ($customer !== null) {
                $customerId = $customerIdRaw;
                if ($clientName === '') {
                    $clientName = (string) $customer['customer_name'];
                }
            }
        }

        if ($productIdRaw > 0) {
            $qty = ctype_digit($quantityRaw) ? (int) $quantityRaw : 0;
            $product = (new Product($db->pdo()))->findByIdForCompany($productIdRaw, $companyId);
            if ($product !== null) {
                $productId = $productIdRaw;
                $unitPrice = (float) ($product['unit_price'] ?? 0);
                $lineDescription = trim((string) ($product['description'] ?? ''));
                if ($qty > 0) {
                    $quantity = $qty;
                    $amountRaw = (string) round($unitPrice * $qty, 2);
                } elseif (is_numeric($amountRaw) && (float) $amountRaw > 0 && $unitPrice > 0) {
                    $derivedQty = self::deriveQuantityFromAmount((float) $amountRaw, $unitPrice);
                    if ($derivedQty !== null) {
                        $quantity = $derivedQty;
                    }
                }
            }
        }

        $errors = [];
        if ($clientName === '') { $errors[] = 'Client name is required.'; }
        if ($productIdRaw > 0 && (!ctype_digit($quantityRaw) || (int) $quantityRaw <= 0)) { $errors[] = 'Quantity must be a whole number greater than 0.'; }
        if (!is_numeric($amountRaw) || (float) $amountRaw <= 0) { $errors[] = 'Amount must be positive.'; }
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $estimateDate)) { $errors[] = 'Estimate date must be YYYY-MM-DD.'; }
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $expiryDate)) { $errors[] = 'Expiry date must be YYYY-MM-DD.'; }
        if (!in_array($status, ['draft', 'sent', 'accepted', 'rejected', 'expired'], true)) { $errors[] = 'Invalid estimate status.'; }

        return [$customerId, $productId, $quantity, $unitPrice, $lineDescription, $clientName, (float) $amountRaw, $estimateDate, $expiryDate, $status, $errors];
    }

    private static function normalizeEstimateLine(Database $db, int $companyId, array $row): array
    {
        $amount = (float) ($row['amount'] ?? 0);
        if ($amount <= 0) {
            return $row;
        }

        $productId = (int) ($row['product_id'] ?? 0);
        $quantity = (int) ($row['quantity'] ?? 0);
        $unitPrice = (float) ($row['unit_price'] ?? 0);

        if ($productId > 0 && $unitPrice <= 0) {
            $product = (new Product($db->pdo()))->findByIdForCompany($productId, $companyId);
            if ($product !== null) {
                $unitPrice = (float) ($product['unit_price'] ?? 0);
                if ($unitPrice > 0) {
                    $row['unit_price'] = $unitPrice;
                }
                if (((string) ($row['product_name'] ?? '')) === '') {
                    $row['product_name'] = $product['product_name'] ?? '';
                }
                if (((string) ($row['product_description'] ?? '')) === '') {
                    $row['product_description'] = $product['description'] ?? '';
                }
                if (((string) ($row['line_description'] ?? '')) === '') {
                    $row['line_description'] = $row['product_description'] ?? '';
                }
            }
        }

        if ($unitPrice > 0) {
            $derivedQty = self::deriveQuantityFromAmount($amount, $unitPrice);
            if ($derivedQty !== null && ($quantity <= 0 || abs(($quantity * $unitPrice) - $amount) > 0.01)) {
                $row['quantity'] = $derivedQty;
            }
            return $row;
        }

        if ($productId > 0) {
            return $row;
        }

        $matches = [];
        foreach ((new Product($db->pdo()))->listActiveByCompany($companyId) as $product) {
            $candidateUnitPrice = (float) ($product['unit_price'] ?? 0);
            if ($candidateUnitPrice <= 0) {
                continue;
            }
            $derivedQty = self::deriveQuantityFromAmount($amount, $candidateUnitPrice);
            if ($derivedQty !== null) {
                $matches[] = [
                    'product_id' => (int) $product['product_id'],
                    'product_name' => (string) ($product['product_name'] ?? ''),
                    'unit_price' => $candidateUnitPrice,
                    'quantity' => $derivedQty,
                ];
            }
        }

        if (count($matches) === 1) {
            $row['product_id'] = $matches[0]['product_id'];
            $row['product_name'] = $matches[0]['product_name'];
            $row['unit_price'] = $matches[0]['unit_price'];
            $row['quantity'] = $matches[0]['quantity'];
        }

        return $row;
    }

    private static function deriveQuantityFromAmount(float $amount, float $unitPrice): ?int
    {
        if ($amount <= 0 || $unitPrice <= 0) {
            return null;
        }

        $raw = $amount / $unitPrice;
        $rounded = (int) round($raw);
        if ($rounded <= 0) {
            return null;
        }

        return abs(($rounded * $unitPrice) - $amount) <= 0.01 ? $rounded : null;
    }

    private static function journalExpenseRows(Database $db, int $companyId, string $fromDate, string $toDate): array
    {
        $stmt = $db->pdo()->prepare(
            'SELECT entry_id, entry_date, account, description, debit, credit
             FROM journal_entries
             WHERE company_id = :company_id AND entry_date BETWEEN :from AND :to
             ORDER BY entry_date ASC, entry_id ASC'
        );
        $stmt->execute(['company_id' => $companyId, 'from' => $fromDate, 'to' => $toDate]);
        $rows = $stmt->fetchAll() ?: [];

        $expenseRows = [];
        foreach ($rows as $row) {
            if (!in_array((string) ($row['account'] ?? ''), self::expenseAccounts(), true)) {
                continue;
            }

            $amount = (float) ($row['debit'] ?? 0) - (float) ($row['credit'] ?? 0);
            if ($amount <= 0) {
                continue;
            }

            $expenseRows[] = [
                'expense_id' => 'JNL-' . (string) ($row['entry_id'] ?? ''),
                'category' => (string) ($row['account'] ?? 'Expense'),
                'description' => trim((string) ($row['description'] ?? '')) !== ''
                    ? (string) $row['description']
                    : 'Journal entry #' . (string) ($row['entry_id'] ?? ''),
                'amount' => $amount,
                'expense_date' => (string) ($row['entry_date'] ?? ''),
            ];
        }

        return $expenseRows;
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

    private static function companyId(RequestContext $context): ?int
    {
        $cid = (int) ($context->company()['company_id'] ?? 0);
        $sid = (int) ($_SESSION['user']['company_id'] ?? 0);
        return ($cid > 0 && $sid > 0 && $cid === $sid) ? $cid : null;
    }

    private static function deny(): void
    {
        http_response_code(403);
        echo 'Tenant context is invalid.';
    }
}
