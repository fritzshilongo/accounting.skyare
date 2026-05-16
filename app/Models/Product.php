<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\Schema;

class Product extends Model
{
    use HasFactory;
    protected $primaryKey = 'product_id';
    private static ?bool $productsHasCompanyId = null;

    protected $fillable = [
        'company_id',
        'sku',
        'name',
        'description',
        'price',
        'type',
        'stock_control_type',
        'stock_qty',
        'is_active',
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public static function supportsCompanyScope(): bool
    {
        if (self::$productsHasCompanyId !== null) {
            return self::$productsHasCompanyId;
        }

        try {
            self::$productsHasCompanyId = Schema::hasColumn('products', 'company_id');
        } catch (\Throwable $e) {
            self::$productsHasCompanyId = false;
        }

        return self::$productsHasCompanyId;
    }

    public function scopeForCompany($query, int $companyId)
    {
        if (self::supportsCompanyScope()) {
            $query->where('company_id', $companyId);
        }

        return $query;
    }

    public static function hasAvailableStock(int $productId, float $quantity, ?int $companyId = null): bool
    {
        return self::canDeductStock($productId, $quantity, $companyId);
    }

    public static function canDeductStock(int $productId, float $quantity, ?int $companyId = null): bool
    {
        $query = self::where('product_id', $productId);
        if ($companyId !== null && $companyId > 0 && self::supportsCompanyScope()) {
            $query->where('company_id', $companyId);
        }
        $product = $query->first();
        if ($product === null) {
            return false;
        }
        if ($product->stock_control_type !== 'STOCK_CONTROLLED') {
            return true;
        }
        return ($product->stock_qty - $quantity) >= 0;
    }

    // Compatibility getters/setters for legacy code that uses product_name
    public function getProductNameAttribute()
    {
        return $this->attributes['product_name'] ?? $this->attributes['name'] ?? null;
    }

    public function setProductNameAttribute($value)
    {
        $this->attributes['name'] = $value;
    }

    // Compatibility getters/setters for legacy code that uses unit_price
    public function getUnitPriceAttribute()
    {
        return $this->attributes['unit_price'] ?? $this->attributes['price'] ?? null;
    }

    public function setUnitPriceAttribute($value)
    {
        $this->attributes['price'] = $value;
    }

    public function getNameAttribute()
    {
        return $this->attributes['name'] ?? $this->attributes['product_name'] ?? null;
    }

    public function setNameAttribute($value)
    {
        $this->attributes['name'] = $value;
    }

    public function getPriceAttribute()
    {
        return $this->attributes['price'] ?? $this->attributes['unit_price'] ?? null;
    }

    public function setPriceAttribute($value)
    {
        $this->attributes['price'] = $value;
    }

    public function listAll(int $limit = 200): array
    {
        return self::query()
            ->orderByDesc('product_id')
            ->limit($limit)
            ->get()
            ->map(static fn (self $product): array => self::toLegacyRow($product))
            ->all();
    }

    public function listByCompany(int $companyId, int $limit = 200): array
    {
        return self::query()
            ->forCompany($companyId)
            ->orderByDesc('product_id')
            ->limit($limit)
            ->get()
            ->map(static fn (self $product): array => self::toLegacyRow($product))
            ->all();
    }

    public function findByIdForCompany(int $productId, int $companyId): ?array
    {
        $product = self::query()
            ->where('product_id', $productId)
            ->forCompany($companyId)
            ->first();

        return $product instanceof self ? self::toLegacyRow($product) : null;
    }

    public function createForCompany(
        int $companyId,
        ?string $sku,
        string $name,
        float $price,
        float $stockQty,
        string $stockControlType,
        bool $active,
        ?string $description
    ): int {
        $payload = [
            'sku' => $sku,
            'name' => $name,
            'description' => $description,
            'price' => $price,
            'type' => $stockControlType === 'STOCK_CONTROLLED' ? 'product' : 'service',
            'stock_control_type' => $stockControlType,
            'stock_qty' => $stockQty,
            'is_active' => $active,
        ];

        if (self::supportsCompanyScope()) {
            $payload['company_id'] = $companyId;
        }

        $product = self::query()->create($payload);

        return (int) $product->product_id;
    }

    public function updateForCompany(
        int $productId,
        int $companyId,
        ?string $sku,
        string $name,
        float $price,
        string $stockControlType,
        bool $active,
        ?string $description
    ): bool {
        $product = self::query()
            ->where('product_id', $productId)
            ->forCompany($companyId)
            ->first();

        if (!$product instanceof self) {
            return false;
        }

        return (bool) $product->update([
            'sku' => $sku,
            'name' => $name,
            'description' => $description,
            'price' => $price,
            'type' => $stockControlType === 'STOCK_CONTROLLED' ? 'product' : 'service',
            'stock_control_type' => $stockControlType,
            'is_active' => $active,
        ]);
    }

    public function deleteForCompany(int $productId, int $companyId): bool
    {
        $query = self::query()->where('product_id', $productId)->forCompany($companyId);

        return $query->update(['is_active' => false]) > 0;
    }

    public static function applyStockDelta(int $productId, int|float $companyIdOrDelta, ?float $delta = null): bool
    {
        $companyId = null;
        if ($delta === null) {
            $delta = (float) $companyIdOrDelta;
        } else {
            $companyId = (int) $companyIdOrDelta;
        }

        $query = self::query()->where('product_id', $productId);
        if ($companyId !== null && $companyId > 0 && self::supportsCompanyScope()) {
            $query->where('company_id', $companyId);
        }

        $product = $query->first();
        if (!$product instanceof self) {
            return false;
        }
        if ($product->stock_control_type === 'STOCK_CONTROLLED' && ($product->stock_qty + $delta) < 0) {
            return false;
        }

        $product->stock_qty = max(0, (float) $product->stock_qty + $delta);
        return $product->save();
    }

    private static function toLegacyRow(self $product): array
    {
        return [
            'product_id' => (int) $product->product_id,
            'company_id' => (int) ($product->company_id ?? 0),
            'sku' => $product->sku,
            'product_name' => (string) ($product->name ?? ''),
            'description' => $product->description,
            'unit_price' => (float) ($product->price ?? 0),
            'stock_qty' => (float) ($product->stock_qty ?? 0),
            'stock_control_type' => (string) ($product->stock_control_type ?? 'STOCK_CONTROLLED'),
            'is_active' => (int) ((bool) $product->is_active),
            'created_at' => (string) ($product->created_at ?? ''),
            'updated_at' => (string) ($product->updated_at ?? ''),
        ];
    }

}
