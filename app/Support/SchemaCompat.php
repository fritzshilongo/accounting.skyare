<?php

namespace App\Support;

use Illuminate\Support\Facades\Schema;

final class SchemaCompat
{
    private static array $cache = [];

    public static function hasColumn(string $table, string $column): bool
    {
        $key = $table . '.' . $column;
        if (array_key_exists($key, self::$cache)) {
            return self::$cache[$key];
        }

        try {
            self::$cache[$key] = Schema::hasColumn($table, $column);
        } catch (\Throwable $e) {
            self::$cache[$key] = false;
        }

        return self::$cache[$key];
    }

    public static function firstExisting(string $table, array $columns, ?string $default = null): ?string
    {
        foreach ($columns as $column) {
            if (self::hasColumn($table, $column)) {
                return $column;
            }
        }

        return $default;
    }

    public static function invoiceAmountSql(): string
    {
        $hasTotal = self::hasColumn('invoices', 'total');
        $hasAmount = self::hasColumn('invoices', 'amount');

        if ($hasTotal && $hasAmount) {
            return 'COALESCE(total, amount)';
        }
        if ($hasTotal) {
            return 'total';
        }
        if ($hasAmount) {
            return 'amount';
        }

        return '0';
    }

    public static function invoiceNoColumn(): ?string
    {
        return self::firstExisting('invoices', ['invoice_no']);
    }

    public static function invoiceClientNameColumn(): ?string
    {
        return self::firstExisting('invoices', ['client_name']);
    }

    public static function productNameColumn(): string
    {
        return self::firstExisting('products', ['name', 'product_name'], 'name') ?? 'name';
    }

    public static function productPriceColumn(): string
    {
        return self::firstExisting('products', ['price', 'unit_price'], 'price') ?? 'price';
    }

    public static function productSkuColumn(): ?string
    {
        return self::firstExisting('products', ['sku']);
    }

    public static function productStockQtyColumn(): ?string
    {
        return self::firstExisting('products', ['stock_qty']);
    }

    public static function supportsCompany(string $table): bool
    {
        return self::hasColumn($table, 'company_id');
    }
}
