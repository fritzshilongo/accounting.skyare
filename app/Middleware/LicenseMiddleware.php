<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\Database;
use App\Core\RemoteService;
use App\Core\RequestContext;
use App\Core\View;
use App\Models\License;

final class LicenseMiddleware
{
    public function handle(Database $db, RequestContext $context): bool
    {
        $company = $context->company();
        if ($company === null) {
            $this->deny(404, 'Company not found for this request.', null, $context, []);
            return false;
        }

        $companyId = (int) ($company['company_id'] ?? 0);
        if ($companyId <= 0) {
            $this->deny(403, 'Invalid tenant context.', $company, $context, []);
            return false;
        }

        $host = $context->host();
        $licenseModel = new License($db->pdo());
        $license = $licenseModel->findActiveByCompanyAndDomain(
            $companyId,
            (string) ($company['company_name'] ?? ''),
            $host
        );

        // 7-day free trial: full access from company creation date, no license required.
        $trialDays = 7;
        $companyCreatedAt = (string) ($company['created_at'] ?? '');
        $withinFreeTrial = false;
        if ($companyCreatedAt !== '') {
            $createdTs = strtotime($companyCreatedAt);
            if ($createdTs !== false && time() < ($createdTs + $trialDays * 86400)) {
                $withinFreeTrial = true;
            }
        }

        if ($license === null) {
            if ($withinFreeTrial) {
                return true; // Full access during free trial window
            }
            $this->deny(403, 'No active license found for this domain.', $company, $context, []);
            return false;
        }

        if ($this->isExpired($license)) {
            if ($withinFreeTrial) {
                return true; // Still within free trial even if issued license expired
            }
            $this->deny(403, 'License has expired.', $company, $context, []);
            return false;
        }
        
        if (!$this->isPathAllowedByPlan((string) ($license['plan'] ?? 'professional'), (string) parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH))) {
            $this->deny(403, 'Your license plan does not include this module.', $company, $context, []);
            return false;
        }

        if ($this->shouldBypassRemote($context, $host)) {
            return true;
        }

        $config = $context->licenseConfig();
        $cachePath = $this->cachePath($companyId);
        $cache = $this->readCache($cachePath);

        $result = RemoteService::postJson(
            (string) ($config['server_url'] ?? ''),
            [
                'license_key' => $license['license_key'],
                'domain' => $host,
                'company_id' => $companyId,
            ],
            (int) ($config['verify_timeout_seconds'] ?? 5)
        );

        if (($result['ok'] ?? false) === true && $this->isRemoteValid($result['body'] ?? [])) {
            $licenseModel->touchVerifiedAt((int) $license['license_id']);
            $this->writeCache($cachePath, [
                'last_success_at' => time(),
                'grace_until' => time() + (((int) ($config['grace_hours'] ?? 72)) * 3600),
                'license_id' => (int) $license['license_id'],
                'status' => 'valid',
            ]);

            return true;
        }

        if (($result['ok'] ?? false) === true) {
            $this->writeCache($cachePath, [
                'last_success_at' => $cache['last_success_at'] ?? null,
                'grace_until' => $cache['grace_until'] ?? null,
                'license_id' => (int) $license['license_id'],
                'status' => 'invalid',
            ]);

            $this->deny(403, 'License verification failed.', $company, $context, $cache);
            return false;
        }

        if ($this->isWithinGrace($cache) || $this->isWithinDbGrace($license, (int) ($config['grace_hours'] ?? 72))) {
            return true;
        }

        $this->deny(503, 'License server is unavailable and grace period has ended.', $company, $context, $cache);
        return false;
    }

    private function deny(int $statusCode, string $message, ?array $company, RequestContext $context, array $cache): void
    {
        $graceUntil = (int) ($cache['grace_until'] ?? 0);
        $response = View::render('system/license_required', [
            'message' => $message,
            'company' => $company,
            'host' => $context->host(),
            'grace_until' => $graceUntil > 0 ? date('Y-m-d H:i:s', $graceUntil) : null,
        ], $statusCode);
        $response->send();
    }

    private function isExpired(array $license): bool
    {
        $expiryDate = trim((string) ($license['expiry_date'] ?? $license['valid_until'] ?? ''));
        if ($expiryDate === '') {
            return true;
        }

        $today = date('Y-m-d');
        return $expiryDate < $today;
    }

    private function isRemoteValid(array $body): bool
    {
        if (isset($body['valid'])) {
            return (bool) $body['valid'];
        }

        if (($body['status'] ?? null) === 'active') {
            return true;
        }

        return ($body['ok'] ?? false) === true;
    }

    private function isPathAllowedByPlan(string $plan, string $path): bool
    {
        $plan = strtolower(trim($plan));
        if ($plan === '' || $plan === 'enterprise') {
            return true;
        }

        $restrictedForStarter = [
            '/audit-trail',
            '/exchange-rates',
            '/credit-management',
            '/users',
        ];

        if ($plan === 'starter') {
            foreach ($restrictedForStarter as $prefix) {
                if (str_starts_with($path, $prefix)) {
                    return false;
                }
            }
        }

        return true;
    }

    private function cachePath(int $companyId): string
    {
        return dirname(__DIR__, 2) . '/storage/cache/license-' . $companyId . '.json';
    }

    private function readCache(string $cachePath): array
    {
        if (!is_file($cachePath)) {
            return [];
        }

        $raw = file_get_contents($cachePath);
        if ($raw === false || $raw === '') {
            return [];
        }

        $decoded = json_decode($raw, true);
        return is_array($decoded) ? $decoded : [];
    }

    private function writeCache(string $cachePath, array $payload): void
    {
        $dir = dirname($cachePath);
        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }

        file_put_contents($cachePath, json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    }

    private function isWithinGrace(array $cache): bool
    {
        $graceUntil = (int) ($cache['grace_until'] ?? 0);
        return $graceUntil > time();
    }

    private function isWithinDbGrace(array $license, int $graceHours): bool
    {
        $lastVerifiedAt = (string) ($license['last_verified_at'] ?? '');
        if ($lastVerifiedAt === '') {
            return false;
        }

        $verifiedTs = strtotime($lastVerifiedAt);
        if ($verifiedTs === false) {
            return false;
        }

        return ($verifiedTs + max(1, $graceHours) * 3600) > time();
    }

    private function shouldBypassRemote(RequestContext $context, string $host): bool
    {
        $appEnv = strtolower((string) ($context->appConfig()['app_env'] ?? 'production'));
        if ($appEnv === 'production') {
            return false;
        }

        return $host === 'localhost' || str_ends_with($host, '.localhost');
    }
}
