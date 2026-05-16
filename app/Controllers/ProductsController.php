<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\AuditLogger;
use App\Core\Database;
use App\Core\RequestContext;
use App\Core\View;
use App\Models\Product;

final class ProductsController
{
    public static function index(Database $db, RequestContext $context): void
    {
        $companyId = self::companyId($context);
        if ($companyId === null) { self::deny(); return; }

        $model = new Product($db->pdo());
        View::render('products/index', [
            'company' => $context->company(),
            'rows' => $model->listByCompany($companyId),
            'token' => \App\Middleware\CsrfMiddleware::token(),
            'errors' => [],
            'old' => ['sku' => '', 'product_name' => '', 'description' => '', 'unit_price' => '0.00', 'stock_control_type' => 'STOCK_CONTROLLED', 'is_active' => '1'],
        ]);
    }

    public static function store(Database $db, RequestContext $context): void
    {
        $companyId = self::companyId($context);
        if ($companyId === null) { self::deny(); return; }

        [$sku, $name, $description, $price, $stockControlType, $active, $errors] = self::input();
        $model = new Product($db->pdo());

        if ($errors !== []) {
            http_response_code(422);
            View::render('products/index', [
                'company' => $context->company(),
                'rows' => $model->listByCompany($companyId),
                'token' => \App\Middleware\CsrfMiddleware::token(),
                'errors' => $errors,
                'old' => ['sku' => $sku ?? '', 'product_name' => $name, 'description' => $description ?? '', 'unit_price' => (string) $price, 'stock_control_type' => $stockControlType, 'is_active' => $active ? '1' : '0'],
            ]);
            return;
        }

        $qty = 0.0;
        $id = $model->createForCompany($companyId, $sku, $name, $price, $qty, $stockControlType, $active, $description);
        AuditLogger::log($db, $context, 'product.create', 'product', (string) $id, 'Created product: ' . $name);

        // Stock adjustments are managed only in Inventory module.

        header('Location: /products');
    }

    public static function edit(Database $db, RequestContext $context): void
    {
        $companyId = self::companyId($context);
        if ($companyId === null) { self::deny(); return; }

        $id = (int) ($_GET['product_id'] ?? 0);
        $model = new Product($db->pdo());
        $row = $model->findByIdForCompany($id, $companyId);
        if ($row === null) { http_response_code(404); echo 'Product not found.'; return; }

        View::render('products/edit', ['company' => $context->company(), 'row' => $row, 'token' => \App\Middleware\CsrfMiddleware::token(), 'errors' => []]);
    }

    public static function update(Database $db, RequestContext $context): void
    {
        $companyId = self::companyId($context);
        if ($companyId === null) { self::deny(); return; }

        $id = (int) ($_POST['product_id'] ?? 0);
        [$sku, $name, $description, $price, $stockControlType, $active, $errors] = self::input();
        $model = new Product($db->pdo());

        if ($errors !== []) {
            http_response_code(422);
            View::render('products/edit', [
                'company' => $context->company(),
                'row' => ['product_id' => $id, 'sku' => $sku, 'product_name' => $name, 'description' => $description, 'unit_price' => $price, 'stock_control_type' => $stockControlType, 'is_active' => $active ? 1 : 0],
                'token' => \App\Middleware\CsrfMiddleware::token(),
                'errors' => $errors,
            ]);
            return;
        }

        $model->updateForCompany($id, $companyId, $sku, $name, $price, $stockControlType, $active, $description);
        AuditLogger::log($db, $context, 'product.update', 'product', (string) $id, 'Updated product: ' . $name);
        header('Location: /products');
    }

    public static function delete(Database $db, RequestContext $context): void
    {
        $companyId = self::companyId($context);
        if ($companyId === null) { self::deny(); return; }

        $id = (int) ($_POST['product_id'] ?? 0);
        $model = new Product($db->pdo());
        $model->deleteForCompany($id, $companyId);
        AuditLogger::log($db, $context, 'product.delete', 'product', (string) $id, 'Deleted product');
        header('Location: /products');
    }

    private static function input(): array
    {
        $skuRaw = trim((string) ($_POST['sku'] ?? ''));
        $name = trim((string) ($_POST['product_name'] ?? ''));
        $description = trim((string) ($_POST['description'] ?? ''));
        $priceRaw = trim((string) ($_POST['unit_price'] ?? '0'));
        $stockControlType = trim((string) ($_POST['stock_control_type'] ?? 'STOCK_CONTROLLED'));
        $active = (string) ($_POST['is_active'] ?? '1') === '1';

        $errors = [];
        if ($name === '') { $errors[] = 'Product name is required.'; }
        if (!is_numeric($priceRaw) || (float) $priceRaw < 0) { $errors[] = 'Unit price must be 0 or greater.'; }
        if (!in_array($stockControlType, ['STOCK_CONTROLLED', 'STOCK_NOT_CONTROLLED'], true)) { $errors[] = 'Invalid stock control type.'; }

        return [$skuRaw !== '' ? $skuRaw : null, $name, $description !== '' ? $description : null, (float) $priceRaw, $stockControlType, $active, $errors];
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
