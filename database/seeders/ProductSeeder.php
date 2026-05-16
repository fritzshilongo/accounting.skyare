<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Product;
use Illuminate\Support\Facades\Schema;

class ProductSeeder extends Seeder
{
    public function run()
    {
        $columns = array_flip(Schema::getColumnListing('products'));

        foreach (range(1, 10) as $index) {
            $payload = [
                'company_id' => 1,
                'sku' => sprintf('SKU-%03d', $index),
                'name' => sprintf('Product %02d', $index),
                'description' => sprintf('Seeded product %02d description.', $index),
                'price' => 25 + ($index * 7.5),
                'type' => $index % 3 === 0 ? 'service' : 'product',
                'stock_control_type' => $index % 3 === 0 ? 'STOCK_NOT_CONTROLLED' : 'STOCK_CONTROLLED',
                'stock_qty' => $index % 3 === 0 ? 0 : 50 + ($index * 5),
                'is_active' => true,
            ];

            Product::create(array_intersect_key($payload, $columns));
        }
    }
}
