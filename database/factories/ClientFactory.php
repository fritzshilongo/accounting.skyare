<?php

namespace Database\Factories;

use App\Models\Client;
use Illuminate\Database\Eloquent\Factories\Factory;

class ClientFactory extends Factory
{
    protected $model = Client::class;

    public function definition()
    {
        return [
            'type' => $this->faker->randomElement(['individual', 'company']),
            'name' => $this->faker->company,
            'contact_person' => $this->faker->name,
            'email' => $this->faker->unique()->safeEmail,
            'phone' => $this->faker->phoneNumber,
            'address' => $this->faker->address,
            'vat_number' => $this->faker->bothify('VAT########'),
            'tax_number' => $this->faker->bothify('TAX########'),
            'registration_number' => $this->faker->uuid,
        ];
    }
}
