<?php

namespace App\Console\Commands;

use App\Core\Database;
use App\Models\Company;
use App\Models\License;
use Illuminate\Console\Command;

class LicenseIssueCommand extends Command
{
    protected $signature = 'license:issue {companyId} {plan=professional} {--months=12} {--domain=} {--expiry=}';
    protected $description = 'Issue a new license for a company tenant.';

    public function handle(Database $db): int
    {
        $companyId = (int) $this->argument('companyId');
        $plan = trim((string) $this->argument('plan')) ?: 'professional';
        $domain = trim((string) $this->option('domain'));
        $expiry = trim((string) $this->option('expiry'));
        $months = max(1, (int) $this->option('months'));

        if ($companyId <= 0) {
            $this->error('companyId must be a positive integer.');
            return self::FAILURE;
        }

        $companyModel = new Company($db->pdo());
        $company = $companyModel->findById($companyId);
        if (!$company) {
            $this->error('Company not found for companyId ' . $companyId . '.');
            return self::FAILURE;
        }

        if ($domain === '') {
            $subdomain = trim((string) ($company['subdomain'] ?? ''));
            if ($subdomain === '') {
                $this->error('No domain was provided and the company has no subdomain.');
                return self::FAILURE;
            }

            $baseDomain = getenv('APP_BASE_DOMAIN') ?: 'skyare.space';
            $domain = $subdomain . '.' . $baseDomain;
        }

        if ($expiry === '') {
            $expiry = date('Y-m-d', strtotime('+' . $months . ' months'));
        }

        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $expiry) || strtotime($expiry) === false) {
            $this->error('Expiry date must be in YYYY-MM-DD format.');
            return self::FAILURE;
        }

        $licenseKey = 'LIC-' . strtoupper(bin2hex(random_bytes(8)));
        $licenseModel = new License($db->pdo());

        try {
            $licenseId = $licenseModel->createForCompany(
                $companyId,
                $licenseKey,
                $domain,
                $expiry,
                $plan
            );
        } catch (\Throwable $e) {
            $this->error('License creation failed: ' . $e->getMessage());
            return self::FAILURE;
        }

        $this->info('License created successfully.');
        $this->line('License ID: ' . $licenseId);
        $this->line('License Key: ' . $licenseKey);
        $this->line('Domain: ' . $domain);
        $this->line('Expiry: ' . $expiry);
        $this->line('Plan: ' . $plan);

        return self::SUCCESS;
    }
}
