<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Core\RequestContext;
use App\Core\Database;
use App\Models\Credit;
use App\Models\Customer;
use App\Support\SchemaCompat;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Mail;

class OperationsController extends Controller
{
    public function sales(Request $request, RequestContext $context, Database $db)
    {
        $company = $context->company();
        if (!$company) {
            return response('Company not found', 404);
        }

        $companyId = (int) $company['company_id'];
        $salesTotalAmount = 0;
        $invoiceCount = 0;

        try {
            $pdo = $db->pdo();
            $stmt = $pdo->prepare('SELECT COUNT(*) as count, COALESCE(SUM(' . SchemaCompat::invoiceAmountSql() . '), 0) as total FROM invoices WHERE company_id = ?');
            $stmt->execute([$companyId]);
            $result = $stmt->fetch(\PDO::FETCH_ASSOC);
            if ($result) {
                $invoiceCount = (int) $result['count'];
                $salesTotalAmount = (float) $result['total'];
            }
        } catch (\Exception $e) {
            error_log("Sales calculation error: " . $e->getMessage());
        }

        return view('operations.sales', [
            'company' => $company,
            'total_invoices' => $invoiceCount,
            'total_sales' => $salesTotalAmount,
        ]);
    }

    public function financialStatement(Request $request, RequestContext $context, Database $db)
    {
        $company = $context->company();
        if (!$company) {
            return response('Company not found', 404);
        }

        $companyId = (int) $company['company_id'];
        $pdo = $db->pdo();
        $summary = ['gross_revenue' => 0, 'operating_expenses' => 0, 'net_position' => 0];

        try {
            $stmt = $pdo->prepare('SELECT COALESCE(SUM(' . SchemaCompat::invoiceAmountSql() . '), 0) FROM invoices WHERE company_id = ?');
            $stmt->execute([$companyId]);
            $summary['gross_revenue'] = (float) $stmt->fetchColumn();

            $stmt = $pdo->prepare('SELECT COALESCE(SUM(amount), 0) FROM expenses WHERE company_id = ?');
            $stmt->execute([$companyId]);
            $summary['operating_expenses'] = (float) $stmt->fetchColumn();

            $summary['net_position'] = $summary['gross_revenue'] - $summary['operating_expenses'];
        } catch (\Throwable $e) {
            error_log("financialStatement error: " . $e->getMessage());
        }

        return view('operations.financial-statement', [
            'company' => $company,
            'summary' => $summary,
        ]);
    }

    public function reports(Request $request, RequestContext $context)
    {
        $company = $context->company();
        if (!$company) {
            return response('Company not found', 404);
        }

        $reports = [
            ['name' => 'Sales Report', 'route' => '/reports/sales', 'icon' => 'fa-file-invoice-dollar', 'desc' => 'Invoice activity, client performance, and monthly sales trends.'],
            ['name' => 'Revenue Report', 'route' => '/reports/revenue', 'icon' => 'fa-chart-line', 'desc' => 'Cash collected vs expenses with profit margins and collection rates.'],
            ['name' => 'Expense Report', 'route' => '/reports/expenses', 'icon' => 'fa-receipt', 'desc' => 'Operating costs by category, monthly trend, and transaction detail.'],
            ['name' => 'Balance Sheet', 'route' => '/reports/balance', 'icon' => 'fa-scale-balanced', 'desc' => 'Assets, liabilities, and equity snapshot at a specific date.'],
            ['name' => 'Financial Statement', 'route' => '/sales/financial-statement', 'icon' => 'fa-file-lines', 'desc' => 'Consolidated revenue, costs, and margin performance summary.'],
            ['name' => 'General Ledger', 'route' => '/sales/general-ledger', 'icon' => 'fa-book', 'desc' => 'Journal entries with account codes, debits, and credits.'],
        ];

        return view('operations.reports', [
            'company' => $company,
            'reports' => $reports,
        ]);
    }

    /* ------------------------------------------------------------------ */
    /*  Individual Reports                                                 */
    /* ------------------------------------------------------------------ */

    public function generalLedger(Request $request, RequestContext $context, Database $db)
    {
        $company = $context->company();
        if (!$company) {
            return response('Company not found', 404);
        }

        $companyId = (int) $company['company_id'];
        $pdo = $db->pdo();
        $from = $request->query('from', date('Y-m-01', strtotime('-11 months')));
        $to = $request->query('to', date('Y-m-d'));
        $entries = [];
        $totals = ['debit' => 0, 'credit' => 0];

        try {
            $stmt = $pdo->prepare(
                'SELECT entry_id, date, account_code, reference, description, debit_amount, credit_amount, status
                 FROM journal_entries
                 WHERE company_id = :cid AND date BETWEEN :from AND :to
                 ORDER BY date DESC, entry_id DESC'
            );
            $stmt->execute(['cid' => $companyId, 'from' => $from, 'to' => $to]);
            $entries = $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];

            foreach ($entries as $e) {
                $totals['debit'] += (float) $e['debit_amount'];
                $totals['credit'] += (float) $e['credit_amount'];
            }
        } catch (\Throwable $e) {
            error_log('generalLedger error: ' . $e->getMessage());
        }

        return view('reports.general-ledger', compact('company', 'entries', 'totals', 'from', 'to'));
    }

    public function reportSales(Request $request, RequestContext $context, Database $db)
    {
        $company = $context->company();
        if (!$company) {
            return response('Company not found', 404);
        }

        $companyId = (int) $company['company_id'];
        $pdo = $db->pdo();
        $from = $request->query('from', date('Y-01-01'));
        $to = $request->query('to', date('Y-m-d'));
        $invoiceAmountSql = SchemaCompat::invoiceAmountSql();

        try {
            // Summary metrics
            $stmt = $pdo->prepare(
                'SELECT COUNT(*) AS count,
                        COALESCE(SUM(' . $invoiceAmountSql . '), 0) AS total,
                        COALESCE(SUM(CASE WHEN status = "paid" THEN ' . $invoiceAmountSql . ' ELSE 0 END), 0) AS paid,
                        COALESCE(SUM(CASE WHEN status != "paid" THEN ' . $invoiceAmountSql . ' ELSE 0 END), 0) AS outstanding
                 FROM invoices
                 WHERE company_id = :cid AND issue_date BETWEEN :from AND :to'
            );
            $stmt->execute(['cid' => $companyId, 'from' => $from, 'to' => $to]);
            $summary = $stmt->fetch(\PDO::FETCH_ASSOC) ?: [];

            // Monthly breakdown
            $stmt = $pdo->prepare(
                "SELECT DATE_FORMAT(issue_date, '%Y-%m') AS month,
                        COUNT(*) AS count,
                        COALESCE(SUM(" . $invoiceAmountSql . "), 0) AS total
                 FROM invoices
                 WHERE company_id = :cid AND issue_date BETWEEN :from AND :to
                 GROUP BY month ORDER BY month"
            );
            $stmt->execute(['cid' => $companyId, 'from' => $from, 'to' => $to]);
            $monthly = $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];

            // Top clients
            $stmt = $pdo->prepare(
                'SELECT client_name, COUNT(*) AS invoices, COALESCE(SUM(' . $invoiceAmountSql . '), 0) AS total
                 FROM invoices
                 WHERE company_id = :cid AND issue_date BETWEEN :from AND :to AND client_name IS NOT NULL
                 GROUP BY client_name ORDER BY total DESC LIMIT 10'
            );
            $stmt->execute(['cid' => $companyId, 'from' => $from, 'to' => $to]);
            $topClients = $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];

            // Recent invoices
            $stmt = $pdo->prepare(
                'SELECT invoice_no, issue_date, client_name, ' . $invoiceAmountSql . ' AS total, status
                 FROM invoices
                 WHERE company_id = :cid AND issue_date BETWEEN :from AND :to
                 ORDER BY issue_date DESC LIMIT 50'
            );
            $stmt->execute(['cid' => $companyId, 'from' => $from, 'to' => $to]);
            $invoices = $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];
        } catch (\Throwable $e) {
            error_log("reportSales error: " . $e->getMessage());
            $summary = ['count' => 0, 'total' => 0, 'paid' => 0, 'outstanding' => 0];
            $monthly = [];
            $topClients = [];
            $invoices = [];
        }

        return view('reports.sales', compact('company', 'summary', 'monthly', 'topClients', 'invoices', 'from', 'to'));
    }

    public function reportRevenue(Request $request, RequestContext $context, Database $db)
    {
        $company = $context->company();
        if (!$company) {
            return response('Company not found', 404);
        }

        $companyId = (int) $company['company_id'];
        $pdo = $db->pdo();
        $from = $request->query('from', date('Y-01-01'));
        $to = $request->query('to', date('Y-m-d'));
        $invoiceAmountSql = SchemaCompat::invoiceAmountSql();

        try {
            // Revenue = paid invoices
            $stmt = $pdo->prepare(
                'SELECT COALESCE(SUM(p.amount), 0) AS collected
                 FROM payments p
                 INNER JOIN invoices i ON i.invoice_id = p.invoice_id
                 WHERE i.company_id = :cid AND p.payment_date BETWEEN :from AND :to'
            );
            $stmt->execute(['cid' => $companyId, 'from' => $from, 'to' => $to]);
            $collected = (float) ($stmt->fetchColumn() ?: 0);

            // Total invoiced
            $stmt = $pdo->prepare(
                'SELECT COALESCE(SUM(' . $invoiceAmountSql . '), 0) AS invoiced
                 FROM invoices
                 WHERE company_id = :cid AND issue_date BETWEEN :from AND :to'
            );
            $stmt->execute(['cid' => $companyId, 'from' => $from, 'to' => $to]);
            $invoiced = (float) ($stmt->fetchColumn() ?: 0);

            // Expenses total
            $stmt = $pdo->prepare(
                'SELECT COALESCE(SUM(amount), 0) AS expenses
                 FROM expenses
                 WHERE company_id = :cid AND date BETWEEN :from AND :to'
            );
            $stmt->execute(['cid' => $companyId, 'from' => $from, 'to' => $to]);
            $expenses = (float) ($stmt->fetchColumn() ?: 0);

            // Monthly revenue (payments received)
            $stmt = $pdo->prepare(
                "SELECT DATE_FORMAT(p.payment_date, '%Y-%m') AS month,
                        COALESCE(SUM(p.amount), 0) AS collected
                 FROM payments p
                 INNER JOIN invoices i ON i.invoice_id = p.invoice_id
                 WHERE i.company_id = :cid AND p.payment_date BETWEEN :from AND :to
                 GROUP BY month ORDER BY month"
            );
            $stmt->execute(['cid' => $companyId, 'from' => $from, 'to' => $to]);
            $monthlyRevenue = $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];

            // Monthly expenses
            $stmt = $pdo->prepare(
                "SELECT DATE_FORMAT(date, '%Y-%m') AS month,
                        COALESCE(SUM(amount), 0) AS spent
                 FROM expenses
                 WHERE company_id = :cid AND date BETWEEN :from AND :to
                 GROUP BY month ORDER BY month"
            );
            $stmt->execute(['cid' => $companyId, 'from' => $from, 'to' => $to]);
            $monthlyExpenses = $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];
        } catch (\Throwable $e) {
            error_log("reportRevenue error: " . $e->getMessage());
            $collected = 0;
            $invoiced = 0;
            $expenses = 0;
            $monthlyRevenue = [];
            $monthlyExpenses = [];
        }

        $netProfit = $collected - $expenses;

        return view('reports.revenue', compact(
            'company', 'collected', 'invoiced', 'expenses', 'netProfit',
            'monthlyRevenue', 'monthlyExpenses', 'from', 'to'
        ));
    }

    public function reportExpenses(Request $request, RequestContext $context, Database $db)
    {
        $company = $context->company();
        if (!$company) {
            return response('Company not found', 404);
        }

        $companyId = (int) $company['company_id'];
        $pdo = $db->pdo();
        $from = $request->query('from', date('Y-01-01'));
        $to = $request->query('to', date('Y-m-d'));

        try {
            // Total
            $stmt = $pdo->prepare(
                'SELECT COUNT(*) AS count, COALESCE(SUM(amount), 0) AS total
                 FROM expenses
                 WHERE company_id = :cid AND date BETWEEN :from AND :to'
            );
            $stmt->execute(['cid' => $companyId, 'from' => $from, 'to' => $to]);
            $summary = $stmt->fetch(\PDO::FETCH_ASSOC) ?: ['count' => 0, 'total' => 0];

            // By category
            $stmt = $pdo->prepare(
                'SELECT category, COUNT(*) AS count, COALESCE(SUM(amount), 0) AS total
                 FROM expenses
                 WHERE company_id = :cid AND date BETWEEN :from AND :to
                 GROUP BY category ORDER BY total DESC'
            );
            $stmt->execute(['cid' => $companyId, 'from' => $from, 'to' => $to]);
            $byCategory = $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];

            // Monthly
            $stmt = $pdo->prepare(
                "SELECT DATE_FORMAT(date, '%Y-%m') AS month, COALESCE(SUM(amount), 0) AS total
                 FROM expenses
                 WHERE company_id = :cid AND date BETWEEN :from AND :to
                 GROUP BY month ORDER BY month"
            );
            $stmt->execute(['cid' => $companyId, 'from' => $from, 'to' => $to]);
            $monthly = $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];

            // Recent items
            $stmt = $pdo->prepare(
                'SELECT date, category, description, amount
                 FROM expenses
                 WHERE company_id = :cid AND date BETWEEN :from AND :to
                 ORDER BY date DESC LIMIT 50'
            );
            $stmt->execute(['cid' => $companyId, 'from' => $from, 'to' => $to]);
            $expenses = $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];
        } catch (\Throwable $e) {
            error_log("reportExpenses error: " . $e->getMessage());
            $summary = ['count' => 0, 'total' => 0];
            $byCategory = [];
            $monthly = [];
            $expenses = [];
        }

        return view('reports.expenses', compact('company', 'summary', 'byCategory', 'monthly', 'expenses', 'from', 'to'));
    }

    public function reportBalance(Request $request, RequestContext $context, Database $db)
    {
        $company = $context->company();
        if (!$company) {
            return response('Company not found', 404);
        }

        $companyId = (int) $company['company_id'];
        $pdo = $db->pdo();
        $asOf = $request->query('as_of', date('Y-m-d'));

        try {
            // Assets: cash collected (payments received)
            $stmt = $pdo->prepare(
                'SELECT COALESCE(SUM(p.amount), 0) AS cash
                 FROM payments p
                 INNER JOIN invoices i ON i.invoice_id = p.invoice_id
                 WHERE i.company_id = :cid AND p.payment_date <= :asof'
            );
            $stmt->execute(['cid' => $companyId, 'asof' => $asOf]);
            $cashReceived = (float) ($stmt->fetchColumn() ?: 0);

            // Assets: accounts receivable (invoiced - paid)
            $stmt = $pdo->prepare(
                'SELECT COALESCE(SUM(' . SchemaCompat::invoiceAmountSql() . '), 0) AS invoiced
                 FROM invoices WHERE company_id = :cid AND issue_date <= :asof'
            );
            $stmt->execute(['cid' => $companyId, 'asof' => $asOf]);
            $totalInvoiced = (float) ($stmt->fetchColumn() ?: 0);
            $accountsReceivable = max(0, $totalInvoiced - $cashReceived);

            // Assets: inventory value
            $stockQtyColumn = SchemaCompat::productStockQtyColumn() ?? '0';
            $priceColumn = SchemaCompat::productPriceColumn();
            $stmt = $pdo->prepare(
                'SELECT COALESCE(SUM(' . $stockQtyColumn . ' * ' . $priceColumn . '), 0) AS inv_value
                 FROM products WHERE company_id = :cid'
            );
            $stmt->execute(['cid' => $companyId]);
            $inventoryValue = (float) ($stmt->fetchColumn() ?: 0);

            // Credit outstanding (liabilities / loans issued)
            $stmt = $pdo->prepare(
                'SELECT COALESCE(SUM(GREATEST(total_amount - amount_paid, 0)), 0) AS credit_out
                 FROM credits WHERE company_id = :cid AND status = "ACTIVE"'
            );
            $stmt->execute(['cid' => $companyId]);
            $creditsOutstanding = (float) ($stmt->fetchColumn() ?: 0);

            // Total expenses to date
            $stmt = $pdo->prepare(
                'SELECT COALESCE(SUM(amount), 0) AS total_exp
                 FROM expenses WHERE company_id = :cid AND date <= :asof'
            );
            $stmt->execute(['cid' => $companyId, 'asof' => $asOf]);
            $totalExpenses = (float) ($stmt->fetchColumn() ?: 0);

            // Journal entries summary
            $stmt = $pdo->prepare(
                'SELECT COALESCE(SUM(debit_amount), 0) AS total_debit,
                        COALESCE(SUM(credit_amount), 0) AS total_credit
                 FROM journal_entries WHERE company_id = :cid AND date <= :asof'
            );
            $stmt->execute(['cid' => $companyId, 'asof' => $asOf]);
            $journal = $stmt->fetch(\PDO::FETCH_ASSOC) ?: ['total_debit' => 0, 'total_credit' => 0];
        } catch (\Throwable $e) {
            error_log("reportBalance error: " . $e->getMessage());
            $cashReceived = 0;
            $accountsReceivable = 0;
            $inventoryValue = 0;
            $creditsOutstanding = 0;
            $totalExpenses = 0;
            $totalInvoiced = 0;
            $journal = ['total_debit' => 0, 'total_credit' => 0];
        }

        $totalAssets = $cashReceived + $accountsReceivable + $inventoryValue + $creditsOutstanding;
        $retainedEarnings = $cashReceived - $totalExpenses;

        $balance = [
            'assets' => [
                'Cash Received' => $cashReceived,
                'Accounts Receivable' => $accountsReceivable,
                'Inventory' => $inventoryValue,
                'Credit Facilities (Owed to Company)' => $creditsOutstanding,
            ],
            'total_assets' => $totalAssets,
            'liabilities' => [
                'Operating Expenses' => $totalExpenses,
            ],
            'total_liabilities' => $totalExpenses,
            'equity' => [
                'Retained Earnings (Cash − Expenses)' => $retainedEarnings,
            ],
            'total_equity' => $retainedEarnings,
            'journal_debits' => (float) $journal['total_debit'],
            'journal_credits' => (float) $journal['total_credit'],
        ];

        return view('reports.balance', compact('company', 'balance', 'asOf'));
    }

    public function setup(Request $request, RequestContext $context)
    {
        $company = $context->company();
        if (!$company) {
            return response('Company not found', 404);
        }

        return view('operations.setup', [
            'company' => $company,
        ]);
    }

    public function setupHealthCheck(Request $request, RequestContext $context, Database $db)
    {
        $company = $context->company();
        if (!$company) {
            return response('Company not found', 404);
        }

        $checks = [
            'database' => $this->checkDatabase($db),
            'tables' => $this->checkTables($db),
            'permissions' => $this->checkPermissions($db, $company),
            'mail' => $this->checkMailTransport(),
        ];

        return response()->json($checks);
    }

    public function setupSendTestEmail(Request $request, RequestContext $context)
    {
        $company = $context->company();
        if (!$company) {
            return response()->json([
                'sent' => false,
                'error' => 'Company not found',
            ], 404);
        }

        $to = (string) config('mail.from.address', '');
        if ($to === '') {
            return response()->json([
                'sent' => false,
                'error' => 'MAIL_FROM_ADDRESS is not configured.',
            ], 422);
        }

        $subject = 'Skyare SMTP Test Email';
        $body = "This is a test email from Skyare setup at " . now()->toDateTimeString() . ".";

        try {
            Mail::raw($body, function ($message) use ($to, $subject) {
                $message->to($to)->subject($subject);
            });

            return response()->json([
                'sent' => true,
                'to' => $to,
                'mailer' => (string) config('mail.default', 'smtp'),
                'error' => null,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'sent' => false,
                'to' => $to,
                'mailer' => (string) config('mail.default', 'smtp'),
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function creditManagement(Request $request, RequestContext $context, Database $db)
    {
        $company = $context->company();
        if (!$company) {
            return response('Company not found', 404);
        }

        $companyId = (int) $company['company_id'];
        $creditModel = new Credit($db->pdo());
        $customerModel = new Customer($db->pdo());

        try {
            $creditModel->reconcileByCompany($companyId);
            $credits = $creditModel->listByCompany($companyId);
            $recentPayments = $creditModel->paymentListByCompany($companyId);
            $customers = $customerModel->listByCompany($companyId, 500);
        } catch (\Throwable $e) {
            error_log('creditManagement error: ' . $e->getMessage());
            return redirect('/dashboard')->withErrors(['credit' => 'Unable to load credit/loan management right now.']);
        }

        return view('operations.credit-management', [
            'company' => $company,
            'credits' => $credits,
            'customers' => $customers,
            'recentPayments' => $recentPayments,
        ]);
    }

    public function creditIssue(Request $request, RequestContext $context, Database $db)
    {
        $company = $context->company();
        if (!$company) {
            return response('Company not found', 404);
        }

        $validated = $request->validate([
            'customer_id' => 'nullable|integer|min:1',
            'customer_name' => 'nullable|string|max:191',
            'amount' => 'required|numeric|min:0.01',
            'interest_type' => 'required|in:flat,monthly,daily',
            'interest_percent' => 'required|numeric|min:0|max:500',
            'reason' => 'nullable|string|max:500',
            'due_date' => 'nullable|date',
        ]);

        $companyId = (int) $company['company_id'];
        $customerModel = new Customer($db->pdo());
        $creditModel = new Credit($db->pdo());

        $customerId = (int) ($validated['customer_id'] ?? 0);
        $customerName = trim((string) ($validated['customer_name'] ?? ''));

        if ($customerId > 0) {
            $customer = $customerModel->findByIdForCompany($customerId, $companyId);
            if ($customer === null) {
                return redirect('/credit-management')->withErrors(['customer_id' => 'Selected customer was not found.']);
            }
            $customerName = (string) ($customer['customer_name'] ?? $customerName);
        }

        if ($customerName === '') {
            return redirect('/credit-management')->withErrors(['customer_name' => 'Customer name is required.']);
        }

        if ($customerId <= 0) {
            $existing = $customerModel->findByNameForCompany($customerName, $companyId);
            if ($existing !== null) {
                $customerId = (int) ($existing['customer_id'] ?? 0);
            }
        }

        try {
            $creditModel->create(
                $companyId,
                $customerId,
                $customerName,
                (float) $validated['amount'],
                (string) $validated['interest_type'],
                (float) $validated['interest_percent'],
                (string) ($validated['reason'] ?? ''),
                $validated['due_date'] ?? null
            );
        } catch (\Throwable $e) {
            error_log('creditIssue error: ' . $e->getMessage());
            return redirect('/credit-management')->withErrors(['credit_issue' => 'Could not issue credit facility with the current database schema.']);
        }

        return redirect('/credit-management')->with('success', 'Credit facility issued successfully.');
    }

    public function creditPayment(Request $request, RequestContext $context, Database $db)
    {
        $company = $context->company();
        if (!$company) {
            return response('Company not found', 404);
        }

        $validated = $request->validate([
            'credit_id' => 'required|integer|min:1',
            'amount' => 'required|numeric|min:0.01',
            'payment_date' => 'nullable|date',
            'payment_method' => 'nullable|string|max:50',
            'reference' => 'nullable|string|max:191',
        ]);

        $creditModel = new Credit($db->pdo());
        try {
            $ok = $creditModel->recordPayment(
                (int) $company['company_id'],
                (int) $validated['credit_id'],
                (float) $validated['amount'],
                (string) ($validated['payment_date'] ?? now()->toDateString()),
                (string) ($validated['payment_method'] ?? 'bank_transfer'),
                (string) ($validated['reference'] ?? '')
            );
        } catch (\Throwable $e) {
            error_log('creditPayment error: ' . $e->getMessage());
            $ok = false;
        }

        if (!$ok) {
            return redirect('/credit-management')->withErrors([
                'credit_payment' => 'Payment could not be applied. Ensure the amount does not exceed the outstanding balance and the facility is active.',
            ]);
        }

        return redirect('/credit-management')->with('success', 'Credit payment recorded.');
    }

    public function creditWriteOff(Request $request, RequestContext $context, Database $db)
    {
        $company = $context->company();
        if (!$company) {
            return response('Company not found', 404);
        }

        $validated = $request->validate([
            'credit_id' => 'required|integer|min:1',
            'reason' => 'required|string|max:500',
        ]);

        $user = $_SESSION['user'] ?? [];
        $actor = (string) ($user['full_name'] ?? $user['email'] ?? 'System');

        $creditModel = new Credit($db->pdo());
        try {
            $ok = $creditModel->writeOff(
                (int) $company['company_id'],
                (int) $validated['credit_id'],
                (string) $validated['reason'],
                $actor
            );
        } catch (\Throwable $e) {
            error_log('creditWriteOff error: ' . $e->getMessage());
            $ok = false;
        }

        if (!$ok) {
            return redirect('/credit-management')->withErrors(['credit_write_off' => 'Unable to write off this credit facility.']);
        }

        return redirect('/credit-management')->with('success', 'Credit facility written off.');
    }

    public function creditReopen(Request $request, RequestContext $context, Database $db)
    {
        $company = $context->company();
        if (!$company) {
            return response('Company not found', 404);
        }

        $validated = $request->validate([
            'credit_id' => 'required|integer|min:1',
        ]);

        $creditModel = new Credit($db->pdo());
        try {
            $ok = $creditModel->reopen((int) $company['company_id'], (int) $validated['credit_id']);
        } catch (\Throwable $e) {
            error_log('creditReopen error: ' . $e->getMessage());
            $ok = false;
        }

        if (!$ok) {
            return redirect('/credit-management')->withErrors(['credit_reopen' => 'Unable to reopen this credit facility.']);
        }

        return redirect('/credit-management')->with('success', 'Credit facility reopened.');
    }

    public function creditView(Request $request, RequestContext $context, Database $db)
    {
        $company = $context->company();
        if (!$company) {
            return response('Company not found', 404);
        }

        $creditId = (int) $request->query('credit_id', 0);
        if ($creditId <= 0) {
            return redirect('/credit-management')->withErrors(['credit_id' => 'Credit facility not specified.']);
        }

        $creditModel = new Credit($db->pdo());
        try {
            $credit = $creditModel->findAgreementByIdForCompany($creditId, (int) $company['company_id']);
        } catch (\Throwable $e) {
            error_log('creditView find error: ' . $e->getMessage());
            $credit = null;
        }
        if ($credit === null) {
            return redirect('/credit-management')->withErrors(['credit_id' => 'Credit facility not found.']);
        }

        try {
            $payments = $creditModel->paymentListByCreditForCompany((int) $company['company_id'], $creditId);
        } catch (\Throwable $e) {
            error_log('creditView payments error: ' . $e->getMessage());
            $payments = [];
        }

        return view('operations.credit-view', [
            'company' => $company,
            'credit' => $credit,
            'payments' => $payments,
        ]);
    }

    public function creditAgreement(Request $request, RequestContext $context, Database $db)
    {
        $company = $context->company();
        if (!$company) {
            return response('Company not found', 404);
        }

        $creditId = (int) $request->query('credit_id', 0);
        if ($creditId <= 0) {
            return redirect('/credit-management')->withErrors(['credit_id' => 'Credit facility not specified.']);
        }

        $creditModel = new Credit($db->pdo());
        try {
            $credit = $creditModel->findAgreementByIdForCompany($creditId, (int) $company['company_id']);
        } catch (\Throwable $e) {
            error_log('creditAgreement find error: ' . $e->getMessage());
            $credit = null;
        }
        if ($credit === null) {
            return redirect('/credit-management')->withErrors(['credit_id' => 'Credit facility not found.']);
        }

        try {
            $payments = $creditModel->paymentListByCreditForCompany((int) $company['company_id'], $creditId);
        } catch (\Throwable $e) {
            error_log('creditAgreement payments error: ' . $e->getMessage());
            $payments = [];
        }

        if ((int) $request->query('download', 0) === 1) {
            $pdf = Pdf::loadView('operations.credit-agreement-pdf', [
                'company' => $company,
                'credit' => $credit,
                'payments' => $payments,
            ]);

            return $pdf->download(($credit['credit_no'] ?? 'credit-agreement') . '.pdf');
        }

        return view('operations.credit-agreement', [
            'company' => $company,
            'credit' => $credit,
            'payments' => $payments,
        ]);
    }

    private function checkDatabase(Database $db): bool
    {
        try {
            $db->pdo()->query('SELECT 1');
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    private function checkTables(Database $db): array
    {
        $requiredTables = ['users', 'companies', 'invoices', 'clients', 'products'];
        $found = [];
        $missing = [];

        try {
            $pdo = $db->pdo();
            $stmt = $pdo->query("SHOW TABLES");
            $tables = $stmt->fetchAll(\PDO::FETCH_COLUMN);

            foreach ($requiredTables as $table) {
                if (in_array($table, $tables)) {
                    $found[] = $table;
                } else {
                    $missing[] = $table;
                }
            }
        } catch (\Exception $e) {
            $missing = $requiredTables;
        }

        return ['found' => $found, 'missing' => $missing];
    }

    private function checkPermissions(Database $db, array $company): bool
    {
        // Check if current user has necessary permissions
        return isset($_SESSION['user']);
    }

    private function checkMailTransport(): array
    {
        $host = (string) config('mail.mailers.smtp.host', '');
        $port = (int) config('mail.mailers.smtp.port', 0);
        $encryption = (string) config('mail.mailers.smtp.encryption', '');
        $username = (string) config('mail.mailers.smtp.username', '');

        if ($host === '' || $port <= 0) {
            return [
                'configured' => false,
                'reachable' => false,
                'host' => $host,
                'port' => $port,
                'encryption' => $encryption,
                'username_set' => $username !== '',
                'error' => 'SMTP host or port is not configured.',
            ];
        }

        $target = $encryption === 'ssl' ? "ssl://{$host}" : $host;
        $errno = 0;
        $errstr = '';

        $socket = @fsockopen($target, $port, $errno, $errstr, 5.0);

        if (!is_resource($socket)) {
            return [
                'configured' => true,
                'reachable' => false,
                'host' => $host,
                'port' => $port,
                'encryption' => $encryption,
                'username_set' => $username !== '',
                'error' => trim("{$errno} {$errstr}"),
            ];
        }

        fclose($socket);

        return [
            'configured' => true,
            'reachable' => true,
            'host' => $host,
            'port' => $port,
            'encryption' => $encryption,
            'username_set' => $username !== '',
            'error' => null,
        ];
    }
}
