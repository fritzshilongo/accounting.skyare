<?php

namespace App\Console\Commands;

use App\Core\Database;
use App\Models\License;
use Illuminate\Console\Command;

class LicenseRevokeCommand extends Command
{
    protected $signature = 'license:revoke \\n        {--licenseId= : The ID of the license to revoke.} \\n        {--companyId= : The company ID whose licenses should be revoked.} \\n        {--domain= : The domain of the license to revoke.} \\n        {--all : Remove all licenses that match the selected company or domain.}';
    protected $description = 'Revoke an existing license record so it can be recreated.';

    public function handle(Database $db): int
    {
        $licenseId = trim((string) $this->option('licenseId'));
        $companyId = trim((string) $this->option('companyId'));
        $domain = trim((string) $this->option('domain'));
        $all = (bool) $this->option('all');

        if ($licenseId === '' && $companyId === '' && $domain === '') {
            $this->error('Provide at least --licenseId, --companyId, or --domain.');
            return self::FAILURE;
        }

        $licenseModel = new License($db->pdo());

        if ($licenseId !== '') {
            $deleted = $licenseModel->deleteById((int) $licenseId);
            if ($deleted) {
                $this->info('Deleted license ID ' . $licenseId);
                return self::SUCCESS;
            }

            $this->error('License ID ' . $licenseId . ' was not found.');
            return self::FAILURE;
        }

        if ($companyId !== '') {
            if ($all) {
                $deleted = $licenseModel->deleteByCompany((int) $companyId);
                $this->info('Deleted ' . $deleted . ' license(s) for company ID ' . $companyId . '.');
                return self::SUCCESS;
            }

            if ($domain !== '') {
                $deleted = $licenseModel->deleteByCompanyAndDomain((int) $companyId, $domain);
                if ($deleted) {
                    $this->info('Deleted license for company ID ' . $companyId . ' and domain ' . $domain . '.');
                    return self::SUCCESS;
                }
                $this->error('No license found for company ID ' . $companyId . ' and domain ' . $domain . '.');
                return self::FAILURE;
            }

            $this->error('For --companyId, also pass --all or --domain.');
            return self::FAILURE;
        }

        if ($domain !== '') {
            $deleted = $licenseModel->deleteByDomain($domain);
            if ($deleted) {
                $this->info('Deleted ' . $deleted . ' license(s) for domain ' . $domain . '.');
                return self::SUCCESS;
            }
            $this->error('No licenses found for domain ' . $domain . '.');
            return self::FAILURE;
        }

        $this->error('No valid delete condition provided.');
        return self::FAILURE;
    }
}
