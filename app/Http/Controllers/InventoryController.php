<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Core\RequestContext;
use App\Core\Database;
use App\Models\InventoryMovement;
use App\Models\Product;

class InventoryController extends Controller
{
    public function index(Request $request, RequestContext $context, Database $db)
    {
        $company = $context->company();
        if (!$company) {
            return response('Company not found', 404);
        }

        $companyId = (int) $company['company_id'];
        $inventory = Product::query()
            ->forCompany($companyId)
            ->orderBy('name')
            ->get()
            ->map(static function (Product $product): array {
                return [
                    'product_id' => (int) $product->product_id,
                    'product_name' => (string) ($product->name ?? 'Unknown'),
                    'sku' => (string) ($product->sku ?? '-'),
                    'quantity' => (float) ($product->stock_qty ?? 0),
                    'location' => '-',
                ];
            })
            ->all();

        return view('inventory.index', [
            'company' => $company,
            'inventory' => $inventory,
        ]);
    }

    public function move(Request $request, RequestContext $context, Database $db)
    {
        $company = $context->company();
        if (!$company) {
            return response('Company not found', 404);
        }

        $validated = $request->validate([
            'product_id' => 'required|integer|min:1',
            'quantity' => 'required|numeric|min:0.01',
            'type' => 'nullable|in:in,out',
            'movement_reason' => 'nullable|in:added,purchase,returned,adjust_in,sold,damaged,return_to_supplier,adjust_out',
            'description' => 'nullable|string|max:255',
        ]);

        $companyId = (int) $company['company_id'];
        $productId = (int) $validated['product_id'];
        $quantity = (float) $validated['quantity'];
        $movementReason = (string) ($validated['movement_reason'] ?? '');
        $type = (string) ($validated['type'] ?? '');
        if ($movementReason === '') {
            $movementReason = $type === 'out' ? 'adjust_out' : 'added';
        }

        $outgoingReasons = ['sold', 'damaged', 'return_to_supplier', 'adjust_out'];
        $incomingReasons = ['added', 'purchase', 'returned', 'adjust_in'];

        if (in_array($movementReason, $outgoingReasons, true)) {
            $direction = 'out';
        } elseif (in_array($movementReason, $incomingReasons, true)) {
            $direction = 'in';
        } else {
            $direction = $type === 'out' ? 'out' : 'in';
        }

        $product = Product::query()
            ->where('product_id', $productId)
            ->forCompany($companyId)
            ->first();

        if (!$product instanceof Product) {
            return redirect('/inventory')->withErrors(['product_id' => 'Product not found for this company.']);
        }

        $delta = $direction === 'in' ? $quantity : -$quantity;
        $qtyBefore = (float) ($product->stock_qty ?? 0);

        if ($delta < 0 && !Product::canDeductStock($productId, abs($delta), $companyId)) {
            return redirect('/inventory')->withErrors(['quantity' => 'Not enough stock available for this movement.']);
        }

        $description = trim((string) ($validated['description'] ?? ''));
        $note = $description !== '' ? $description : ('Reason: ' . str_replace('_', ' ', $movementReason));

        try {
            if (!Product::applyStockDelta($productId, $companyId, $delta)) {
                return redirect('/inventory')->withErrors(['quantity' => 'Unable to update stock for this product.']);
            }

            $product->refresh();
            $qtyAfter = (float) ($product->stock_qty ?? $qtyBefore);

            $movementModel = new InventoryMovement($db->pdo());
            $movementModel->createForCompany(
                $companyId,
                $productId,
                $movementReason,
                $quantity,
                $qtyBefore,
                $qtyAfter,
                $note,
                isset($_SESSION['user']['user_id']) ? (int) $_SESSION['user']['user_id'] : null
            );

            return redirect('/inventory')->with('success', 'Movement recorded.');
        } catch (\Exception $e) {
            error_log("Inventory move error: " . $e->getMessage());
            return redirect('/inventory')->withErrors(['quantity' => 'Error recording movement: ' . $e->getMessage()]);
        }
    }

    public function audit(Request $request, RequestContext $context, Database $db)
    {
        $company = $context->company();
        if (!$company) {
            return response('Company not found', 404);
        }

        $companyId = (int) $company['company_id'];
        $movements = [];

        try {
            $movements = (new InventoryMovement($db->pdo()))->listByCompany($companyId, 300);
        } catch (\Exception $e) {
            error_log("Inventory audit fetch error: " . $e->getMessage());
        }

        return view('inventory.audit', [
            'company' => $company,
            'movements' => $movements,
        ]);
    }
}
