<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Client;

class ClientSeeder extends Seeder
{
    public static $clientIds = [];

    public function run()
    {
        self::$clientIds = [];

        foreach (range(1, 10) as $index) {
            $client = Client::create([
                'type' => $index % 2 === 0 ? 'company' : 'individual',
                'name' => sprintf('Client %02d', $index),
                'contact_person' => sprintf('Contact %02d', $index),
                'email' => sprintf('client%02d@example.com', $index),
                'phone' => sprintf('+263771%04d', 1000 + $index),
                'address' => sprintf('%d Example Street, Harare', $index),
                'vat_number' => sprintf('VAT%08d', $index),
                'tax_number' => sprintf('TAX%08d', $index),
                'registration_number' => sprintf('REG-%04d', $index),
                'status' => 'active',
            ]);

            self::$clientIds[] = $client->client_id;
        }
    }
}
