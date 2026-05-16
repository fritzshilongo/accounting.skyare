<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Core\Database;
use App\Core\RequestContext;
use App\Support\SchemaCompat;

class ApiController extends Controller
{
    protected function getCompanyId(RequestContext $context): ?int
    {
        $company = $context->company();
        return $company ? (int) $company['company_id'] : null;
    }

    protected function unauthorized()
    {
        return response()->json(['error' => 'Unauthorized'], 401);
    }

    protected function notFound(string $entity = 'Resource')
    {
        return response()->json(['error' => "{$entity} not found"], 404);
    }

    private function dbError(string $msg = 'Database error'): \Illuminate\Http\JsonResponse
    {
        return response()->json(['error' => $msg], 500);
    }

    // ── CLIENTS ──

    public function clientsIndex(RequestContext $context, Database $db)
    {
        $companyId = $this->getCompanyId($context);
        if (!$companyId) return $this->unauthorized();

        try {
            $stmt = $db->pdo()->prepare('SELECT * FROM clients WHERE company_id = :cid ORDER BY name');
            $stmt->execute(['cid' => $companyId]);
            return response()->json(['data' => $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: []]);
        } catch (\Throwable $e) {
            return $this->dbError();
        }
    }

    public function clientsShow($id, RequestContext $context, Database $db)
    {
        $companyId = $this->getCompanyId($context);
        if (!$companyId) return $this->unauthorized();

        try {
            $stmt = $db->pdo()->prepare('SELECT * FROM clients WHERE client_id = :id AND company_id = :cid LIMIT 1');
            $stmt->execute(['id' => (int) $id, 'cid' => $companyId]);
            $client = $stmt->fetch(\PDO::FETCH_ASSOC);
            return $client ? response()->json(['data' => $client]) : $this->notFound('Client');
        } catch (\Throwable $e) {
            return $this->dbError();
        }
    }

    public function clientsStore(Request $request, RequestContext $context, Database $db)
    {
        $companyId = $this->getCompanyId($context);
        if (!$companyId) return $this->unauthorized();

        $validated = $request->validate([
            'name' => 'required|string|max:191',
            'email' => 'nullable|email|max:191',
            'phone' => 'nullable|string|max:50',
            'address' => 'nullable|string|max:500',
        ]);

        try {
            $db->pdo()->prepare(
                'INSERT INTO clients (company_id, name, email, phone, address, created_at, updated_at) VALUES (:cid, :name, :email, :phone, :address, NOW(), NOW())'
            )->execute([
                'cid' => $companyId,
                'name' => $validated['name'],
                'email' => $validated['email'] ?? null,
                'phone' => $validated['phone'] ?? null,
                'address' => $validated['address'] ?? null,
            ]);

            $id = $db->pdo()->lastInsertId();
            return response()->json(['data' => ['client_id' => (int) $id] + $validated, 'message' => 'Client created'], 201);
        } catch (\Throwable $e) {
            return $this->dbError('Could not create client');
        }
    }

    // ── PRODUCTS ──

    public function productsIndex(RequestContext $context, Database $db)
    {
        $companyId = $this->getCompanyId($context);
        if (!$companyId) return $this->unauthorized();

        try {
            $nameCol = SchemaCompat::productNameColumn();
            $hasCompanyId = SchemaCompat::hasColumn('products', 'company_id');

            $sql = 'SELECT * FROM products';
            $params = [];
            if ($hasCompanyId) {
                $sql .= ' WHERE company_id = :cid';
                $params['cid'] = $companyId;
            }
            $sql .= ' ORDER BY ' . $nameCol;

            $stmt = $db->pdo()->prepare($sql);
            $stmt->execute($params);
            return response()->json(['data' => $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: []]);
        } catch (\Throwable $e) {
            return $this->dbError();
        }
    }

    public function productsShow($id, RequestContext $context, Database $db)
    {
        $companyId = $this->getCompanyId($context);
        if (!$companyId) return $this->unauthorized();

        try {
            $hasCompanyId = SchemaCompat::hasColumn('products', 'company_id');
            $sql = 'SELECT * FROM products WHERE product_id = :id';
            $params = ['id' => (int) $id];
            if ($hasCompanyId) {
                $sql .= ' AND company_id = :cid';
                $params['cid'] = $companyId;
            }
            $sql .= ' LIMIT 1';

            $stmt = $db->pdo()->prepare($sql);
            $stmt->execute($params);
            $product = $stmt->fetch(\PDO::FETCH_ASSOC);
            return $product ? response()->json(['data' => $product]) : $this->notFound('Product');
        } catch (\Throwable $e) {
            return $this->dbError();
        }
    }

    public function productsStore(Request $request, RequestContext $context, Database $db)
    {
        $companyId = $this->getCompanyId($context);
        if (!$companyId) return $this->unauthorized();

        $validated = $request->validate([
            'name' => 'required|string|max:191',
            'price' => 'required|numeric|min:0',
            'sku' => 'nullable|string|max:50',
            'stock_qty' => 'nullable|integer|min:0',
            'description' => 'nullable|string|max:500',
        ]);

        try {
            $nameCol = SchemaCompat::productNameColumn();
            $priceCol = SchemaCompat::productPriceColumn();

            $columns = [];
            $params = [];
            $push = static function (string $column, mixed $value) use (&$columns, &$params): void {
                if (!SchemaCompat::hasColumn('products', $column)) {
                    return;
                }
                $columns[] = $column;
                $params[$column] = $value;
            };

            $push('company_id', $companyId);
            $push($nameCol, $validated['name']);
            $push($priceCol, $validated['price']);
            $push('sku', $validated['sku'] ?? null);
            $push('stock_qty', $validated['stock_qty'] ?? 0);
            $push('description', $validated['description'] ?? null);
            $push('created_at', now()->toDateTimeString());
            $push('updated_at', now()->toDateTimeString());

            if ($columns === []) {
                return $this->dbError('Products table is missing required columns');
            }

            $placeholders = array_map(static fn (string $column): string => ':' . $column, $columns);
            $db->pdo()->prepare(
                'INSERT INTO products (' . implode(', ', $columns) . ') VALUES (' . implode(', ', $placeholders) . ')'
            )->execute($params);

            $id = $db->pdo()->lastInsertId();
            return response()->json(['data' => ['product_id' => (int) $id] + $validated, 'message' => 'Product created'], 201);
        } catch (\Throwable $e) {
            return $this->dbError('Could not create product');
        }
    }

    // ── INVOICES ──

    public function invoicesIndex(Request $request, RequestContext $context, Database $db)
    {
        $companyId = $this->getCompanyId($context);
        if (!$companyId) return $this->unauthorized();

        $status = $request->query('status');
        $sql = 'SELECT * FROM invoices WHERE company_id = :cid';
        $params = ['cid' => $companyId];

        if ($status) {
            $sql .= ' AND status = :status';
            $params['status'] = $status;
        }
        $sql .= ' ORDER BY created_at DESC';

        try {
            $stmt = $db->pdo()->prepare($sql);
            $stmt->execute($params);
            return response()->json(['data' => $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: []]);
        } catch (\Throwable $e) {
            return $this->dbError();
        }
    }

    public function invoicesShow($id, RequestContext $context, Database $db)
    {
        $companyId = $this->getCompanyId($context);
        if (!$companyId) return $this->unauthorized();

        try {
            $stmt = $db->pdo()->prepare('SELECT * FROM invoices WHERE invoice_id = :id AND company_id = :cid LIMIT 1');
            $stmt->execute(['id' => (int) $id, 'cid' => $companyId]);
            $invoice = $stmt->fetch(\PDO::FETCH_ASSOC);
            if (!$invoice) return $this->notFound('Invoice');

            // Include items
            $itemStmt = $db->pdo()->prepare('SELECT * FROM invoice_items WHERE invoice_id = :id');
            $itemStmt->execute(['id' => (int) $id]);
            $invoice['items'] = $itemStmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];

            return response()->json(['data' => $invoice]);
        } catch (\Throwable $e) {
            return $this->dbError();
        }
    }

    // ── PAYMENTS ──

    public function paymentsIndex(RequestContext $context, Database $db)
    {
        $companyId = $this->getCompanyId($context);
        if (!$companyId) return $this->unauthorized();

        try {
            $stmt = $db->pdo()->prepare(
                'SELECT p.* FROM payments p INNER JOIN invoices i ON p.invoice_id = i.invoice_id WHERE i.company_id = :cid ORDER BY p.created_at DESC'
            );
            $stmt->execute(['cid' => $companyId]);
            return response()->json(['data' => $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: []]);
        } catch (\Throwable $e) {
            return $this->dbError();
        }
    }

    // ── EXPENSES ──

    public function expensesIndex(RequestContext $context, Database $db)
    {
        $companyId = $this->getCompanyId($context);
        if (!$companyId) return $this->unauthorized();

        try {
            $stmt = $db->pdo()->prepare('SELECT * FROM expenses WHERE company_id = :cid ORDER BY date DESC');
            $stmt->execute(['cid' => $companyId]);
            return response()->json(['data' => $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: []]);
        } catch (\Throwable $e) {
            return $this->dbError();
        }
    }

    // ── DASHBOARD STATS ──

    public function dashboardStats(RequestContext $context, Database $db)
    {
        $companyId = $this->getCompanyId($context);
        if (!$companyId) return $this->unauthorized();

        $pdo = $db->pdo();
        $stats = [
            'total_invoices' => 0,
            'total_revenue' => 0.0,
            'outstanding_invoices' => 0,
            'outstanding_amount' => 0.0,
            'total_clients' => 0,
            'total_products' => 0,
        ];

        try {
            $stmt = $pdo->prepare('SELECT COUNT(*) as count, COALESCE(SUM(' . SchemaCompat::invoiceAmountSql() . '), 0) as revenue FROM invoices WHERE company_id = ?');
            $stmt->execute([$companyId]);
            $r = $stmt->fetch(\PDO::FETCH_ASSOC);
            $stats['total_invoices'] = (int) ($r['count'] ?? 0);
            $stats['total_revenue'] = (float) ($r['revenue'] ?? 0);
        } catch (\Throwable $e) {}

        try {
            $statusFilter = SchemaCompat::hasColumn('invoices', 'status') ? ' AND status != ?' : '';
            $stmt = $pdo->prepare('SELECT COUNT(*) as count, COALESCE(SUM(' . SchemaCompat::invoiceAmountSql() . '), 0) as amount FROM invoices WHERE company_id = ?' . $statusFilter);
            $params = [$companyId];
            if ($statusFilter !== '') {
                $params[] = 'paid';
            }
            $stmt->execute($params);
            $r = $stmt->fetch(\PDO::FETCH_ASSOC);
            $stats['outstanding_invoices'] = (int) ($r['count'] ?? 0);
            $stats['outstanding_amount'] = (float) ($r['amount'] ?? 0);
        } catch (\Throwable $e) {}

        try {
            if (SchemaCompat::hasColumn('clients', 'company_id')) {
                $stmt = $pdo->prepare('SELECT COUNT(*) as count FROM clients WHERE company_id = ?');
                $stmt->execute([$companyId]);
            } else {
                $stmt = $pdo->query('SELECT COUNT(*) as count FROM clients');
            }
            $stats['total_clients'] = (int) ($stmt->fetch(\PDO::FETCH_ASSOC)['count'] ?? 0);
        } catch (\Throwable $e) {}

        try {
            if (SchemaCompat::hasColumn('products', 'company_id')) {
                $stmt = $pdo->prepare('SELECT COUNT(*) as count FROM products WHERE company_id = ?');
                $stmt->execute([$companyId]);
            } else {
                $stmt = $pdo->query('SELECT COUNT(*) as count FROM products');
            }
            $stats['total_products'] = (int) ($stmt->fetch(\PDO::FETCH_ASSOC)['count'] ?? 0);
        } catch (\Throwable $e) {}

        return response()->json(['data' => $stats]);
    }
}
