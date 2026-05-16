<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\AuditLogger;
use App\Core\Database;
use App\Core\RequestContext;
use App\Core\View;
use App\Models\InventoryMovement;
use App\Models\Product;

final class InventoryController
{
    public static function index(Database $db, RequestContext $context): void
    {
        $companyId = self::companyId($context);
        if ($companyId === null) { self::deny(); return; }

        $productModel = new Product($db->pdo());
        $movementModel = new InventoryMovement($db->pdo());

        View::render('inventory/index', [
            'company' => $context->company(),
            'products' => $productModel->listByCompany($companyId),
            'rows' => $movementModel->listByCompany($companyId),
            'token' => \App\Middleware\CsrfMiddleware::token(),
            'errors' => [],
        ]);
    }

    public static function audit(Database $db, RequestContext $context): void
    {
        $companyId = self::companyId($context);
        if ($companyId === null) { self::deny(); return; }

        // Filter params
        $productId    = (int)    ($_GET['product_id']    ?? 0);
        $movementType = trim((string) ($_GET['movement_type'] ?? ''));
        $fromDate     = trim((string) ($_GET['from']          ?? ''));
        $toDate       = trim((string) ($_GET['to']            ?? ''));
        $search       = trim((string) ($_GET['q']             ?? ''));

        $productModel  = new Product($db->pdo());
        $movementModel = new InventoryMovement($db->pdo());

        // Stock snapshot (all products for this company)
        $allProducts = $productModel->listByCompany($companyId, 500);

        // Compute stock summary stats
        $totalStockValue  = 0.0;
        $lowStockCount    = 0;
        $outOfStockCount  = 0;
        $stockControlled  = 0;
        foreach ($allProducts as $p) {
            $qty   = (float) ($p['stock_qty']  ?? 0);
            $price = (float) ($p['unit_price'] ?? 0);
            if ((string) ($p['stock_control_type'] ?? '') === 'STOCK_CONTROLLED') {
                $stockControlled++;
                $totalStockValue += $qty * $price;
                if ($qty <= 0)  { $outOfStockCount++; }
                elseif ($qty <= 5) { $lowStockCount++; }
            }
        }

        // Filtered movement history
        $movements = $movementModel->listFiltered(
            $companyId,
            $productId,
            $movementType,
            $fromDate,
            $toDate,
            $search
        );

        // Movement-type breakdown for the filtered set
        $typeBreakdown = [];
        foreach ($movements as $row) {
            $type = (string) ($row['movement_type'] ?? 'unknown');
            $typeBreakdown[$type] = ($typeBreakdown[$type] ?? 0) + 1;
        }

        View::render('inventory/audit', [
            'company'          => $context->company(),
            'all_products'     => $allProducts,
            'movements'        => $movements,
            'type_breakdown'   => $typeBreakdown,
            'stock_summary'    => [
                'total_products'  => count($allProducts),
                'stock_controlled' => $stockControlled,
                'total_value'     => $totalStockValue,
                'low_stock'       => $lowStockCount,
                'out_of_stock'    => $outOfStockCount,
                'movement_count'  => count($movements),
            ],
            // current filter values for form repopulation
            'f_product_id'    => $productId,
            'f_movement_type' => $movementType,
            'f_from'          => $fromDate,
            'f_to'            => $toDate,
            'f_search'        => $search,
        ]);
    }

    public static function move(Database $db, RequestContext $context): void
    {
        $companyId = self::companyId($context);
        if ($companyId === null) { self::deny(); return; }

        $productId = (int) ($_POST['product_id'] ?? 0);
        $movementType = trim((string) ($_POST['movement_type'] ?? ''));
        $qtyRaw = trim((string) ($_POST['quantity'] ?? ''));
        $noteRaw = trim((string) ($_POST['note'] ?? ''));

        $allowedTypes = ['in', 'out', 'adjustment', 'sold', 'returned', 'destroyed'];
        $errors = [];
        if ($productId <= 0) { $errors[] = 'Product is required.'; }
        if (!in_array($movementType, $allowedTypes, true)) { $errors[] = 'Invalid movement type.'; }
        if (!is_numeric($qtyRaw) || (float) $qtyRaw <= 0) { $errors[] = 'Quantity must be positive.'; }

        $productModel = new Product($db->pdo());
        $movementModel = new InventoryMovement($db->pdo());

        if ($errors !== []) {
            http_response_code(422);
            View::render('inventory/index', [
                'company' => $context->company(),
                'products' => $productModel->listByCompany($companyId),
                'rows' => $movementModel->listByCompany($companyId),
                'token' => \App\Middleware\CsrfMiddleware::token(),
                'errors' => $errors,
            ]);
            return;
        }

        $qty = (float) $qtyRaw;
        // Stock out / sold / destroyed reduce stock; in / returned / adjustment increase it
        $reduceTypes = ['out', 'sold', 'destroyed'];
        $delta = in_array($movementType, $reduceTypes, true) ? -$qty : $qty;

        $db->pdo()->beginTransaction();
        try {
            // Capture before stock
            $productRow = $productModel->findByIdForCompany($productId, $companyId);
            $qtyBefore = (float) ($productRow['stock_qty'] ?? 0.0);

            $ok = $productModel->applyStockDelta($productId, $companyId, $delta);
            if (!$ok) {
                throw new \RuntimeException('Stock update failed.');
            }

            $qtyAfter = $qtyBefore + $delta;

            $movementId = $movementModel->createForCompany(
                $companyId,
                $productId,
                $movementType,
                $qty,
                $qtyBefore,
                $qtyAfter,
                $noteRaw !== '' ? $noteRaw : null,
                (int) ($_SESSION['user']['user_id'] ?? 0)
            );

            $db->pdo()->commit();
            AuditLogger::log($db, $context, 'inventory.move', 'inventory_movement', (string) $movementId, 'Movement ' . $movementType . ' qty=' . $qty);
        } catch (\Throwable $e) {
            if ($db->pdo()->inTransaction()) {
                $db->pdo()->rollBack();
            }
            http_response_code(500);
            echo 'Unable to save inventory movement.';
            return;
        }

        header('Location: /inventory');
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
