<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\EstimateItem;
use App\Models\Estimate;
use App\Models\Product;

class EstimateItemSeeder extends Seeder
{
    public function run()
    {
        $products = Product::query()->get(['product_id', 'name', 'price']);

        foreach (Estimate::all() as $estimate) {
            $selectedProducts = $products->shuffle()->take(2)->values();

            foreach ($selectedProducts as $offset => $product) {
                EstimateItem::create([
                    'estimate_id' => $estimate->estimate_id,
                    'product_id' => $product->product_id,
                    'description' => $product->name,
                    'price' => $product->price,
                    'quantity' => $offset + 1,
                ]);
            }
        }
    }
}
