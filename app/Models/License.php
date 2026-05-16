<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Support\Facades\Log;
use PDO;

final class License
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function findActiveByCompanyAndDomain(int $companyId, string $companyName, string $domain): ?array
    {
        // Primary path: modern schema with company_id and status columns.
        try {
            $stmt = $this->pdo->prepare(
                'SELECT license_id, license_key, status, expiry_date, domain, plan, last_verified_at
                 FROM licenses
                 WHERE company_id = :company_id
                   AND domain = :domain
                   AND status = :status
                 ORDER BY created_at DESC, license_id DESC
                 LIMIT 1'
            );

            $stmt->execute([
                'company_id' => $companyId,
                'domain' => $domain,
                'status' => 'active',
            ]);

            $row = $stmt->fetch();
            if ($row) {
                return $row;
            }
        } catch (\PDOException $exception) {
            Log::warning('Using legacy license fallback; modern license schema query failed.', [
                'company_id' => $companyId,
                'domain' => $domain,
                'error' => $exception->getMessage(),
            ]);
            // Schema may be legacy (company_name / valid_until). We'll fallback below.
        }

        // Fallback path: legacy schema where license entries are keyed by company_name.
        try {
            $stmt = $this->pdo->prepare(
                                "SELECT license_id, license_key, 'active' AS status, valid_until AS expiry_date, domain, 'professional' AS plan, NULL AS last_verified_at
                 FROM licenses
                 WHERE company_name = :company_name
                   AND domain = :domain
                 ORDER BY license_id DESC
                                 LIMIT 1"
            );

            $stmt->execute([
                'company_name' => $companyName,
                'domain' => $domain,
            ]);

            $row = $stmt->fetch();
            return $row ?: null;
        } catch (\PDOException $exception) {
            return null;
        }
    }

    public function touchVerifiedAt(int $licenseId): void
    {
        try {
            $stmt = $this->pdo->prepare(
                'UPDATE licenses
                 SET last_verified_at = NOW()
                 WHERE license_id = :license_id'
            );

            $stmt->execute([
                'license_id' => $licenseId,
            ]);
        } catch (\Throwable $e) {
            error_log('[license_touch_error] ' . $e->getMessage());
            // Non-critical — suppress silently
        }
    }

    public function listByCompany(int $companyId): array
    {
        try {
            $stmt = $this->pdo->prepare(
                'SELECT license_id, license_key, domain, status, expiry_date, plan, last_verified_at, created_at
                 FROM licenses
                 WHERE company_id = :company_id
                 ORDER BY created_at DESC, license_id DESC'
            );

            $stmt->execute([
                'company_id' => $companyId,
            ]);

            return $stmt->fetchAll() ?: [];
        } catch (\Throwable $e) {
            error_log('[license_list_error] ' . $e->getMessage());
            return [];
        }
    }

    public function createForCompany(int $companyId, string $licenseKey, string $domain, string $expiryDate, string $plan = 'professional'): int
    {
        try {
            $deactivate = $this->pdo->prepare(
                'UPDATE licenses
                 SET status = :inactive
                 WHERE company_id = :company_id
                   AND domain = :domain
                   AND status = :active'
            );
            $deactivate->execute([
                'inactive' => 'inactive',
                'company_id' => $companyId,
                'domain' => $domain,
                'active' => 'active',
            ]);

            $stmt = $this->pdo->prepare(
                'INSERT INTO licenses (company_id, license_key, domain, status, expiry_date, plan, last_verified_at)
                 VALUES (:company_id, :license_key, :domain, :status, :expiry_date, :plan, NOW())'
            );

            $stmt->execute([
                'company_id' => $companyId,
                'license_key' => $licenseKey,
                'domain' => $domain,
                'status' => 'active',
                'expiry_date' => $expiryDate,
                'plan' => $plan,
            ]);

            return (int) $this->pdo->lastInsertId();
        } catch (\Throwable $e) {
            error_log('[license_create_error] ' . $e->getMessage());
            throw $e;
        }
    }

    public function createTrialForCompany(int $companyId, string $domain, int $trialDays = 7): int
    {
        $licenseKey = 'TRIAL-' . strtoupper(bin2hex(random_bytes(8)));
        $expiryDate = date('Y-m-d', strtotime('+' . max(1, $trialDays) . ' days'));

        try {
            $stmt = $this->pdo->prepare(
                'INSERT INTO licenses (company_id, license_key, domain, status, expiry_date, plan, last_verified_at)
                    VALUES (:company_id, :license_key, :domain, :status, :expiry_date, :plan, NOW())'
            );

            $stmt->execute([
                'company_id' => $companyId,
                'license_key' => $licenseKey,
                'domain' => $domain,
                'status' => 'active',
                'expiry_date' => $expiryDate,
                'plan' => 'professional',
            ]);

            return (int) $this->pdo->lastInsertId();
        } catch (\Throwable $e) {
            error_log('[license_trial_create_error] ' . $e->getMessage());
            throw $e;
        }
    }
}
