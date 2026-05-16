<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Core\RequestContext;
use App\Core\Database;
use App\Models\Company;
use App\Models\License;

class SettingsController extends Controller
{
    public function index(Request $request, RequestContext $context, Database $db)
    {
        $company = $context->company();
        if (!$company) {
            return response('Company not found', 404);
        }

        $settings = $this->buildSettingsPayload($db, (int) $company['company_id'], $company);
        $companyModel = new Company($db->pdo());
        $missingBankColumns = $companyModel->missingColumns(['bank_account_type', 'bank_branch_code']);

        return view('settings.index', [
            'company' => $company,
            'settings' => $settings,
            'missingBankColumns' => $missingBankColumns,
            'saved' => (bool) $request->query('saved', false),
        ]);
    }

    public function update(Request $request, RequestContext $context, Database $db)
    {
        $company = $context->company();
        if (!$company) {
            return response('Company not found', 404);
        }

        $validated = $request->validate([
            'company_name' => 'required|string|max:191',
            'registration_number' => 'nullable|string|max:191',
            'email' => 'nullable|email|max:191',
            'phone' => 'nullable|string|max:191',
            'address' => 'nullable|string|max:191',
            'city' => 'nullable|string|max:191',
            'province' => 'nullable|string|max:191',
            'postal_code' => 'nullable|string|max:191',
            'country' => 'nullable|string|max:191',
            'tax_number' => 'nullable|string|max:191',
            'vat_number' => 'nullable|string|max:191',
            'bank_name' => 'nullable|string|max:191',
            'bank_account_holder' => 'nullable|string|max:191',
            'bank_account_number' => 'nullable|string|max:191',
            'bank_account_type' => 'nullable|string|max:191',
            'bank_branch_code' => 'nullable|string|max:191',
            'bank_routing_number' => 'nullable|string|max:191',
            'bank_swift_code' => 'nullable|string|max:191',
            'bank_iban' => 'nullable|string|max:191',
            'tax_type' => 'nullable|string|max:50',
            'tax_rate' => 'nullable|numeric|min:0|max:100',
            'invoice_prefix' => 'nullable|string|max:20',
            'next_invoice_number' => 'nullable|integer|min:1|max:999999',
            'default_payment_terms' => 'nullable|integer|min:0|max:365',
            'payment_methods' => 'nullable|array',
            'payment_methods.*' => 'string|max:50',
            'logo' => 'nullable|file|image|max:2048',
        ]);

        $companyModel = new Company($db->pdo());
        // Handle logo upload as base64 data URI
        $current = $companyModel->findById((int) $company['company_id']);
        $logoData = $current['logo_data'] ?? $company['logo_data'] ?? null;

        if ($request->hasFile('logo') && $request->file('logo')->isValid()) {
            $logoFile = $request->file('logo');
            $mime = $logoFile->getMimeType();
            $contents = file_get_contents($logoFile->getRealPath());
            if ($contents !== false) {
                $logoData = 'data:' . $mime . ';base64,' . base64_encode($contents);
            }
        }

        $validated['logo_data'] = $logoData;

        if (!$companyModel->updateProfile((int) $company['company_id'], $validated)) {
            return back()->withErrors(['bank' => 'Unable to save banking details. Please run database migrations and try again.'])->withInput();
        }

        $this->persistPreference($db, (int) $company['company_id'], 'tax_type', (string) ($validated['tax_type'] ?? 'VAT'));
        $this->persistPreference($db, (int) $company['company_id'], 'tax_rate', (string) ($validated['tax_rate'] ?? '10'));
        $this->persistPreference($db, (int) $company['company_id'], 'invoice_prefix', (string) ($validated['invoice_prefix'] ?? 'INV-'));
        $this->persistPreference($db, (int) $company['company_id'], 'next_invoice_number', (string) ($validated['next_invoice_number'] ?? '1001'));
        $this->persistPreference($db, (int) $company['company_id'], 'default_payment_terms', (string) ($validated['default_payment_terms'] ?? '7'));
        $this->persistPreference($db, (int) $company['company_id'], 'payment_methods', json_encode(array_values($validated['payment_methods'] ?? ['bank_transfer', 'credit_card', 'cash']), JSON_THROW_ON_ERROR));

        return redirect('/settings?saved=1');
    }

    private function buildSettingsPayload(Database $db, int $companyId, array $company): array
    {
        $preferences = $this->loadPreferences($db, $companyId);

        return [
            'company' => $company,
            'tax_settings' => [
                'tax_type' => $preferences['tax_type'] ?? 'VAT',
                'tax_rate' => (float) ($preferences['tax_rate'] ?? 10),
            ],
            'invoice_settings' => [
                'next_invoice_number' => (int) ($preferences['next_invoice_number'] ?? 1001),
                'invoice_prefix' => $preferences['invoice_prefix'] ?? 'INV-',
                'default_payment_terms' => (int) ($preferences['default_payment_terms'] ?? 7),
            ],
            'payment_methods' => $this->decodePaymentMethods($preferences['payment_methods'] ?? null),
        ];
    }

    private function loadPreferences(Database $db, int $companyId): array
    {
        try {
            $stmt = $db->pdo()->prepare('SELECT preference_key, preference_value FROM company_preferences WHERE company_id = :company_id');
            $stmt->execute(['company_id' => $companyId]);
            $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];
        } catch (\Throwable $e) {
            return [];
        }

        $preferences = [];
        foreach ($rows as $row) {
            $preferences[$row['preference_key']] = $row['preference_value'];
        }

        return $preferences;
    }

    private function persistPreference(Database $db, int $companyId, string $key, string $value): void
    {
        try {
            $stmt = $db->pdo()->prepare(
                'INSERT INTO company_preferences (company_id, preference_key, preference_value, created_at, updated_at)
                 VALUES (:company_id, :preference_key, :preference_value, NOW(), NOW())
                 ON DUPLICATE KEY UPDATE preference_value = VALUES(preference_value), updated_at = NOW()'
            );

            $stmt->execute([
                'company_id' => $companyId,
                'preference_key' => $key,
                'preference_value' => $value,
            ]);
        } catch (\Throwable $e) {
            error_log("persistPreference({$key}) failed: " . $e->getMessage());
        }
    }

    private function decodePaymentMethods(?string $encoded): array
    {
        if ($encoded === null || $encoded === '') {
            return ['bank_transfer', 'credit_card', 'check', 'cash'];
        }

        try {
            $decoded = json_decode($encoded, true, 512, JSON_THROW_ON_ERROR);
            return is_array($decoded) && $decoded !== [] ? array_values($decoded) : ['bank_transfer', 'credit_card', 'check', 'cash'];
        } catch (\Throwable $e) {
            return ['bank_transfer', 'credit_card', 'check', 'cash'];
        }
    }

    public function issueLicense(Request $request, RequestContext $context, Database $db)
    {
        if (!$this->isLicenseIssuer($context)) {
            return response('Not authorized.', 403);
        }

        $validated = $request->validate([
            'company_id' => 'required|integer|min:1',
            'plan' => 'required|string|in:3months,6months,yearly',
        ]);

        $companyId = (int) $validated['company_id'];
        $plan = $validated['plan'];

        $companyModel = new Company($db->pdo());
        $targetCompany = $companyModel->findById($companyId);

        if (!$targetCompany) {
            return back()->withErrors(['company_id' => 'Selected company was not found.'])->withInput();
        }

        $issuerCompanyId = (int) ($context->company()['company_id'] ?? 0);
        if ($companyId === $issuerCompanyId) {
            return back()->withErrors(['company_id' => 'Please select a tenant company, not the issuer account.'])->withInput();
        }

        $baseDomain = $context->appConfig()['base_domain'] ?? 'skyare.space';
        $domain = trim((string) ($targetCompany['subdomain'] ?? '')) !== ''
            ? trim((string) ($targetCompany['subdomain'] ?? '')) . '.' . $baseDomain
            : request()->getHost();

        $months = match ($plan) {
            '3months' => 3,
            '6months' => 6,
            default => 12,
        };

        $expiryDate = date('Y-m-d', strtotime('+' . $months . ' months'));
        $licenseKey = 'LIC-' . strtoupper(bin2hex(random_bytes(8)));

        try {
            $licenseModel = new License($db->pdo());
            $licenseModel->createForCompany($companyId, $licenseKey, $domain, $expiryDate, $plan);
        } catch (\Throwable $e) {
            return back()->withErrors(['license' => 'Unable to issue license: ' . $e->getMessage()])->withInput();
        }

        $request->session()->flash('success', "License issued for {$targetCompany['company_name']} ({$domain}) until {$expiryDate}.");
        return redirect('/settings/license');
    }

    public function license(RequestContext $context, Database $db)
    {
        $company = $context->company();
        if (!$company) {
            return response('Company not found', 404);
        }
        $companyId = (int) $company['company_id'];
        $companyModel = new Company($db->pdo());

        $licenseModel = new License($db->pdo());
        $host = request()->getHost();
        $license = $licenseModel->findActiveByCompanyAndDomain($companyId, (string) ($company['company_name'] ?? ''), $host);

        // If no license exists at all for this company, auto-provision a trial
        if (!$license) {
            try {
                $allLicenses = $licenseModel->listByCompany($companyId);
                if (empty($allLicenses)) {
                    $licenseModel->createTrialForCompany($companyId, $host, 14);
                    $license = $licenseModel->findActiveByCompanyAndDomain($companyId, (string) ($company['company_name'] ?? ''), $host);
                }
            } catch (\Throwable $e) {
                // table may not exist yet — continue with null license
            }
        }

        // Pricing table for display
        $plans = [
            '3months' => ['label' => '3 Months', 'price' => 'N$750', 'months' => 3],
            '6months' => ['label' => '6 Months', 'price' => 'N$1,450', 'months' => 6],
            'yearly'  => ['label' => '1 Year', 'price' => 'N$2,850', 'months' => 12],
        ];

        $showIssuerUI = $this->isLicenseIssuer($context);
        $issuerCompanies = [];
        if ($showIssuerUI) {
            $baseDomain = $context->appConfig()['base_domain'] ?? 'skyare.space';
            $issuerCompanies = $companyModel->findAllCompanies();
            $issuerCompanies = array_map(function (array $issuerCompany) use ($db, $licenseModel, $baseDomain) {
                $issuerCompany['license'] = $licenseModel->findActiveByCompanyAndDomain(
                    (int) $issuerCompany['company_id'], 
                    (string) ($issuerCompany['company_name'] ?? ''), 
                    trim((string) ($issuerCompany['subdomain'] ?? '')) . '.' . $baseDomain
                );
                $issuerCompany['license_status'] = $issuerCompany['license'] ? ($issuerCompany['license']['status'] ?? 'active') : 'none';
                $issuerCompany['license_expiry'] = $issuerCompany['license'] ? ($issuerCompany['license']['expiry_date'] ?? $issuerCompany['license']['valid_until'] ?? null) : null;
                $issuerCompany['activity_count'] = $this->tenantActivityCount($db, (int) $issuerCompany['company_id']);
                return $issuerCompany;
            }, $issuerCompanies);
        }

        return view('settings.license', [
            'company' => $company,
            'license' => $license,
            'plans' => $plans,
            'showIssuerUI' => $showIssuerUI,
            'issuerCompanies' => $issuerCompanies,
        ]);
    }

    public function toggleTenantStatus(Request $request, RequestContext $context, Database $db)
    {
        if (!$this->isLicenseIssuer($context)) {
            return response('Not authorized.', 403);
        }

        $validated = $request->validate([
            'company_id' => 'required|integer|min:1',
            'action' => 'required|string|in:enable,disable',
        ]);

        $companyId = (int) $validated['company_id'];
        $action = $validated['action'];
        $currentCompanyId = (int) ($context->company()['company_id'] ?? 0);

        if ($companyId === $currentCompanyId) {
            return back()->withErrors(['company_id' => 'Cannot change the status of the issuer tenant.']);
        }

        $companyModel = new Company($db->pdo());
        $targetCompany = $companyModel->findById($companyId);
        if (!$targetCompany) {
            return back()->withErrors(['company_id' => 'Selected company was not found.'])->withInput();
        }

        $newStatus = $action === 'enable' ? 'active' : 'inactive';
        if (!$companyModel->updateStatus($companyId, $newStatus)) {
            return back()->withErrors(['company_status' => 'Unable to update tenant status.'])->withInput();
        }

        $request->session()->flash('success', ucfirst($newStatus) . 'd tenant ' . ($targetCompany['company_name'] ?? '')); 
        return redirect('/settings/license');
    }

    private function tenantActivityCount(Database $db, int $companyId): int
    {
        try {
            $stmt = $db->pdo()->prepare(
                'SELECT COUNT(*) AS cnt
                 FROM audit_logs
                 WHERE company_id = :company_id
                   AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)'
            );
            $stmt->execute(['company_id' => $companyId]);
            $row = $stmt->fetch();
            return (int) ($row['cnt'] ?? 0);
        } catch (\Throwable $e) {
            return 0;
        }
    }

    private function isLicenseIssuer(RequestContext $context): bool
    {
        $appConfig = $context->appConfig();
        $baseDomain = strtolower((string) ($appConfig['base_domain'] ?? ''));
        $issuerSubdomain = trim((string) ($appConfig['license_issuer_subdomain'] ?? 'www'));
        $host = strtolower($context->host());

        if ($baseDomain === '') {
            return false;
        }

        if ($host === $baseDomain) {
            return $issuerSubdomain === 'www';
        }

        if (str_ends_with($host, '.' . $baseDomain)) {
            $subdomain = substr($host, 0, -strlen('.' . $baseDomain));
            return $subdomain === $issuerSubdomain;
        }

        return false;
    }
}
