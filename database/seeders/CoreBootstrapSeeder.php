<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CoreBootstrapSeeder extends Seeder
{
    public function run(): void
    {
        $companyName  = (string) env('DEV_BOOTSTRAP_COMPANY_NAME', 'Skyare');
        $subdomain    = (string) env('DEV_BOOTSTRAP_SUBDOMAIN', 'www');
        $adminName    = (string) env('DEV_BOOTSTRAP_ADMIN_NAME', 'Skyare Admin');
        $adminEmail   = (string) env('DEV_BOOTSTRAP_ADMIN_EMAIL', 'support@skyare.space');
        $adminPassword = (string) env('DEV_BOOTSTRAP_ADMIN_PASSWORD', 'Welcome@1');
        $licenseDomain = (string) env('DEV_BOOTSTRAP_LICENSE_DOMAIN', 'skyare.space');
        $passwordHash = password_hash($adminPassword, PASSWORD_DEFAULT);

        $existingCompanyId = DB::table('companies')
            ->where('subdomain', $subdomain)
            ->value('company_id');

        if ($existingCompanyId === null) {
            $companyId = DB::table('companies')->insertGetId([
                'company_name' => $companyName,
                'subdomain' => $subdomain,
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ], 'company_id');
        } else {
            $companyId = (int) $existingCompanyId;

            DB::table('companies')
                ->where('company_id', $companyId)
                ->update([
                    'company_name' => $companyName,
                    'status' => 'active',
                    'updated_at' => now(),
                ]);
        }

        DB::table('users')->updateOrInsert(
            ['email' => $adminEmail],
            [
                'name' => $adminName,
                'full_name' => $adminName,
                'company_id' => $companyId,
                'password' => $passwordHash,
                'password_hash' => $passwordHash,
                'role_key' => 'admin',
                'is_active' => 1,
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );

        // Insert a 1-year trial license for the production domain
        $appDomain = parse_url((string) env('APP_URL', 'https://skyare.space'), PHP_URL_HOST) ?: 'skyare.space';

        $existingLicense = DB::table('licenses')
            ->where('company_id', $companyId)
            ->where('domain', $appDomain)
            ->first();

        if (!$existingLicense) {
            DB::table('licenses')->insert([
                'company_id'       => $companyId,
                'license_key'      => strtoupper(bin2hex(random_bytes(16))),
                'company_name'     => $companyName,
                'domain'           => $appDomain,
                'status'           => 'active',
                'plan'             => 'trial',
                'expiry_date'      => now()->addDays(14)->toDateString(),
                'valid_until'      => now()->addDays(14)->toDateString(),
                'last_verified_at' => now(),
                'created_at'       => now(),
                'updated_at'       => now(),
            ]);
        } else {
            DB::table('licenses')
                ->where('license_id', $existingLicense->license_id)
                ->update([
                    'status'           => 'active',
                    'plan'             => 'trial',
                    'expiry_date'      => now()->addDays(14)->toDateString(),
                    'valid_until'      => now()->addDays(14)->toDateString(),
                    'last_verified_at' => now(),
                    'updated_at'       => now(),
                ]);
        }
    }
}