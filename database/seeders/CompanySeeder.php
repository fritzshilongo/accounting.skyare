<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CompanySeeder extends Seeder
{
    public function run(): void
    {
        DB::table('companies')->insert([
            'company_id' => 1,
            'company_name' => 'Skyare Demo Company',
            'subdomain' => 'demo',
            'status' => 'active',
            'registration_number' => 'REG-0001',
            'phone' => '+263771000001',
            'email' => 'info@skyare.test',
            'address' => '1 Enterprise Way',
            'city' => 'Harare',
            'province' => 'Harare',
            'postal_code' => '0000',
            'country' => 'Zimbabwe',
            'tax_number' => 'TAX00000001',
            'vat_number' => 'VAT00000001',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}