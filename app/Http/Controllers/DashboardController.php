<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Core\Database;
use App\Core\RequestContext;
use App\Models\User;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Client;
use App\Models\Product;
use App\Support\SchemaCompat;

class DashboardController extends Controller
{
    public function index(Request $request, Database $db, RequestContext $context)
    {
        $company = $context->company();
        if (!$company) {
            return response('Company not found', 404);
        }

        $user = $this->getAuthenticatedUser($request, $db, $company);
        if (!$user) {
            return redirect('/login');
        }

        $companyId = (int) $company['company_id'];

        // Calculate dashboard statistics
        $stats = [
            'total_invoices' => 0,
            'total_revenue' => 0,
            'outstanding_invoices' => 0,
            'outstanding_amount' => 0,
            'amount_paid' => 0,
            'overdue_invoices' => 0,
            'overdue_amount' => 0,
            'total_clients' => 0,
            'total_products' => 0,
            'recent_invoices' => [],
            'recent_payments' => [],
            'invoice_reminders' => [],
            'monthly_revenue' => [],
            'monthly_expenses' => [],
            'invoice_status_counts' => [],
            'top_clients' => [],
        ];

        try {
            $pdo = $db->pdo();
            $invoiceAmountSql = SchemaCompat::invoiceAmountSql();
            $invoiceNoColumn = SchemaCompat::invoiceNoColumn();
            $invoiceClientNameColumn = SchemaCompat::invoiceClientNameColumn();

            // Total invoices and billed value
            $stmt = $pdo->prepare('SELECT COUNT(*) as count, COALESCE(SUM(' . $invoiceAmountSql . '), 0) as billed FROM invoices WHERE company_id = ?');
            $stmt->execute([$companyId]);
            $result = $stmt->fetch(\PDO::FETCH_ASSOC);
            if ($result) {
                $stats['total_invoices'] = (int) $result['count'];
            }

            // Revenue received (payments actually collected)
            $stmt = $pdo->prepare(
                'SELECT COALESCE(SUM(p.amount), 0) AS revenue
                 FROM payments p
                 INNER JOIN invoices i ON p.invoice_id = i.invoice_id
                 WHERE i.company_id = ?'
            );
            $stmt->execute([$companyId]);
            $result = $stmt->fetch(\PDO::FETCH_ASSOC);
            if ($result) {
                $stats['total_revenue'] = (float) ($result['revenue'] ?? 0);
            }

            // Outstanding invoices and amount (remaining balance after payments)
            $stmt = $pdo->prepare(
                'SELECT
                    COUNT(*) AS count,
                    COALESCE(SUM(GREATEST(0, inv_total - paid_total)), 0) AS amount,
                    COALESCE(SUM(LEAST(inv_total, paid_total)), 0) AS amount_paid,
                    COALESCE(SUM(CASE WHEN due_date IS NOT NULL AND due_date < CURDATE() AND GREATEST(0, inv_total - paid_total) > 0 THEN 1 ELSE 0 END), 0) AS overdue_count,
                    COALESCE(SUM(CASE WHEN due_date IS NOT NULL AND due_date < CURDATE() THEN GREATEST(0, inv_total - paid_total) ELSE 0 END), 0) AS overdue_amount
                 FROM (
                    SELECT
                        i.invoice_id,
                        i.status,
                        i.due_date,
                        COALESCE(' . $invoiceAmountSql . ', 0) AS inv_total,
                        COALESCE((SELECT SUM(p.amount) FROM payments p WHERE p.invoice_id = i.invoice_id), 0) AS paid_total
                    FROM invoices i
                    WHERE i.company_id = :cid
                      AND COALESCE(i.status, "") NOT IN ("cancelled", "paid", "finalised", "finalized")
                 ) x'
            );
            $stmt->execute(['cid' => $companyId]);
            $result = $stmt->fetch(\PDO::FETCH_ASSOC);
            if ($result) {
                $stats['outstanding_invoices'] = (int) $result['count'];
                $stats['outstanding_amount'] = (float) $result['amount'];
                $stats['amount_paid'] = (float) ($result['amount_paid'] ?? 0);
                $stats['overdue_invoices'] = (int) ($result['overdue_count'] ?? 0);
                $stats['overdue_amount'] = (float) ($result['overdue_amount'] ?? 0);
            }

            // Invoice reminders due today/soon and overdue (auto-clears when fully paid/finalised)
            $stmt = $pdo->prepare(
                'SELECT
                    i.invoice_id,
                    ' . ($invoiceNoColumn !== null ? $invoiceNoColumn : "''") . ' AS invoice_no,
                    ' . ($invoiceClientNameColumn !== null ? $invoiceClientNameColumn : "''") . ' AS client_name,
                    i.status,
                    i.due_date,
                    COALESCE(' . $invoiceAmountSql . ', 0) AS total,
                    COALESCE((SELECT SUM(p.amount) FROM payments p WHERE p.invoice_id = i.invoice_id), 0) AS paid,
                    GREATEST(0, COALESCE(' . $invoiceAmountSql . ', 0) - COALESCE((SELECT SUM(p.amount) FROM payments p WHERE p.invoice_id = i.invoice_id), 0)) AS balance,
                    CASE
                        WHEN i.due_date < CURDATE() THEN "overdue"
                        WHEN i.due_date = CURDATE() THEN "due_today"
                        ELSE "due_soon"
                    END AS reminder_type
                 FROM invoices i
                 WHERE i.company_id = :cid
                   AND i.due_date IS NOT NULL
                   AND i.due_date <= DATE_ADD(CURDATE(), INTERVAL 7 DAY)
                   AND COALESCE(i.status, "") NOT IN ("cancelled", "paid", "finalised", "finalized")
                   AND GREATEST(0, COALESCE(' . $invoiceAmountSql . ', 0) - COALESCE((SELECT SUM(p.amount) FROM payments p WHERE p.invoice_id = i.invoice_id), 0)) > 0
                 ORDER BY i.due_date ASC, i.invoice_id DESC
                 LIMIT 10'
            );
            $stmt->execute(['cid' => $companyId]);
            $stats['invoice_reminders'] = $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];

            // Counts
            $stmt = $pdo->prepare('SELECT COUNT(*) as count FROM clients WHERE company_id = ?');
            $stmt->execute([$companyId]);
            $result = $stmt->fetch(\PDO::FETCH_ASSOC);
            $stats['total_clients'] = (int) ($result['count'] ?? 0);

            $stmt = $pdo->prepare('SELECT COUNT(*) as count FROM products WHERE company_id = ?');
            $stmt->execute([$companyId]);
            $result = $stmt->fetch(\PDO::FETCH_ASSOC);
            $stats['total_products'] = (int) ($result['count'] ?? 0);

            // Recent invoices
            $recentColumns = ['invoice_id'];
            if ($invoiceNoColumn !== null) {
                $recentColumns[] = $invoiceNoColumn . ' AS invoice_no';
            } else {
                $recentColumns[] = "'' AS invoice_no";
            }
            if ($invoiceClientNameColumn !== null) {
                $recentColumns[] = $invoiceClientNameColumn . ' AS client_name';
            } else {
                $recentColumns[] = "'' AS client_name";
            }
            $recentColumns[] = $invoiceAmountSql . ' AS total';
            $recentColumns[] = 'status';
            $recentColumns[] = 'issue_date';
            $recentColumns[] = 'created_at';
            $stmt = $pdo->prepare('SELECT ' . implode(', ', $recentColumns) . ' FROM invoices WHERE company_id = ? ORDER BY created_at DESC LIMIT 5');
            $stmt->execute([$companyId]);
            $stats['recent_invoices'] = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            // Recent payments (via invoices – payments table has no company_id)
            $stmt = $pdo->prepare(
                'SELECT p.payment_id, p.amount, p.method, p.payment_date, p.invoice_id,
                        i.invoice_no, i.client_name
                 FROM payments p
                 INNER JOIN invoices i ON p.invoice_id = i.invoice_id
                 WHERE i.company_id = ?
                 ORDER BY p.payment_date DESC, p.created_at DESC
                 LIMIT 5'
            );
            $stmt->execute([$companyId]);
            $stats['recent_payments'] = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            // Monthly revenue (last 12 months)
            try {
                $stmt = $pdo->prepare(
                    "SELECT DATE_FORMAT(COALESCE(issue_date, created_at), '%Y-%m') AS month, COALESCE(SUM(" . $invoiceAmountSql . "), 0) AS total
                    FROM invoices WHERE company_id = ? AND COALESCE(issue_date, created_at) >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
                     GROUP BY DATE_FORMAT(COALESCE(issue_date, created_at), '%Y-%m') ORDER BY month ASC"
                );
                $stmt->execute([$companyId]);
                $stats['monthly_revenue'] = $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];
            } catch (\Throwable $e) {}

            // Monthly expenses (last 12 months)
            try {
                $stmt = $pdo->prepare(
                    "SELECT DATE_FORMAT(date, '%Y-%m') AS month, COALESCE(SUM(amount), 0) AS total
                     FROM expenses WHERE company_id = ? AND date >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
                     GROUP BY DATE_FORMAT(date, '%Y-%m') ORDER BY month ASC"
                );
                $stmt->execute([$companyId]);
                $stats['monthly_expenses'] = $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];
            } catch (\Throwable $e) {}

            // Invoice status breakdown
            try {
                $stmt = $pdo->prepare('SELECT CASE WHEN status IN ("partial") THEN "partial_paid" WHEN status IN ("finalized") THEN "finalised" ELSE status END AS status, COUNT(*) AS count FROM invoices WHERE company_id = ? GROUP BY CASE WHEN status IN ("partial") THEN "partial_paid" WHEN status IN ("finalized") THEN "finalised" ELSE status END');
                $stmt->execute([$companyId]);
                $stats['invoice_status_counts'] = $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];
            } catch (\Throwable $e) {}

            // Top 5 clients by revenue
            try {
                if ($invoiceClientNameColumn !== null) {
                    $stmt = $pdo->prepare(
                        'SELECT ' . $invoiceClientNameColumn . ' AS client_name, COALESCE(SUM(' . $invoiceAmountSql . '), 0) AS total_revenue, COUNT(*) AS invoice_count
                         FROM invoices WHERE company_id = ? AND ' . $invoiceClientNameColumn . ' IS NOT NULL
                         GROUP BY ' . $invoiceClientNameColumn . ' ORDER BY total_revenue DESC LIMIT 5'
                    );
                    $stmt->execute([$companyId]);
                    $stats['top_clients'] = $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];
                }
            } catch (\Throwable $e) {}
        } catch (\Exception $e) {
            error_log("Dashboard stats error: " . $e->getMessage());
        }

        return view('dashboard.index', [
            'company' => $company,
            'user' => $user,
            'stats' => $stats,
            'isIssuerHost' => $context->isLicenseIssuer(),
        ]);
    }

    public function checks(Request $request, Database $db, RequestContext $context)
    {
        $company = $context->company();
        if (!$company) {
            return response('Company not found', 404);
        }

        $checks = [
            'database_connection' => true,
            'permissions_table' => false,
            'license_status' => 'unknown',
            'required_tables' => [],
            'missing_tables' => [],
        ];

        try {
            $pdo = $db->pdo();
            $stmt = $pdo->query("SHOW TABLES");
            $tables = $stmt->fetchAll(\PDO::FETCH_COLUMN);

            $requiredTables = ['users', 'companies', 'clients', 'invoices', 'products', 'payments'];
            foreach ($requiredTables as $table) {
                if (in_array($table, $tables)) {
                    $checks['required_tables'][] = $table;
                } else {
                    $checks['missing_tables'][] = $table;
                }
            }

            $checks['permissions_table'] = in_array('role_permissions', $tables);

            return response()->json($checks);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    private function getAuthenticatedUser(Request $request, Database $db, array $company): ?array
    {
        if (!isset($_SESSION['user'])) {
            return null;
        }

        $userModel = new User($db->pdo());
        return $userModel->findById((int) $_SESSION['user']['user_id']);
    }
}
