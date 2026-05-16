<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Client;
use App\Models\Product;
use App\Core\Database;
use App\Core\RequestContext;
use Illuminate\Http\Request;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use App\Http\Controllers\ActivityFeed;

class InvoiceController extends Controller
{
    private static ?bool $invoiceItemDescriptionColumnExists = null;

    public function index(Request $request, RequestContext $context)
    {
        $companyId = (int) ($context->company()['company_id'] ?? 0) ?: 1;
        $search = trim((string) $request->query('search', ''));

        try {
            $invoices = Invoice::where('company_id', $companyId)
                ->with('client')
                ->when($search !== '', function ($q) use ($search) {
                    $q->where(function ($sub) use ($search) {
                        $sub->where('invoice_no', 'like', "%{$search}%")
                            ->orWhereHas('client', function ($qc) use ($search) {
                                $qc->where('name', 'like', "%{$search}%");
                            });
                    });
                })
                ->orderBy('invoice_id', 'desc')
                ->paginate(20)
                ->withQueryString();
        } catch (\Throwable $e) {
            // Fallback: try a simpler query in case of schema mismatch
            try {
                $invoices = Invoice::orderBy('invoice_id', 'desc')
                    ->paginate(20)
                    ->withQueryString();
            } catch (\Throwable $e2) {
                $invoices = new \Illuminate\Pagination\LengthAwarePaginator([], 0, 20);
            }
        }

        return view('invoices.index', compact('invoices', 'search'));
    }

    public function create(RequestContext $context, Database $db)
    {
        $companyId = (int) ($context->company()['company_id'] ?? 0) ?: 1;
        $clients = Client::where('company_id', $companyId)->orderBy('name')->get();
        $products = $this->availableProductsForCompany($companyId);
        $settings = $this->invoiceSettings($db, $companyId);
        $defaultTaxRate = (float) ($settings['tax_rate'] ?? 0);
        $defaultTerms = (int) ($settings['default_terms'] ?? 7);
        $invoiceDescriptionColumnExists = $this->invoiceItemDescriptionColumnExists();
        return view('invoices.create', compact('clients', 'products', 'defaultTaxRate', 'defaultTerms', 'invoiceDescriptionColumnExists'));
    }

    public function store(Request $request, RequestContext $context, Database $db)
    {
        $request->validate([
            'client_id' => 'required|exists:clients,client_id',
            'issue_date' => 'nullable|date',
            'due_date' => 'nullable|date',
            'product_id' => 'nullable|exists:products,product_id',
            'quantity' => 'nullable|numeric|min:0.01',
        ]);

        $companyId = (int) (($context->company()['company_id'] ?? 0) ?: 1);
        $invoiceSettings = $this->invoiceSettings($db, $companyId);

        $invoiceNumberInfo = $this->nextInvoiceNumberForCompany($companyId, $invoiceSettings);
        $invoiceNo = $invoiceNumberInfo['invoice_no'];

        $client = Client::where('company_id', $companyId)->findOrFail($request->client_id);

        $selectedProduct = null;
        $selectedQuantity = 1.0;
        if ($request->filled('product_id')) {
            $selectedProduct = Product::forCompany($companyId)->find((int) $request->product_id);
            if (!$selectedProduct) {
                return back()->withErrors(['product_id' => 'Selected product/service is not available for your company.'])->withInput();
            }

            $selectedQuantity = (float) ($request->input('quantity', 1));
            if ($selectedQuantity <= 0) {
                return back()->withErrors(['quantity' => 'Quantity must be greater than zero.'])->withInput();
            }

            if (!Product::canDeductStock((int) $selectedProduct->product_id, $selectedQuantity, $companyId)) {
                return back()->withErrors(['quantity' => 'Insufficient stock available for this product.'])->withInput();
            }
        }

        $issueDate = $request->issue_date ?? now()->toDateString();
        $dueDate = $request->due_date;
        if ($dueDate === null || $dueDate === '') {
            $dueDate = Carbon::parse($issueDate)->addDays($invoiceSettings['default_terms'])->toDateString();
        }

        $invoice = null;
        $attempts = 0;

        while ($invoice === null) {
            try {
                $invoice = Invoice::create([
                    'company_id' => $companyId,
                    'client_id' => $client->client_id,
                    'client_name' => $client->name,
                    'invoice_no' => $invoiceNo,
                    'amount' => 0,
                    'tax_rate' => $invoiceSettings['tax_rate'],
                    'status' => 'draft',
                    'issue_date' => $issueDate,
                    'due_date' => $dueDate,
                ]);
            } catch (QueryException $e) {
                if ($attempts >= 5 || strpos($e->getMessage(), 'invoices_invoice_no_unique') === false) {
                    throw $e;
                }

                $invoiceNumberInfo = $this->nextInvoiceNumberForCompany($companyId, $invoiceSettings);
                $invoiceNo = $invoiceNumberInfo['invoice_no'];
                $attempts++;
            }
        }

        if ($selectedProduct) {
            $itemPayload = [
                'invoice_id' => $invoice->invoice_id,
                'product_id' => (int) $selectedProduct->product_id,
                'quantity' => $selectedQuantity,
                'unit_price' => (float) $selectedProduct->price,
                'line_total' => $selectedQuantity * (float) $selectedProduct->price,
            ];

            if ($this->invoiceItemDescriptionColumnExists()) {
                $itemPayload['description'] = $this->safeDescription($selectedProduct->description ?: $selectedProduct->name);
            }

            InvoiceItem::create($itemPayload);

            if ((string) ($selectedProduct->stock_control_type ?? '') === 'STOCK_CONTROLLED') {
                $qtyBefore = (float) ($selectedProduct->stock_qty ?? 0);
                Product::applyStockDelta((int) $selectedProduct->product_id, $companyId, -$selectedQuantity);
                $selectedProduct->refresh();
                $qtyAfter = (float) ($selectedProduct->stock_qty ?? 0);

                try {
                    $pdo = DB::connection()->getPdo();
                    (new \App\Models\InventoryMovement($pdo))->createForCompany(
                        $companyId,
                        (int) $selectedProduct->product_id,
                        'sale_out',
                        $selectedQuantity,
                        $qtyBefore,
                        $qtyAfter,
                        'Invoice ' . ($invoice->invoice_no ?? ('#' . $invoice->invoice_id)),
                        (int) ($_SESSION['user']['user_id'] ?? 0) ?: null
                    );
                } catch (\Throwable $e) {
                    error_log('Invoice store movement log error: ' . $e->getMessage());
                }
            }

            $this->recalculate($invoice->invoice_id);
        }

        $this->incrementNextInvoiceNumber($db, $companyId, $invoiceNumberInfo['next_number']);

        try {
            ActivityFeed::log($db, $context, 'created', 'invoice', $invoice->invoice_id, $invoiceNo);
            ActivityFeed::notify($db, $context, 'invoice_created', 'New Invoice Created', "Invoice {$invoiceNo} for {$client->name}", '/invoices/' . $invoice->invoice_id, 'fa-file-invoice-dollar');
        } catch (\Throwable $e) {}

        return redirect('/invoices/' . $invoice->invoice_id);
    }

    public function show($id, RequestContext $context, Database $db)
    {
        $companyId = (int) ($context->company()['company_id'] ?? 0) ?: 1;
        $invoice = Invoice::where('company_id', $companyId)->with(['items', 'client'])->findOrFail($id);
        $products = $this->availableProductsForCompany($companyId);

        $attachments = [];
        try {
            $stmt = $db->pdo()->prepare('SELECT * FROM file_attachments WHERE company_id = :cid AND attachable_type = :type AND attachable_id = :eid ORDER BY created_at DESC');
            $stmt->execute(['cid' => $companyId, 'type' => 'invoice', 'eid' => (int) $id]);
            $attachments = $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];
        } catch (\Throwable $e) {}

        $invoiceDescriptionColumnExists = $this->invoiceItemDescriptionColumnExists();
        return view('invoices.show', compact('invoice', 'products', 'attachments', 'invoiceDescriptionColumnExists'));
    }

    public function addItem(Request $request, $id, RequestContext $context)
    {
        $companyId = (int) ($context->company()['company_id'] ?? 0) ?: 1;

        $request->validate([
            'product_id' => 'required|exists:products,product_id',
            'quantity' => 'required|numeric|min:0.01',
        ]);

        $product = Product::forCompany($companyId)->findOrFail($request->product_id);
        $quantity = (float) $request->quantity;

        // Verify invoice belongs to current tenant
        $invoice = Invoice::where('company_id', $companyId)->findOrFail($id);

        if (! $this->invoiceIsEditable($invoice)) {
            return back()->withErrors(['invoice' => 'Cannot add items to a paid, finalised, or cancelled invoice.']);
        }

        if (!Product::canDeductStock((int) $product->product_id, $quantity, $companyId)) {
            return back()->withErrors(['quantity' => 'Insufficient stock available for this product.']);
        }

        $itemPayload = [
            'invoice_id' => $invoice->invoice_id,
            'product_id' => (int) $product->product_id,
            'quantity' => $quantity,
            'unit_price' => $product->price,
            'line_total' => $quantity * (float) $product->price,
        ];

        if ($this->invoiceItemDescriptionColumnExists()) {
            $itemPayload['description'] = $this->safeDescription($product->description ?: $product->name);
        }

        InvoiceItem::create($itemPayload);

        if ((string) ($product->stock_control_type ?? '') === 'STOCK_CONTROLLED') {
            $qtyBefore = (float) ($product->stock_qty ?? 0);
            Product::applyStockDelta((int) $product->product_id, $companyId, -$quantity);
            $product->refresh();
            $qtyAfter = (float) ($product->stock_qty ?? 0);

            try {
                $pdo = DB::connection()->getPdo();
                (new \App\Models\InventoryMovement($pdo))->createForCompany(
                    $companyId,
                    (int) $product->product_id,
                    'sale_out',
                    $quantity,
                    $qtyBefore,
                    $qtyAfter,
                    'Invoice ' . ($invoice->invoice_no ?? ('#' . $id)),
                    (int) ($_SESSION['user']['user_id'] ?? 0) ?: null
                );
            } catch (\Throwable $e) {
                error_log('Invoice addItem movement log error: ' . $e->getMessage());
            }
        }

        $this->recalculate($id);
        return back();
    }

    public function updateStatus(Request $request, $id, RequestContext $context)
    {
        $companyId = (int) ($context->company()['company_id'] ?? 0) ?: 1;
        $invoice = Invoice::where('company_id', $companyId)->findOrFail($id);

        $status = strtolower((string) $request->input('status', 'paid'));
        if ($status === 'partial') {
            $status = 'partial_paid';
        }
        if ($status === 'finalized') {
            $status = 'finalised';
        }

        if (!in_array($status, ['draft', 'accepted', 'partial_paid', 'paid', 'finalised', 'cancelled'], true)) {
            abort(400, 'Invalid invoice status');
        }

        if (($invoice->due_date === null || $invoice->due_date === '') && $status === 'accepted') {
            $invoice->due_date = now()->addDays(7)->toDateString();
        }

        $invoice->status = $status;
        $invoice->paid_at = in_array($status, ['paid', 'finalised'], true) ? now() : null;

        // If status is paid and there is no left balance, set paid at and save.
        if (in_array($status, ['paid', 'finalised'], true)) {
            $invoice->refreshPaymentStatus();
            if ($status === 'finalised' && $invoice->balance <= 0) {
                $invoice->status = 'finalised';
                $invoice->paid_at = now();
                $invoice->save();
            }
        } else {
            $invoice->save();
        }

        return back()->with('success', 'Invoice status updated to ' . $status);
    }

    public function pdf($id, RequestContext $context)
    {
        $companyId = (int) ($context->company()['company_id'] ?? 0) ?: 1;
        $invoice = Invoice::where('company_id', $companyId)->with(['items', 'client'])->findOrFail($id);
        $company = $context->company();
        $payments = $invoice->payments()->orderBy('payment_date')->get();

        $pdf = Pdf::loadView('invoices.pdf', compact('invoice', 'company', 'payments'));
        return $pdf->download($invoice->invoice_no . '.pdf');
    }

    public function edit($id, RequestContext $context)
    {
        $companyId = (int) ($context->company()['company_id'] ?? 0) ?: 1;
        $invoice = Invoice::where('company_id', $companyId)->with(['items', 'client'])->findOrFail($id);

        if (! $this->invoiceIsEditable($invoice)) {
            return redirect('/invoices/' . $invoice->invoice_id)
                ->withErrors(['invoice' => 'Paid, finalised, or cancelled invoices cannot be edited.']);
        }

        $clients = Client::where('company_id', $companyId)->orderBy('name')->get();
        $products = $this->availableProductsForCompany($companyId);
        $invoiceDescriptionColumnExists = $this->invoiceItemDescriptionColumnExists();
        return view('invoices.edit', compact('invoice', 'clients', 'products', 'invoiceDescriptionColumnExists'));
    }

    public function update(Request $request, $id, RequestContext $context)
    {
        $companyId = (int) ($context->company()['company_id'] ?? 0) ?: 1;
        $invoice = Invoice::where('company_id', $companyId)->with('items')->findOrFail($id);

        if (! $this->invoiceIsEditable($invoice)) {
            return redirect('/invoices/' . $invoice->invoice_id)
                ->withErrors(['invoice' => 'Paid, finalised, or cancelled invoices cannot be edited.']);
        }

        $request->validate([
            'client_id' => 'required|exists:clients,client_id',
            'issue_date' => 'required|date',
            'due_date' => 'required|date',
            'status' => 'required|in:draft,accepted,partial_paid,paid,finalised,cancelled,partial,finalized',
            'item_product_id.*' => 'nullable|exists:products,product_id',
            'item_quantity.*' => 'nullable|numeric|min:0.01',
            'item_unit_price.*' => 'nullable|numeric|min:0',
            'item_description.*' => 'nullable|string|max:2000',
        ]);

        $client = Client::where('company_id', $companyId)->findOrFail($request->client_id);
        $normalizedStatus = $this->normalizeInvoiceStatus((string) $request->status);

        $invoice->update([
            'client_id' => (int) $request->client_id,
            'client_name' => $client->name,
            'issue_date' => $request->issue_date,
            'due_date' => $request->due_date,
            'status' => $normalizedStatus,
        ]);

        try {
            $this->processInvoiceItems($request, $invoice, $companyId);
        } catch (\RuntimeException $e) {
            return back()->withErrors(['invoice' => $e->getMessage()])->withInput();
        }

        $this->recalculate($invoice->invoice_id);
        if ($invoice->paid_amount > 0) {
            $invoice->refreshPaymentStatus();
        }

        return redirect('/invoices/' . $invoice->invoice_id)->with('success', 'Invoice updated.');
    }

    public function destroy($id, RequestContext $context)
    {
        $companyId = (int) ($context->company()['company_id'] ?? 0) ?: 1;
        $invoice = Invoice::where('company_id', $companyId)->findOrFail($id);
        $invoice->status = 'cancelled';
        $invoice->save();
        return redirect('/invoices')->with('success', 'Invoice marked cancelled.');
    }

    private function recalculate($id)
    {
        $subtotal = InvoiceItem::where('invoice_id', $id)->sum('line_total');
        $invoice = Invoice::findOrFail($id);
        $taxRate = (float) ($invoice->tax_rate ?? 0);
        $vat = $subtotal * ($taxRate / 100);
        $total = $subtotal + $vat;

        $invoice->amount = $total;
        $invoice->total = $total;
        $invoice->tax_amount = $vat;
        $invoice->save();
    }

    private function invoiceIsEditable(Invoice $invoice): bool
    {
        $status = strtolower((string) ($invoice->status ?? 'draft'));
        return !in_array($status, ['paid', 'finalised', 'cancelled'], true);
    }

    private function safeDescription(?string $description): ?string
    {
        if ($description === null) {
            return null;
        }

        $maxLength = 255;

        try {
            if (Schema::hasTable('invoice_items') && Schema::hasColumn('invoice_items', 'description')) {
                $column = DB::selectOne("SHOW COLUMNS FROM `invoice_items` WHERE `Field` = 'description'");
                if ($column) {
                    $type = strtolower($column->Type ?? '');
                    if (preg_match('/varchar\((\d+)\)/i', $type, $matches)) {
                        $maxLength = (int) $matches[1];
                    } elseif (strpos($type, 'text') !== false) {
                        $maxLength = 65535;
                    }
                }
            }
        } catch (\Throwable $e) {
            // fallback to default length if schema inspection fails
        }

        return mb_substr($description, 0, $maxLength);
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

    private function processInvoiceItems(Request $request, Invoice $invoice, int $companyId): void
    {
        $itemIds = $request->input('item_id', []);
        $productIds = $request->input('item_product_id', []);
        $descriptions = $request->input('item_description', []);
        $quantities = $request->input('item_quantity', []);
        $unitPrices = $request->input('item_unit_price', []);
        $deletedFlags = $request->input('item_deleted', []);

        if (!is_array($productIds)) {
            return;
        }

        foreach ($productIds as $index => $productIdRaw) {
            $productId = (int) ($productIdRaw ?? 0);
            if ($productId <= 0) {
                continue;
            }

            $quantity = (float) ($quantities[$index] ?? 0);
            $unitPrice = (float) ($unitPrices[$index] ?? 0);
            $description = trim((string) ($descriptions[$index] ?? '')) ?: null;
            $deleted = (int) ($deletedFlags[$index] ?? 0) === 1;
            $itemId = (int) ($itemIds[$index] ?? 0);
            $product = Product::forCompany($companyId)->find($productId);

            if (!$product) {
                continue;
            }

            $existingProduct = null;
            $oldQuantity = 0.0;
            if ($itemId > 0) {
                $existingItem = InvoiceItem::where('invoice_id', $invoice->invoice_id)->find($itemId);
                if ($existingItem) {
                    $existingProduct = Product::forCompany($companyId)->find((int) ($existingItem->product_id ?? 0));
                    $oldQuantity = (float) ($existingItem->quantity ?? 0);
                }
            }

            if ($deleted) {
                if (!empty($existingItem)) {
                    if ($existingProduct && (string) ($existingProduct->stock_control_type ?? '') === 'STOCK_CONTROLLED') {
                        Product::applyStockDelta((int) $existingProduct->product_id, $companyId, $oldQuantity);
                    }
                    $existingItem->delete();
                }
                continue;
            }

            if ($quantity <= 0) {
                continue;
            }

            $lineTotal = round($quantity * $unitPrice, 2);
            $requestedDelta = $quantity;
            if ($itemId > 0 && !empty($existingItem) && $existingProduct && (int) $existingProduct->product_id === $productId) {
                $requestedDelta = max(0, $quantity - $oldQuantity);
            }

            if (! Product::canDeductStock($productId, $requestedDelta, $companyId)) {
                throw new \RuntimeException('Insufficient stock available for ' . $product->name . '.');
            }

            if ($itemId > 0 && !empty($existingItem)) {
                if ($existingProduct && (int) $existingProduct->product_id !== $productId) {
                    if ((string) ($existingProduct->stock_control_type ?? '') === 'STOCK_CONTROLLED') {
                        Product::applyStockDelta((int) $existingProduct->product_id, $companyId, $oldQuantity);
                    }
                    if ((string) ($product->stock_control_type ?? '') === 'STOCK_CONTROLLED') {
                        Product::applyStockDelta($productId, $companyId, -$quantity);
                    }
                } else {
                    $delta = $quantity - $oldQuantity;
                    if ($delta !== 0 && (string) ($product->stock_control_type ?? '') === 'STOCK_CONTROLLED') {
                        Product::applyStockDelta($productId, $companyId, -$delta);
                    }
                }

                $updatePayload = [
                    'product_id' => $productId,
                    'quantity' => $quantity,
                    'unit_price' => $unitPrice,
                    'line_total' => $lineTotal,
                ];

                if ($this->invoiceItemDescriptionColumnExists()) {
                    $updatePayload['description'] = $this->safeDescription($description ?? ($product->description ?: $product->name));
                }

                $existingItem->update($updatePayload);
            } else {
                if ((string) ($product->stock_control_type ?? '') === 'STOCK_CONTROLLED') {
                    Product::applyStockDelta($productId, $companyId, -$quantity);
                }

                $itemPayload = [
                    'invoice_id' => $invoice->invoice_id,
                    'product_id' => $productId,
                    'quantity' => $quantity,
                    'unit_price' => $unitPrice,
                    'line_total' => $lineTotal,
                ];

                if ($this->invoiceItemDescriptionColumnExists()) {
                    $itemPayload['description'] = $this->safeDescription($description ?? ($product->description ?: $product->name));
                }

                InvoiceItem::create($itemPayload);
            }
        }
    }

    private function invoiceSettings(Database $db, int $companyId): array
    {
        $defaults = [
            'prefix' => 'INV-',
            'next_number' => 1001,
            'default_terms' => 7,
            'tax_rate' => 15.0,
        ];

        try {
            $stmt = $db->pdo()->prepare('SELECT preference_key, preference_value FROM company_preferences WHERE company_id = :company_id');
            $stmt->execute(['company_id' => $companyId]);
            $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];
            $prefs = [];
            foreach ($rows as $row) {
                $prefs[(string) $row['preference_key']] = (string) ($row['preference_value'] ?? '');
            }

            $invoicePrefix = (string) ($prefs['invoice_prefix'] ?? '');
            $defaults['prefix'] = $invoicePrefix !== '' ? $invoicePrefix : $defaults['prefix'];
            $defaults['next_number'] = max(1, (int) ($prefs['next_invoice_number'] ?? $defaults['next_number']));
            $defaults['default_terms'] = max(0, (int) ($prefs['default_payment_terms'] ?? $defaults['default_terms']));
            $defaults['tax_rate'] = max(0, (float) ($prefs['tax_rate'] ?? $defaults['tax_rate']));
        } catch (\Throwable $e) {
            return $defaults;
        }

        return $defaults;
    }

    private function incrementNextInvoiceNumber(Database $db, int $companyId, int $nextNumber): void
    {
        try {
            $stmt = $db->pdo()->prepare(
                'INSERT INTO company_preferences (company_id, preference_key, preference_value, created_at, updated_at)
                 VALUES (:company_id, :preference_key, :preference_value, NOW(), NOW())
                 ON DUPLICATE KEY UPDATE preference_value = VALUES(preference_value), updated_at = NOW()'
            );

            $stmt->execute([
                'company_id' => $companyId,
                'preference_key' => 'next_invoice_number',
                'preference_value' => (string) $nextNumber,
            ]);
        } catch (\Throwable $e) {
            // Non-fatal: counter may not persist but invoice creation should not fail
            error_log('incrementNextInvoiceNumber failed: ' . $e->getMessage());
        }
    }

    private function availableProductsForCompany(int $companyId)
    {
        $baseQuery = Product::forCompany($companyId)->orderBy('name');

        try {
            if (Schema::hasColumn('products', 'is_active')) {
                $active = Product::forCompany($companyId)
                    ->where(function ($q) {
                        $q->where('is_active', true)
                          ->orWhereNull('is_active');
                    })
                    ->orderBy('name')
                    ->get();

                if ($active->isNotEmpty()) {
                    return $active;
                }
            }
        } catch (\Throwable $e) {
            // Fall back to unfiltered product list.
        }

        return $baseQuery->get();
    }

    private function nextInvoiceNumberForCompany(int $companyId, array $invoiceSettings): array
    {
        $prefix = (string) ($invoiceSettings['prefix'] ?? 'INV-');
        $nextNumber = max(1, (int) ($invoiceSettings['next_number'] ?? 1001));
        $maxSerial = 0;

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

    private function normalizeInvoiceStatus(string $status): string
    {
        $status = strtolower(trim($status));

        return match ($status) {
            'partial' => 'partial_paid',
            'finalized' => 'finalised',
            default => $status,
        };
    }
}

