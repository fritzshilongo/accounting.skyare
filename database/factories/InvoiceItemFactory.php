<?php

namespace Database\Factories;

use App\Models\InvoiceItem;
use Illuminate\Database\Eloquent\Factories\Factory;

class InvoiceItemFactory extends Factory
{
    protected $model = InvoiceItem::class;

    public function definition()
    {
        $quantity = $this->faker->numberBetween(1, 10);
        $unit_price = $this->faker->randomFloat(2, 10, 1000);
        return [
            'invoice_id' => null, // to be set in seeder
            'product_id' => null, // to be set in seeder
            'quantity' => $quantity,
            'unit_price' => $unit_price,
            'line_total' => $quantity * $unit_price,
        ];
    }
}
