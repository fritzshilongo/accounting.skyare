<?php

namespace App\Http\Controllers;

use App\Core\RequestContext;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class GlobalSearchController extends Controller
{
    public function index(Request $request, RequestContext $context)
    {
        $company = $context->company();
        if (!$company) {
            return response('Company not found', 404);
        }

        $term = trim((string) $request->query('q', ''));
        $companyId = (int) ($company['company_id'] ?? 0);

        $clients = collect();
        $products = collect();
        $invoices = collect();

        if ($term !== '') {
            try {
                $clientsQuery = Client::query();
                if (Schema::hasColumn('clients', 'company_id')) {
                    $clientsQuery->where('company_id', $companyId);
                }

                $clients = $clientsQuery
                    ->where(function ($q) use ($term) {
                        $this->applySearchColumns($q, 'clients', ['name', 'email', 'phone'], $term);
                    })
                    ->limit(8)
                    ->get();
            } catch (\Throwable $e) {
                $clients = collect();
            }

            try {
                $productsQuery = Product::query();
                if (Schema::hasColumn('products', 'company_id')) {
                    $productsQuery->where('company_id', $companyId);
                }

                $products = $productsQuery
                    ->where(function ($q) use ($term) {
                        $this->applySearchColumns($q, 'products', ['name', 'product_name', 'sku', 'description'], $term);
                    })
                    ->limit(8)
                    ->get();
            } catch (\Throwable $e) {
                $products = collect();
            }

            try {
                $invoicesQuery = Invoice::query();
                if (Schema::hasColumn('invoices', 'company_id')) {
                    $invoicesQuery->where('company_id', $companyId);
                }

                $invoices = $invoicesQuery
                    ->where(function ($q) use ($term) {
                        $this->applySearchColumns($q, 'invoices', ['invoice_no', 'client_name'], $term);
                    })
                    ->orderByDesc('invoice_id')
                    ->limit(8)
                    ->get();
            } catch (\Throwable $e) {
                $invoices = collect();
            }
        }

        return view('system.search', [
            'query' => $term,
            'clients' => $clients,
            'products' => $products,
            'invoices' => $invoices,
        ]);
    }

    private function applySearchColumns($query, string $table, array $preferredColumns, string $term): void
    {
        $existing = [];
        foreach ($preferredColumns as $column) {
            if (Schema::hasColumn($table, $column)) {
                $existing[] = $column;
            }
        }

        if ($existing === []) {
            return;
        }

        $first = true;
        foreach ($existing as $column) {
            if ($first) {
                $query->where($column, 'like', '%' . $term . '%');
                $first = false;
            } else {
                $query->orWhere($column, 'like', '%' . $term . '%');
            }
        }
    }
}