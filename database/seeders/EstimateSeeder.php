<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Estimate;
use Database\Seeders\ClientSeeder;
use Illuminate\Support\Facades\Schema;

class EstimateSeeder extends Seeder
{
    public function run()
    {
        $columns = array_flip(Schema::getColumnListing('estimates'));
        $clientIds = ClientSeeder::$clientIds;
        foreach (range(1, 10) as $i) {
            $quantity = $i + 1;
            $unit_price = 40 + ($i * 12.5);
            $amount = $quantity * $unit_price;
            $tax = round($amount * 0.15, 2); // 15% VAT
            $payload = [
                'company_id' => 1,
                'client_id' => $clientIds[array_rand($clientIds)],
                'client_name' => sprintf('Estimate Client %02d', $i),
                'quantity' => $quantity,
                'unit_price' => $unit_price,
                'amount' => $amount,
                'tax_amount' => $tax,
                'total' => $amount + $tax,
                'estimate_date' => now()->subDays($i)->toDateString(),
                'expiry_date' => now()->addDays($i + 7)->toDateString(),
                'status' => ['pending', 'approved', 'rejected'][($i - 1) % 3],
            ];
            Estimate::create(array_intersect_key($payload, $columns));
        }
    }
}
