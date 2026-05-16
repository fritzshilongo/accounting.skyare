<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

class DevAccessSeeder extends Seeder
{
    public function run(): void
    {
        if (!Schema::hasTable('companies') || !Schema::hasTable('users')) {
            $this->command?->warn('Skipping DevAccessSeeder: companies/users table missing.');
            return;
        }

        $companyName = (string) env('DEV_BOOTSTRAP_COMPANY_NAME', 'Skyare Development');
        $subdomain = (string) env('DEV_BOOTSTRAP_SUBDOMAIN', 'www');

        $adminName = (string) env('DEV_BOOTSTRAP_ADMIN_NAME', 'Skyare Support');
        $adminEmail = (string) env('DEV_BOOTSTRAP_ADMIN_EMAIL', 'support@skyare.space');
        $adminPassword = (string) env('DEV_BOOTSTRAP_ADMIN_PASSWORD', 'Welcome@1');

        $company = DB::table('companies')->where('subdomain', $subdomain)->first();
        if (!$company) {
            $companyId = DB::table('companies')->insertGetId([
                'company_name' => $companyName,
                'subdomain' => $subdomain,
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } else {
            $companyId = (int) ($company->company_id ?? 0);
            if ($companyId <= 0) {
                $companyId = (int) DB::table('companies')->where('subdomain', $subdomain)->value('company_id');
            }
        }

        $columns = Schema::getColumnListing('users');
        $has = static fn (string $column): bool => in_array($column, $columns, true);

        $payload = [];
        if ($has('company_id')) {
            $payload['company_id'] = $companyId > 0 ? $companyId : null;
        }
        if ($has('full_name')) {
            $payload['full_name'] = $adminName;
        }
        if ($has('name')) {
            $payload['name'] = $adminName;
        }
        if ($has('email')) {
            $payload['email'] = $adminEmail;
        }
        if ($has('password_hash')) {
            $payload['password_hash'] = Hash::make($adminPassword);
        }
        if ($has('password')) {
            $payload['password'] = Hash::make($adminPassword);
        }
        if ($has('role_key')) {
            $payload['role_key'] = 'admin';
        }
        if ($has('is_active')) {
            $payload['is_active'] = 1;
        }
        if ($has('email_verified_at')) {
            $payload['email_verified_at'] = now();
        }
        if ($has('updated_at')) {
            $payload['updated_at'] = now();
        }

        $existing = DB::table('users')->where('email', $adminEmail)->first();
        if ($existing) {
            DB::table('users')->where('email', $adminEmail)->update($payload);
        } else {
            if ($has('created_at')) {
                $payload['created_at'] = now();
            }
            DB::table('users')->insert($payload);
        }

        $this->command?->info('Dev access ensured.');
        $this->command?->line('Email: ' . $adminEmail);
        $this->command?->line('Password: ' . $adminPassword);
        $this->command?->line('Subdomain: ' . $subdomain);
    }
}
