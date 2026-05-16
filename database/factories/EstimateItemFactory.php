<?php

namespace Database\Factories;

use App\Models\EstimateItem;
use App\Models\Estimate;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

class EstimateItemFactory extends Factory
{
    protected $model = EstimateItem::class;

    public function definition()
    {
        $estimateId = Estimate::inRandomOrder()->value('estimate_id') ?? 1;
        $productId = Product::inRandomOrder()->value('product_id') ?? 1;

        return [
            'estimate_id' => $estimateId,
            'product_id' => $productId,
            'description' => $this->faker->sentence,
            'price' => $this->faker->randomFloat(2, 10, 1000),
            'quantity' => $this->faker->numberBetween(1, 100),
        ];
    }
}
