<?php

namespace Database\Factories;

use App\Models\Invoice;
use App\Models\Payment;
use Illuminate\Database\Eloquent\Factories\Factory;

class PaymentFactory extends Factory
{
    protected $model = Payment::class;

    public function definition()
    {
        $invoiceId = Invoice::inRandomOrder()->value('invoice_id') ?? 1;

        return [
            'invoice_id' => $invoiceId,
            'amount' => $this->faker->randomFloat(2, 10, 1000),
            'payment_date' => $this->faker->date(),
            'method' => $this->faker->randomElement(['cash', 'credit_card', 'bank_transfer']),
        ];
    }
}
