<?php

namespace Database\Factories;

use App\Models\Invoice;
use Illuminate\Database\Eloquent\Factories\Factory;

class InvoiceFactory extends Factory
{
    protected $model = Invoice::class;

    public function definition()
    {
        $amount = $this->faker->randomFloat(2, 100, 10000);
        $tax = round($amount * 0.15, 2);
        return [
            'company_id' => 1,
            'client_id' => null, // to be set in seeder
            'client_name' => $this->faker->company,
            'invoice_no' => $this->faker->unique()->numerify('INV####'),
            'amount' => $amount,
            'tax_amount' => $tax,
            'total' => $amount + $tax,
            'issue_date' => $this->faker->date(),
        ];
    }
}
