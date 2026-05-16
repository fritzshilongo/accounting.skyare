<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Product;
use App\Core\RequestContext;
use App\Core\Database;

class ProductController extends Controller
{
    private static array $columnSupportCache = [];

    private function hasColumn(Database $db, string $column): bool
    {
        if (array_key_exists($column, self::$columnSupportCache)) {
            return self::$columnSupportCache[$column];
        }

        try {
            $stmt = $db->pdo()->query("SHOW COLUMNS FROM products LIKE '" . str_replace("'", "''", $column) . "'");
            self::$columnSupportCache[$column] = ($stmt && $stmt->rowCount() > 0);
        } catch (\Throwable $e) {
            self::$columnSupportCache[$column] = false;
        }

        return self::$columnSupportCache[$column];
    }

    private function supportsCompanyId(Database $db): bool
    {
        return $this->hasColumn($db, 'company_id');
    }

    private function companyScopedQuery(int $companyId, Database $db)
    {
        $query = Product::query();
        if ($this->supportsCompanyId($db)) {
            $query->where('company_id', $companyId);
        }
        return $query;
    }

    public function index(Request $request, RequestContext $context, Database $db)
    {
        $companyId = (int) ($context->company()['company_id'] ?? 0) ?: 1;
        $query = $this->companyScopedQuery($companyId, $db);
        $nameColumn = $this->hasColumn($db, 'name') ? 'name' : 'product_name';
        $priceColumn = $this->hasColumn($db, 'price') ? 'price' : 'unit_price';

        if ($request->filled('status') && $this->hasColumn($db, 'is_active')) {
            $status = $request->query('status');
            if (in_array($status, ['active', 'inactive'], true)) {
                $query->where('is_active', $status === 'active');
            }
        }

        if ($request->filled('search')) {
            $search = $request->query('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%");
            });

            if ($nameColumn !== 'name') {
                $query->orWhere($nameColumn, 'like', "%{$search}%");
            }
            if ($this->hasColumn($db, 'description')) {
                $query->orWhere('description', 'like', "%{$search}%");
            }
            if ($this->hasColumn($db, 'sku')) {
                $query->orWhere('sku', 'like', "%{$search}%");
            }
        }

        $products = $query->orderBy($nameColumn)->paginate(20)->withQueryString();

        return view('products.index', compact('products'));
    }

    public function create()
    {
        return view('products.create');
    }

    public function store(Request $request, RequestContext $context, Database $db)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'sku' => 'nullable|string|max:100',
            'type' => 'required|in:product,service',
            'stock_qty' => 'nullable|numeric|min:0',
        ]);

        $payload = [];

        $nameField = $this->hasColumn($db, 'name') ? 'name' : ($this->hasColumn($db, 'product_name') ? 'product_name' : 'name');
        $priceField = $this->hasColumn($db, 'price') ? 'price' : ($this->hasColumn($db, 'unit_price') ? 'unit_price' : 'price');

        $payload[$nameField] = $validated['name'];
        $payload[$priceField] = $validated['price'];

        if ($this->hasColumn($db, 'description')) {
            $payload['description'] = $validated['description'] ?? null;
        }
        if ($this->hasColumn($db, 'sku')) {
            $payload['sku'] = $validated['sku'] ?? null;
        }
        if ($this->hasColumn($db, 'type')) {
            $payload['type'] = $validated['type'];
        }
        if ($this->hasColumn($db, 'stock_control_type')) {
            $payload['stock_control_type'] = $validated['type'] === 'service' ? 'STOCK_NOT_CONTROLLED' : 'STOCK_CONTROLLED';
        }
        if ($this->hasColumn($db, 'stock_qty')) {
            $payload['stock_qty'] = $validated['type'] === 'service' ? 0 : (float) ($validated['stock_qty'] ?? 0);
        }
        if ($this->hasColumn($db, 'is_active')) {
            $payload['is_active'] = true;
        }

        if ($this->supportsCompanyId($db)) {
            $payload['company_id'] = (int) ($context->company()['company_id'] ?? 0) ?: 1;
        }

        Product::create($payload);

        return redirect('/products')->with('success', 'Product created successfully.');
    }

    public function show($id, RequestContext $context, Database $db)
    {
        $companyId = (int) ($context->company()['company_id'] ?? 0) ?: 1;
        $product = $this->companyScopedQuery($companyId, $db)->findOrFail($id);
        return view('products.show', compact('product'));
    }

    public function edit($id, RequestContext $context, Database $db)
    {
        $companyId = (int) ($context->company()['company_id'] ?? 0) ?: 1;
        $product = $this->companyScopedQuery($companyId, $db)->findOrFail($id);
        return view('products.edit', compact('product'));
    }

    public function update(Request $request, $id, RequestContext $context, Database $db)
    {
        $companyId = (int) ($context->company()['company_id'] ?? 0) ?: 1;
        $product = $this->companyScopedQuery($companyId, $db)->findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'sku' => 'nullable|string|max:100',
            'type' => 'required|in:product,service',
            'status' => 'required|in:active,inactive',
            'stock_qty' => 'nullable|numeric|min:0',
        ]);

        $payload = [];

        $nameField = $this->hasColumn($db, 'name') ? 'name' : ($this->hasColumn($db, 'product_name') ? 'product_name' : 'name');
        $priceField = $this->hasColumn($db, 'price') ? 'price' : ($this->hasColumn($db, 'unit_price') ? 'unit_price' : 'price');
        $payload[$nameField] = $validated['name'];
        $payload[$priceField] = $validated['price'];

        if ($this->hasColumn($db, 'description')) {
            $payload['description'] = $validated['description'] ?? null;
        }
        if ($this->hasColumn($db, 'sku')) {
            $payload['sku'] = $validated['sku'] ?? null;
        }
        if ($this->hasColumn($db, 'type')) {
            $payload['type'] = $validated['type'];
        }
        if ($this->hasColumn($db, 'stock_control_type')) {
            $payload['stock_control_type'] = $validated['type'] === 'service' ? 'STOCK_NOT_CONTROLLED' : 'STOCK_CONTROLLED';
        }
        if ($this->hasColumn($db, 'stock_qty')) {
            $existingStock = (float) ($product->stock_qty ?? 0);
            $payload['stock_qty'] = $validated['type'] === 'service' ? 0 : (float) ($validated['stock_qty'] ?? $existingStock);
        }
        if ($this->hasColumn($db, 'is_active')) {
            $payload['is_active'] = $validated['status'] === 'active';
        }

        $product->update($payload);

        return redirect('/products')->with('success', 'Product updated successfully.');
    }

    public function destroy($id, RequestContext $context, Database $db)
    {
        $companyId = (int) ($context->company()['company_id'] ?? 0) ?: 1;
        $product = $this->companyScopedQuery($companyId, $db)->findOrFail($id);

        if ($this->hasColumn($db, 'is_active')) {
            $product->update(['is_active' => false]);
        } else {
            $product->delete();
        }

        return redirect('/products')->with('success', 'Product disabled.');
    }
}
