<?php

namespace Database\Factories;

use App\Models\Estimate;
use Illuminate\Database\Eloquent\Factories\Factory;

class EstimateFactory extends Factory
{
    protected $model = Estimate::class;

    public function definition()
    {
        $quantity = $this->faker->numberBetween(1, 100);
        $unit_price = $this->faker->randomFloat(2, 10, 1000);
        return [
            'company_id' => 1,
            'customer_id' => 1,
            'product_id' => 1,
            'client_id' => null, // to be set in seeder
            'client_name' => $this->faker->company,
            'quantity' => $quantity,
            'unit_price' => $unit_price,
            'amount' => $quantity * $unit_price,
            'estimate_date' => $this->faker->date(),
            'expiry_date' => $this->faker->dateTimeBetween('+1 week', '+1 month')->format('Y-m-d'),
            'status' => $this->faker->randomElement(['pending', 'approved', 'rejected']),
        ];
    }
}
