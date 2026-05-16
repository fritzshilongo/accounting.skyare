<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Estimate;
use App\Models\Client;
use App\Models\Product;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\EstimateItem;
use App\Core\RequestContext;
use App\Core\Database;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Http\Controllers\ActivityFeed;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class EstimateController extends Controller
{
    private static ?bool $invoiceItemDescriptionColumnExists = null;

    public function index(Request $request, RequestContext $context)
    {
        $companyId = (int) ($context->company()['company_id'] ?? 0) ?: 1;
        $search = trim((string) $request->query('search', ''));
        $status = trim((string) $request->query('status', ''));

        $estimates = Estimate::where('company_id', $companyId)
            ->with('client', 'product')
            ->when($search !== '', function ($q) use ($search) {
                $q->where(function ($sub) use ($search) {
                    $sub->where('client_name', 'like', "%{$search}%")
                        ->orWhereHas('client', function ($qc) use ($search) {
                            $qc->where('name', 'like', "%{$search}%");
                        });
                });
            })
            ->when($status !== '', function ($q) use ($status) {
                $q->where('status', $status);
            })
            ->latest('estimate_id')
            ->paginate(20)
            ->withQueryString();

        return view('estimates.index', compact('estimates', 'search', 'status'));
    }

    public function create(RequestContext $context)
    {
        $companyId = (int) ($context->company()['company_id'] ?? 0) ?: 1;
        $clients = Client::where('company_id', $companyId)->get();
        $products = Product::forCompany($companyId)->get();
        $defaultTaxRate = $this->defaultTaxRateForCompany($companyId);
        return view('estimates.create', compact('clients', 'products', 'defaultTaxRate'));
    }

    public function store(Request $request, RequestContext $context, Database $db)
    {
        $companyId = (int) ($context->company()['company_id'] ?? 0) ?: 1;
        $hasItemRows = is_array($request->input('item_product_id', [])) && count(array_filter($request->input('item_product_id', []), fn ($value) => (int) $value > 0)) > 0;

        $rules = [
            'client_id' => 'required',
            'issue_date' => 'required|date',
            'due_date' => 'required|date',
        ];

        if ($hasItemRows) {
            $rules['item_product_id.*'] = 'required|exists:products,product_id';
            $rules['item_quantity.*'] = 'required|numeric|min:0.01';
            $rules['item_price.*'] = 'required|numeric|min:0';
            $rules['item_description.*'] = 'nullable|string|max:2000';
        } else {
            $rules['product_id'] = 'required|exists:products,product_id';
            $rules['quantity'] = 'required|numeric|min:0.01';
            $rules['price'] = 'nullable|numeric|min:0';
        }

        $request->validate($rules);
        $client = Client::where('company_id', $companyId)->findOrFail((int) $request->client_id);

        if ($hasItemRows) {
            $estimate = Estimate::create([
                'company_id' => $companyId,
                'client_id' => (int) $request->client_id,
                'customer_id' => $request->client_id,
                'product_id' => (int) ($request->input('item_product_id.0') ?: 0),
                'client_name' => $client->name,
                'quantity' => 0,
                'unit_price' => 0,
                'amount' => 0,
                'tax_amount' => 0,
                'total' => 0,
                'estimate_date' => $request->issue_date,
                'expiry_date' => $request->due_date,
                'status' => 'draft',
            ]);

            $itemProductIds = $request->input('item_product_id', []);
            $itemDescriptions = $request->input('item_description', []);
            $itemQuantities = $request->input('item_quantity', []);
            $itemPrices = $request->input('item_price', []);
            $itemDeleted = $request->input('item_deleted', []);

            $itemProducts = Product::forCompany($companyId)
                ->whereIn('product_id', array_filter(array_map('intval', $itemProductIds)))
                ->get()
                ->keyBy('product_id');

            $totalQuantity = 0;
            $subtotal = 0;
            $firstProductId = null;
            $firstPrice = 0;

            foreach ($itemProductIds as $index => $productIdRaw) {
                $productId = (int) ($productIdRaw ?? 0);
                if ($productId <= 0 || (int) ($itemDeleted[$index] ?? 0) === 1) {
                    continue;
                }

                $quantity = (float) ($itemQuantities[$index] ?? 0);
                $price = (float) ($itemPrices[$index] ?? 0);
                $product = $itemProducts->get($productId);
                if ($price <= 0 && $product) {
                    $price = (float) ($product->price ?? 0);
                }

                if ($quantity <= 0) {
                    continue;
                }

                $description = trim((string) ($itemDescriptions[$index] ?? '')) ?: null;
                $lineTotal = round($quantity * $price, 2);
                $subtotal += $lineTotal;
                $totalQuantity += $quantity;

                if ($firstProductId === null) {
                    $firstProductId = $productId;
                    $firstPrice = $price;
                }

                EstimateItem::create([
                    'estimate_id' => $estimate->estimate_id,
                    'product_id' => $productId,
                    'description' => $description,
                    'price' => $price,
                    'quantity' => $quantity,
                ]);
            }

            $taxRate = $this->defaultTaxRateForCompany($companyId);
            $taxAmount = round($subtotal * ($taxRate / 100), 2);
            $total = round($subtotal + $taxAmount, 2);

            $estimate->update([
                'product_id' => $firstProductId,
                'quantity' => $totalQuantity,
                'unit_price' => $firstPrice,
                'amount' => $subtotal,
                'tax_amount' => $taxAmount,
                'total' => $total,
            ]);
        } else {
            $product = Product::forCompany($companyId)->findOrFail((int) $request->product_id);
            $unitPrice = (float) ($product->price ?? 0);
            $quantity = (float) $request->quantity;
            $subtotal = round($unitPrice * $quantity, 2);
            $taxRate = $this->defaultTaxRateForCompany($companyId);
            $taxAmount = round($subtotal * ($taxRate / 100), 2);
            $total = round($subtotal + $taxAmount, 2);

            Estimate::create([
                'company_id' => $companyId,
                'client_id' => (int) $request->client_id,
                'customer_id' => $request->client_id,
                'product_id' => $request->product_id,
                'client_name' => $client->name,
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
                'amount' => $subtotal,
                'tax_amount' => $taxAmount,
                'total' => $total,
                'estimate_date' => $request->issue_date,
                'expiry_date' => $request->due_date,
                'status' => 'draft'
            ]);
        }

        try {
            ActivityFeed::log($db, $context, 'created', 'estimate', null, 'Estimate for ' . $client->name);
        } catch (\Throwable $e) {}

        return redirect('/estimates');
    }

    public function show($id, RequestContext $context)
    {
        $companyId = (int) ($context->company()['company_id'] ?? 0) ?: 1;
        $estimate = Estimate::where('company_id', $companyId)->with(['client', 'product', 'items.product'])->findOrFail($id);
        return view('estimates.show', compact('estimate'));
    }

    public function edit($id, RequestContext $context)
    {
        $companyId = (int) ($context->company()['company_id'] ?? 0) ?: 1;
        $estimate = Estimate::where('company_id', $companyId)->with(['items.product'])->findOrFail($id);

        if (strtolower((string) ($estimate->status ?? '')) === 'accepted') {
            return redirect('/estimates/' . $estimate->estimate_id)
                ->withErrors(['estimate' => 'Accepted estimates cannot be edited.']);
        }

        $clients = Client::where('company_id', $companyId)->get();
        $products = Product::forCompany($companyId)->get();
        return view('estimates.edit', compact('estimate', 'clients', 'products'));
    }

    public function update(Request $request, $id, RequestContext $context)
    {
        $companyId = (int) ($context->company()['company_id'] ?? 0) ?: 1;
        $estimate = Estimate::where('company_id', $companyId)->findOrFail($id);

        if (strtolower((string) ($estimate->status ?? '')) === 'accepted') {
            return redirect('/estimates/' . $estimate->estimate_id)
                ->withErrors(['estimate' => 'Accepted estimates cannot be updated.']);
        }

        $hasItemRows = is_array($request->input('item_product_id', [])) && count(array_filter($request->input('item_product_id', []), fn ($value) => (int) $value > 0)) > 0;

        $rules = [
            'client_id' => 'required|exists:clients,client_id',
            'estimate_date' => 'required|date',
            'expiry_date' => 'required|date',
            'status' => 'required|in:draft,sent,accepted,declined',
        ];

        if ($hasItemRows) {
            $rules['item_product_id.*'] = 'required|exists:products,product_id';
            $rules['item_quantity.*'] = 'required|numeric|min:0.01';
            $rules['item_price.*'] = 'required|numeric|min:0';
            $rules['item_description.*'] = 'nullable|string|max:2000';
        } else {
            $rules['product_id'] = 'required|exists:products,product_id';
            $rules['quantity'] = 'required|numeric|min:0.01';
            $rules['unit_price'] = 'nullable|numeric|min:0';
        }

        $request->validate($rules);
        $client = Client::where('company_id', $companyId)->findOrFail((int) $request->client_id);

        if ($hasItemRows) {
            $estimate->update([
                'client_id' => (int) $request->client_id,
                'client_name' => $client->name,
                'estimate_date' => $request->estimate_date,
                'expiry_date' => $request->expiry_date,
                'status' => $request->status,
            ]);

            $this->processEstimateItems($request, $estimate, $companyId);
            $this->recalculateEstimateTotals($estimate, $companyId);
        } else {
            $product = Product::forCompany($companyId)->findOrFail((int) $request->product_id);
            $unitPrice = (float) ($product->price ?? 0);
            $quantity = (float) $request->quantity;
            $taxRate = $this->defaultTaxRateForCompany($companyId);
            $taxAmount = round($unitPrice * $quantity * ($taxRate / 100), 2);
            $total = round($unitPrice * $quantity + $taxAmount, 2);

            $estimate->update([
                'client_id' => (int) $request->client_id,
                'product_id' => (int) $request->product_id,
                'client_name' => $client->name,
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
                'amount' => round($unitPrice * $quantity, 2),
                'tax_amount' => $taxAmount,
                'total' => $total,
                'estimate_date' => $request->estimate_date,
                'expiry_date' => $request->expiry_date,
                'status' => $request->status,
            ]);
        }

        return redirect('/estimates/' . $estimate->estimate_id)->with('success', 'Estimate updated.');
    }

    private function processEstimateItems(Request $request, Estimate $estimate, int $companyId): void
    {
        $itemIds = $request->input('item_id', []);
        $productIds = $request->input('item_product_id', []);
        $descriptions = $request->input('item_description', []);
        $quantities = $request->input('item_quantity', []);
        $prices = $request->input('item_price', []);
        $deletedFlags = $request->input('item_deleted', []);

        if (!is_array($productIds)) {
            return;
        }

        $productLookup = Product::forCompany($companyId)
            ->whereIn('product_id', array_filter(array_map('intval', $productIds)))
            ->get()
            ->keyBy('product_id');

        foreach ($productIds as $index => $productIdRaw) {
            $productId = (int) ($productIdRaw ?? 0);
            if ($productId <= 0) {
                continue;
            }

            $quantity = (float) ($quantities[$index] ?? 0);
            $price = (float) ($prices[$index] ?? 0);
            $product = $productLookup->get($productId);
            if ($price <= 0 && $product) {
                $price = (float) ($product->price ?? 0);
            }

            $description = trim((string) ($descriptions[$index] ?? '')) ?: null;
            $deleted = (int) ($deletedFlags[$index] ?? 0) === 1;
            $itemId = (int) ($itemIds[$index] ?? 0);
            $existingItem = null;

            if ($itemId > 0) {
                $existingItem = EstimateItem::where('estimate_id', $estimate->estimate_id)->find($itemId);
            }

            if ($deleted) {
                if ($existingItem) {
                    $existingItem->delete();
                }
                continue;
            }

            if ($quantity <= 0) {
                continue;
            }

            $payload = [
                'estimate_id' => $estimate->estimate_id,
                'product_id' => $productId,
                'description' => $description,
                'price' => $price,
                'quantity' => $quantity,
            ];

            if ($existingItem) {
                $existingItem->update($payload);
            } else {
                EstimateItem::create($payload);
            }
        }
    }

    private function recalculateEstimateTotals(Estimate $estimate, int $companyId): void
    {
        $estimate->load('items');
        $items = $estimate->items;

        if ($items->isEmpty()) {
            $estimate->update([
                'product_id' => null,
                'quantity' => 0,
                'unit_price' => 0,
                'amount' => 0,
                'tax_amount' => 0,
                'total' => 0,
            ]);
            return;
        }

        $subtotal = $items->reduce(function ($carry, $item) {
            return $carry + ((float) $item->price * (float) $item->quantity);
        }, 0.0);

        $taxRate = $this->defaultTaxRateForCompany($companyId);
        $taxAmount = round($subtotal * ($taxRate / 100), 2);
        $total = round($subtotal + $taxAmount, 2);

        $estimate->update([
            'product_id' => (int) ($items->first()->product_id ?? null),
            'quantity' => (int) $items->sum('quantity'),
            'unit_price' => (float) ($items->first()->price ?? 0),
            'amount' => $subtotal,
            'tax_amount' => $taxAmount,
            'total' => $total,
        ]);
    }

    public function destroy($id, RequestContext $context)
    {
        $companyId = (int) ($context->company()['company_id'] ?? 0) ?: 1;
        $estimate = Estimate::where('company_id', $companyId)->findOrFail($id);
        $estimate->delete();
        return redirect('/estimates')->with('success', 'Estimate removed.');
    }

    public function convert($id, RequestContext $context)
    {
        $companyId = (int) ($context->company()['company_id'] ?? 0) ?: 1;
        $estimate = Estimate::where('company_id', $companyId)->findOrFail($id);

        try {
            DB::beginTransaction();

            $clientId = (int) ($estimate->client_id ?: $estimate->customer_id ?: 0);
            if ($clientId > 0) {
                $clientExists = Client::where('company_id', $companyId)->where('client_id', $clientId)->exists();
                if (!$clientExists) {
                    $clientId = 0;
                }
            }

            $invoiceNumberInfo = $this->nextInvoiceNumberForCompany($companyId);
            $invoiceNo = $invoiceNumberInfo['invoice_no'];

            $quantity = (float) ($estimate->quantity ?? 1);
            $unitPrice = (float) ($estimate->unit_price ?? $estimate->amount ?? 0);
            $subtotal = (float) ($estimate->amount ?? ($unitPrice * $quantity));
            $taxAmount = (float) ($estimate->tax_amount ?? 0);
            $total = (float) ($estimate->total ?: ($subtotal + $taxAmount) ?: $subtotal);

            $invoice = null;
            $attempts = 0;

            while ($invoice === null) {
                try {
                    $invoice = Invoice::create([
                        'company_id' => (int) ($estimate->company_id ?? $companyId),
                        'client_id' => $clientId > 0 ? $clientId : null,
                        'client_name' => $estimate->client_name ?? '',
                        'invoice_no' => $invoiceNo,
                        'amount' => $subtotal,
                        'tax_rate' => $this->estimateTaxRate($estimate, $companyId),
                        'tax_amount' => $taxAmount,
                        'total' => $total,
                        'issue_date' => $estimate->estimate_date ?? now()->toDateString(),
                        'due_date' => $estimate->expiry_date ?? now()->addDays(14)->toDateString(),
                        'status' => 'draft'
                    ]);
                } catch (QueryException $e) {
                    if ($attempts >= 5 || strpos($e->getMessage(), 'invoices_invoice_no_unique') === false) {
                        throw $e;
                    }

                    $invoiceNumberInfo = $this->nextInvoiceNumberForCompany($companyId);
                    $invoiceNo = $invoiceNumberInfo['invoice_no'];
                    $attempts++;
                }
            }

            $this->incrementNextInvoiceNumber($companyId, $invoiceNumberInfo['next_number']);

            $estimateItems = $estimate->items()->get();
            if ($estimateItems->isEmpty()) {
                // Legacy single-item estimates.
                $productId = (int) ($estimate->product_id ?? 0);
                $product = $productId > 0 ? Product::forCompany($companyId)->find($productId) : null;
                $itemDescription = null;
                if ($product) {
                    $itemDescription = trim((string) ($product->description ?: $product->name));
                }
                if ($itemDescription === '') {
                    $itemDescription = trim((string) ($estimate->client_name ?? 'Quoted item')) ?: 'Quoted item';
                }

                $invoiceItemPayload = [
                    'invoice_id' => $invoice->invoice_id,
                    'product_id' => $productId > 0 ? $productId : null,
                    'quantity' => $quantity,
                    'unit_price' => $unitPrice,
                    'line_total' => $subtotal,
                ];

                if ($this->invoiceItemDescriptionColumnExists()) {
                    $invoiceItemPayload['description'] = $this->safeDescription($itemDescription);
                }

                InvoiceItem::create($invoiceItemPayload);

                if ($product && (string) ($product->stock_control_type ?? '') === 'STOCK_CONTROLLED') {
                    $qtyBefore = (float) ($product->stock_qty ?? 0);

                    if (Product::canDeductStock($productId, $quantity, $companyId)) {
                        Product::applyStockDelta($productId, $companyId, -$quantity);
                        $product->refresh();
                        $qtyAfter = (float) ($product->stock_qty ?? 0);
                    } else {
                        Product::applyStockDelta($productId, $companyId, -$qtyBefore);
                        $product->refresh();
                        $qtyAfter = (float) ($product->stock_qty ?? 0);
                    }

                    try {
                        $pdo = DB::connection()->getPdo();
                        (new \App\Models\InventoryMovement($pdo))->createForCompany(
                            $companyId,
                            $productId,
                            'sale_out',
                            $quantity,
                            $qtyBefore,
                            $qtyAfter,
                            'Invoice ' . $invoiceNo . ' (from estimate)',
                            (int) ($_SESSION['user']['user_id'] ?? 0) ?: null
                        );
                    } catch (\Throwable $movErr) {
                        error_log('Estimate convert movement log error: ' . $movErr->getMessage());
                    }
                }
            } else {
                foreach ($estimateItems as $estimateItem) {
                    $product = $estimateItem->product;
                    $itemDescription = trim((string) ($estimateItem->description ?? ''));
                    if ($itemDescription === '') {
                        if ($product) {
                            $itemDescription = trim((string) ($product->description ?: $product->name));
                        }
                    }
                    if ($itemDescription === '') {
                        $itemDescription = trim((string) ($estimate->client_name ?? 'Quoted item')) ?: 'Quoted item';
                    }

                    $invoiceItemPayload = [
                        'invoice_id' => $invoice->invoice_id,
                        'product_id' => (int) ($estimateItem->product_id ?? null) ?: null,
                        'quantity' => (float) $estimateItem->quantity,
                        'unit_price' => (float) $estimateItem->price,
                        'line_total' => round((float) $estimateItem->price * (float) $estimateItem->quantity, 2),
                    ];

                    if ($this->invoiceItemDescriptionColumnExists()) {
                        $invoiceItemPayload['description'] = $this->safeDescription($itemDescription);
                    }

                    InvoiceItem::create($invoiceItemPayload);

                    if ($product && (string) ($product->stock_control_type ?? '') === 'STOCK_CONTROLLED') {
                        $qtyBefore = (float) ($product->stock_qty ?? 0);
                        $quantity = (float) $estimateItem->quantity;

                        if (Product::canDeductStock((int) $product->product_id, $quantity, $companyId)) {
                            Product::applyStockDelta((int) $product->product_id, $companyId, -$quantity);
                            $product->refresh();
                            $qtyAfter = (float) ($product->stock_qty ?? 0);
                        } else {
                            Product::applyStockDelta((int) $product->product_id, $companyId, -$qtyBefore);
                            $product->refresh();
                            $qtyAfter = (float) ($product->stock_qty ?? 0);
                        }

                        try {
                            $pdo = DB::connection()->getPdo();
                            (new \App\Models\InventoryMovement($pdo))->createForCompany(
                                $companyId,
                                (int) $product->product_id,
                                'sale_out',
                                $quantity,
                                $qtyBefore,
                                $qtyAfter,
                                'Invoice ' . $invoiceNo . ' (from estimate)',
                                (int) ($_SESSION['user']['user_id'] ?? 0) ?: null
                            );
                        } catch (\Throwable $movErr) {
                            error_log('Estimate convert movement log error: ' . $movErr->getMessage());
                        }
                    }
                }
            }

            // mark estimate as converted
            $estimate->status = 'accepted';
            $estimate->save();

            DB::commit();

            return redirect('/invoices')->with('success', 'Estimate converted to draft invoice.');
        } catch (\Throwable $e) {
            DB::rollBack();

            return redirect('/estimates/' . (int) $estimate->estimate_id)
                ->withErrors(['convert' => 'Could not convert estimate: ' . $e->getMessage()]);
        }
    }

    private function safeDescription(?string $description): ?string
    {
        if ($description === null) {
            return null;
        }

        return mb_substr($description, 0, 255);
    }

    private function defaultTaxRateForCompany(int $companyId): float
    {
        try {
            $rows = DB::table('company_preferences')
                ->where('company_id', $companyId)
                ->whereIn('preference_key', ['tax_rate', 'default_tax_rate'])
                ->pluck('preference_value', 'preference_key');

            $preferred = $rows['tax_rate'] ?? $rows['default_tax_rate'] ?? null;
            if ($preferred !== null && $preferred !== '') {
                return max(0.0, (float) $preferred);
            }
        } catch (\Throwable $e) {
            // Fall through to tax rate table/default.
        }

        try {
            $defaultTax = DB::table('tax_rates')
                ->where('company_id', $companyId)
                ->where('is_active', 1)
                ->where('is_default', 1)
                ->value('rate');
            if ($defaultTax !== null) {
                return max(0.0, (float) $defaultTax);
            }
        } catch (\Throwable $e) {
            // Ignore and use hard fallback.
        }

        return 0.0;
    }

    private function invoiceItemDescriptionColumnExists(): bool
    {
        if (self::$invoiceItemDescriptionColumnExists !== null) {
            return self::$invoiceItemDescriptionColumnExists;
        }

        try {
            self::$invoiceItemDescriptionColumnExists = Schema::hasColumn('invoice_items', 'description');
        } catch (\Throwable $e) {
            self::$invoiceItemDescriptionColumnExists = false;
        }

        return self::$invoiceItemDescriptionColumnExists;
    }

    private function nextInvoiceNumberForCompany(int $companyId): array
    {
        $prefix = 'INV-';
        $nextNumber = 1001;
        $maxSerial = 0;

        try {
            $rows = DB::table('company_preferences')
                ->where('company_id', $companyId)
                ->whereIn('preference_key', ['invoice_prefix', 'next_invoice_number'])
                ->pluck('preference_value', 'preference_key');

            $prefix = trim((string) ($rows['invoice_prefix'] ?? $prefix));
            $nextNumber = max(1, (int) ($rows['next_invoice_number'] ?? $nextNumber));
        } catch (\Throwable $e) {
            // Keep default values if preferences cannot be read.
        }

        try {
            $invoiceNos = Invoice::where('company_id', $companyId)
                ->whereNotNull('invoice_no')
                ->where('invoice_no', 'like', $prefix . '%')
                ->pluck('invoice_no');

            foreach ($invoiceNos as $invoiceNo) {
                if (!is_string($invoiceNo) || !str_starts_with($invoiceNo, $prefix)) {
                    continue;
                }

                $raw = substr($invoiceNo, strlen($prefix));
                if (!ctype_digit($raw)) {
                    continue;
                }

                $serial = (int) $raw;
                if ($serial > $maxSerial) {
                    $maxSerial = $serial;
                }
            }
        } catch (\Throwable $e) {
            // Fall back to prefixed invoice numbers if query fails.
        }

        $candidate = max($nextNumber, $maxSerial + 1);
        do {
            $invoiceNo = $prefix . str_pad((string) $candidate, 4, '0', STR_PAD_LEFT);
            if (!Invoice::where('company_id', $companyId)->where('invoice_no', $invoiceNo)->exists()) {
                break;
            }
            $candidate++;
        } while (true);

        return [
            'invoice_no' => $invoiceNo,
            'next_number' => $candidate + 1,
        ];
    }

    private function incrementNextInvoiceNumber(int $companyId, int $nextNumber): void
    {
        try {
            DB::table('company_preferences')
                ->updateOrInsert(
                    ['company_id' => $companyId, 'preference_key' => 'next_invoice_number'],
                    ['preference_value' => (string) $nextNumber, 'updated_at' => now(), 'created_at' => now()]
                );
        } catch (\Throwable $e) {
            error_log('Estimate incrementNextInvoiceNumber failed: ' . $e->getMessage());
        }
    }

    private function estimateTaxRate(Estimate $estimate, int $companyId): float
    {
        $amount = (float) ($estimate->amount ?? 0);
        $taxAmount = (float) ($estimate->tax_amount ?? 0);
        if ($amount > 0 && $taxAmount > 0) {
            return round(($taxAmount / $amount) * 100, 4);
        }

        return $this->defaultTaxRateForCompany($companyId);
    }

    public function pdf($id, RequestContext $context)
    {
        $companyId = (int) ($context->company()['company_id'] ?? 0) ?: 1;
        $estimate = Estimate::where('company_id', $companyId)->with(['client', 'product'])->findOrFail($id);
        $company = $context->company();

        $pdf = Pdf::loadView('estimates.pdf', [
            'estimate' => $estimate,
            'company' => $company,
        ]);

        return $pdf->download('EST-' . $estimate->estimate_id . '.pdf');
    }
}
