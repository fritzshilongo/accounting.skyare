<?php

declare(strict_types=1);

namespace App\Core;

use App\Models\Company;

final class RequestContext
{
    private array $appConfig;
    private ?array $company = null;
    private string $host = 'localhost';

    public function __construct(array $appConfig)
    {
        $this->appConfig = $appConfig;
    }

    public function boot(Database $db): void
    {
        $host = strtolower($_SERVER['HTTP_HOST'] ?? 'localhost');
        $this->host = explode(':', $host)[0];

        $reservedSubdomains = $this->appConfig['reserved_subdomains'] ?? [];
        if (!is_array($reservedSubdomains)) {
            $reservedSubdomains = array_filter(array_map('trim', explode(',', (string) $reservedSubdomains)));
        }

        $subdomain = $this->appConfig['default_subdomain'] ?? 'www';
        $baseDomain = strtolower((string) ($this->appConfig['base_domain'] ?? ''));

        if ($baseDomain !== '' && $this->host !== $baseDomain && str_ends_with($this->host, '.' . $baseDomain)) {
            $derived = substr($this->host, 0, -strlen('.' . $baseDomain));
            if ($derived !== '') {
                $subdomain = $derived;
            }
        } else {
            $parts = explode('.', $this->host);
            if (count($parts) > 2) {
                $subdomain = $parts[0];
            } elseif (count($parts) === 2 && $parts[1] === 'localhost' && $parts[0] !== 'localhost') {
            // Support tenant.localhost in local development.
                $subdomain = $parts[0];
            }
        }

        if (in_array($subdomain, $reservedSubdomains, true) && $this->host !== $baseDomain) {
            abort(404, 'Reserved subdomain.');
        }

        if (!class_exists(Company::class, true)) {
            $companyModelPath = __DIR__ . '/../Models/Company.php';
            if (is_file($companyModelPath)) {
                require_once $companyModelPath;
            }
        }

        if (!class_exists(Company::class, false)) {
            throw new \RuntimeException('Unable to autoload ' . Company::class . '. Please rebuild Composer autoload and restart PHP.');
        }

        $companyModel = new Company($db->pdo());
        $this->company = $companyModel->findBySubdomain($subdomain, true);

        if ($this->company === null && $subdomain !== ($this->appConfig['default_subdomain'] ?? 'www')) {
            $inactiveCompany = $companyModel->findBySubdomain($subdomain, false);
            if ($inactiveCompany !== null && ($inactiveCompany['status'] ?? '') !== 'active') {
                abort(403, 'Tenant is disabled.');
            }

            $this->company = $companyModel->findBySubdomain($this->appConfig['default_subdomain'] ?? 'www', true);
        }

        if ($this->company === null && $this->host !== $baseDomain) {
            $this->company = $companyModel->findAny();
        }
    }

    public function appConfig(): array
    {
        return $this->appConfig;
    }

    public function host(): string
    {
        return $this->host;
    }

    public function company(): ?array
    {
        return $this->company;
    }

    public function isLicenseIssuer(): bool
    {
        $appConfig = $this->appConfig;
        $baseDomain = strtolower((string) ($appConfig['base_domain'] ?? ''));
        if ($baseDomain === '') {
            return false;
        }

        $issuerSubdomain = trim((string) ($appConfig['license_issuer_subdomain'] ?? 'www'));
        $host = strtolower($this->host);

        if ($host === $baseDomain) {
            return $issuerSubdomain === 'www';
        }

        if (str_ends_with($host, '.' . $baseDomain)) {
            $subdomain = substr($host, 0, -strlen('.' . $baseDomain));
            return $subdomain === $issuerSubdomain;
        }

        return false;
    }

    public function licenseConfig(): array
    {
        return [
            'server_url' => $this->appConfig['license_server_url'] ?? '',
            'verify_timeout_seconds' => (int) ($this->appConfig['license_verify_timeout'] ?? 5),
            'grace_hours' => (int) ($this->appConfig['license_grace_hours'] ?? 72),
        ];
    }
}
