<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use App\Core\Database;
use App\Core\RequestContext;
use App\Models\License;

class LicenseCheckMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        try {
            $context = app(RequestContext::class);
            $db = app(Database::class);
        } catch (\Throwable $e) {
            return $next($request);
        }

        $company = $context->company();
        if (!$company) {
            return $next($request);
        }

        $companyId = (int) ($company['company_id'] ?? 0);
        if ($companyId <= 0) {
            return $next($request);
        }

        if ($context->isLicenseIssuer()) {
            $path = trim($request->path(), '/');
            if (!$this->isIssuerAllowedPath($path)) {
                return redirect('/settings/license');
            }
            return $next($request);
        }

        if (!Schema::hasTable('licenses')) {
            abort(503, 'License system unavailable.');
        }

        $host = $request->getHost();
        $licenseModel = new License($db->pdo());

        try {
            $license = $licenseModel->findActiveByCompanyAndDomain(
                $companyId,
                (string) ($company['company_name'] ?? ''),
                $host
            );
        } catch (\Throwable $e) {
            Log::error('License verification failed unexpectedly', [
                'company_id' => $companyId,
                'domain' => $host,
                'error' => $e->getMessage(),
            ]);
            abort(503, 'License system unavailable.');
        }

        if (!$license) {
            return redirect('/license-required');
        }

        $expiry = $license['expiry_date'] ?? $license['valid_until'] ?? null;
        if ($expiry && strtotime($expiry) < strtotime('-3 days')) {
            return redirect('/license-required');
        }

        return $next($request);
    }

    private function isIssuerAllowedPath(string $path): bool
    {
        if ($path === '' || $path === 'dashboard' || $path === 'dashboard/checks') {
            return true;
        }

        $allowedPrefixes = [
            'settings',
            'audit',
            'users',
            'profile',
            'notifications',
            'logout',
        ];

        foreach ($allowedPrefixes as $prefix) {
            if ($path === $prefix || str_starts_with($path, $prefix . '/')) {
                return true;
            }
        }

        return false;
    }
}
