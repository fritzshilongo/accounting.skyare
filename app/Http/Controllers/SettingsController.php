<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Core\Mailer;
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

        $issuerCompanyId = (int) ($context->company()['company_id'] ?? 0);

        $validated = $request->validate([
            'company_id' => [
                'required',
                'integer',
                'min:1',
                Rule::notIn([$issuerCompanyId]),
            ],
            'plan' => 'required|string|in:3months,6months,yearly',
        ], [
            'company_id.not_in' => 'Please select a tenant company, not the issuer account.',
        ]);

        $companyId = (int) $validated['company_id'];
        $plan = $validated['plan'];

        $companyModel = new Company($db->pdo());
        $targetCompany = $companyModel->findById($companyId);

        if (!$targetCompany) {
            return back()->withErrors(['company_id' => 'Selected company was not found.'])->withInput();
        }

        if (($targetCompany['status'] ?? '') === 'deleted') {
            return back()->withErrors(['company_id' => 'Cannot issue a license to a deleted tenant. Restore the tenant first.'])->withInput();
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

    public function createTenant(Request $request, RequestContext $context, Database $db)
    {
        if (!$this->isLicenseIssuer($context)) {
            return response('Not authorized.', 403);
        }

        $validated = $request->validate([
            'company_name' => 'required|string|max:191',
            'subdomain' => ['required', 'string', 'min:3', 'max:32', 'regex:/^[a-z0-9\-]{3,32}$/'],
            'admin_email' => 'required|email|max:191',
            'admin_password' => 'nullable|string|min:8|max:64',
        ], [
            'subdomain.regex' => 'Subdomain must be 3-32 characters and may only contain lowercase letters, numbers, or hyphens.',
        ]);

        $companyName = trim($validated['company_name']);
        $subdomain = strtolower(trim($validated['subdomain']));
        $adminEmail = trim(strtolower($validated['admin_email']));
        $adminPassword = trim((string) ($validated['admin_password'] ?? ''));

        $companyModel = new Company($db->pdo());
        $existingCompany = $companyModel->findBySubdomain($subdomain, false);
        if ($existingCompany) {
            return back()->withErrors(['subdomain' => 'That subdomain is already taken.'])->withInput();
        }

        $appConfig = $context->appConfig();
        $issuerSubdomain = trim((string) ($appConfig['license_issuer_subdomain'] ?? 'www'));
        if ($subdomain === $issuerSubdomain || $subdomain === 'www') {
            return back()->withErrors(['subdomain' => 'That subdomain is reserved and cannot be used for a tenant.'])->withInput();
        }

        if ($adminPassword === '') {
            $adminPassword = bin2hex(random_bytes(5));
        }

        $licenseModel = new License($db->pdo());
        $userModel = new \App\Models\User($db->pdo());

        $pdo = $db->pdo();
        $newUserId = null;
        try {
            $pdo->beginTransaction();

            $companyId = $companyModel->create($companyName, $subdomain);
            $companyModel->updateProfile($companyId, ['email' => $adminEmail]);

            $newUserId = $userModel->createWithPassword($companyId, $companyName . ' Admin', $adminEmail, $adminPassword, 'admin');

            $baseDomain = trim((string) ($appConfig['base_domain'] ?? 'skyare.space'), '.');
            $tenantHost = $subdomain . '.' . $baseDomain;
            $licenseModel->createTrialForCompany($companyId, $tenantHost, 14);

            $pdo->commit();
        } catch (\Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            error_log('[tenant_create_failed] ' . $e->getMessage());
            return back()->withErrors(['company_name' => 'Unable to create tenant. Please try again.'])->withInput();
        }

        $loginUrl = request()->getScheme() . '://' . $tenantHost . '/login';
        $resetToken = '';
        if ($newUserId !== null) {
            $resetToken = $this->createPasswordResetToken($db->pdo(), $newUserId);
        }
        $resetLink = $resetToken !== '' ? request()->getScheme() . '://' . $tenantHost . '/reset-password?token=' . urlencode($resetToken) : '';

        $body = '<h2>Welcome to Skyare</h2>'
              . '<p>Your tenant workspace has been created successfully.</p>'
              . '<p><strong>Company:</strong> ' . htmlspecialchars($companyName, ENT_QUOTES) . '</p>'
              . '<p><strong>Tenant login:</strong> <a href="' . htmlspecialchars($loginUrl, ENT_QUOTES) . '">' . htmlspecialchars($loginUrl, ENT_QUOTES) . '</a></p>'
              . '<p><strong>Login email:</strong> ' . htmlspecialchars($adminEmail, ENT_QUOTES) . '</p>'
              . '<p><strong>Temporary password:</strong> ' . htmlspecialchars($adminPassword, ENT_QUOTES) . '</p>';

        if ($resetLink !== '') {
            $body .= '<p><strong>Set password link:</strong> <a href="' . htmlspecialchars($resetLink, ENT_QUOTES) . '">' . htmlspecialchars($resetLink, ENT_QUOTES) . '</a></p>';
        }

        $body .= '<p>Your account has been issued a 14-day free trial. After login, please update your password and company settings.</p>'
              . '<p>If you have any questions, email support@skyare.space.</p>';

        try {
            Mailer::send($adminEmail, 'Your Skyare tenant is ready', $body);
        } catch (\Throwable $e) {
            error_log('[tenant_welcome_email_failed] to=' . $adminEmail . ' error=' . $e->getMessage());
        }

        return back()->with('success', 'Tenant created successfully. Login details have been sent to ' . $adminEmail);
    }

    private function createPasswordResetToken(\PDO $pdo, int $userId): string
    {
        $rawToken = bin2hex(random_bytes(32));
        $hashedToken = hash('sha256', $rawToken);
        $expiresAt = date('Y-m-d H:i:s', time() + (int) env('PASSWORD_RESET_EXPIRY_SECONDS', 7200));

        try {
            $invalidate = $pdo->prepare('UPDATE password_resets SET used_at = NOW() WHERE user_id = :user_id AND used_at IS NULL');
            $invalidate->execute(['user_id' => $userId]);
            $stmt = $pdo->prepare('INSERT INTO password_resets (user_id, token, expires_at, ip) VALUES (:user_id, :token, :expires_at, :ip)');
            $stmt->execute(['user_id' => $userId, 'token' => $hashedToken, 'expires_at' => $expiresAt, 'ip' => $_SERVER['REMOTE_ADDR'] ?? '']);
        } catch (\Throwable $e) {
            error_log('[tenant_password_reset_token_failed] ' . $e->getMessage());
            return '';
        }

        return $rawToken;
    }

    public function editTenant(int $companyId, RequestContext $context, Database $db)
    {
        if (!$this->isLicenseIssuer($context)) {
            return response('Not authorized.', 403);
        }

        $companyModel = new Company($db->pdo());
        $issuerCompanyId = (int) ($context->company()['company_id'] ?? 0);
        if ($companyId === $issuerCompanyId) {
            return response('Cannot edit the issuer tenant.', 403);
        }

        $company = $companyModel->findById($companyId);
        if (!$company) {
            return response('Tenant company not found.', 404);
        }

        return view('settings.tenant-edit', [
            'company' => $company,
        ]);
    }

    public function updateTenant(Request $request, RequestContext $context, Database $db)
    {
        if (!$this->isLicenseIssuer($context)) {
            return response('Not authorized.', 403);
        }

        $validated = $request->validate([
            'company_id' => 'required|integer|min:1',
            'company_name' => 'required|string|max:191',
            'admin_email' => 'required|email|max:191',
            'admin_password' => 'nullable|string|min:8|max:64',
        ]);

        $companyId = (int) $validated['company_id'];
        $issuerCompanyId = (int) ($context->company()['company_id'] ?? 0);
        if ($companyId === $issuerCompanyId) {
            return back()->withErrors(['company_id' => 'Cannot edit the issuer tenant.'])->withInput();
        }

        $companyModel = new Company($db->pdo());
        $targetCompany = $companyModel->findById($companyId);
        if (!$targetCompany) {
            return back()->withErrors(['company_id' => 'Tenant company not found.'])->withInput();
        }

        $newEmail = trim(strtolower($validated['admin_email']));
        $newName = trim($validated['company_name']);

        $userModel = new \App\Models\User($db->pdo());
        $primaryUser = $userModel->findPrimaryByCompany($companyId);

        $pdo = $db->pdo();
        try {
            $pdo->beginTransaction();

            if (!$companyModel->updateProfile($companyId, ['company_name' => $newName, 'email' => $newEmail])) {
                throw new \RuntimeException('Unable to update tenant profile.');
            }

            if ($primaryUser) {
                if ($primaryUser['email'] !== $newEmail) {
                    if (!$userModel->updateEmail((int) $primaryUser['user_id'], $newEmail)) {
                        throw new \RuntimeException('Unable to update tenant admin email.');
                    }
                }
                if (!empty($validated['admin_password'])) {
                    if (!$userModel->updatePassword((int) $primaryUser['user_id'], $validated['admin_password'])) {
                        throw new \RuntimeException('Unable to update tenant admin password.');
                    }
                }
            }

            $pdo->commit();
        } catch (\Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            return back()->withErrors(['company_name' => $e->getMessage()])->withInput();
        }

        return redirect('/settings/license')->with('success', 'Tenant details updated successfully.');
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
        $statusFilter = trim((string) request()->query('status', 'all'));
        $allowedStatusFilters = ['all', 'active', 'inactive', 'deleted'];
        if (!in_array($statusFilter, $allowedStatusFilters, true)) {
            $statusFilter = 'all';
        }

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
                $tenantHost = trim((string) ($issuerCompany['subdomain'] ?? '')) !== ''
                    ? trim((string) ($issuerCompany['subdomain'] ?? '')) . '.' . $baseDomain
                    : $baseDomain;
                $issuerCompany['tenant_login_url'] = request()->getScheme() . '://' . $tenantHost . '/login';
                $issuerCompany['tenant_forgot_password_url'] = request()->getScheme() . '://' . $tenantHost . '/forgot-password';
                $issuerCompany['tenant_contact_email'] = $this->resolveTenantContactEmail($db, $issuerCompany);

                $issuerCompany['tenant_license_count'] = 0;
                $issuerCompany['tenant_last_license_date'] = null;
                $issuerCompany['tenant_last_license_plan'] = null;
                $issuerCompany['tenant_last_license_key'] = null;
                $issuerCompany['tenant_active_license_plan'] = null;
                try {
                    $licenseStats = $db->pdo()->prepare(
                        'SELECT COUNT(*) AS total, MAX(created_at) AS last_issued_at
                         FROM licenses
                         WHERE company_id = :cid'
                    );
                    $licenseStats->execute(['cid' => (int) $issuerCompany['company_id']]);
                    $stats = $licenseStats->fetch();
                    if ($stats) {
                        $issuerCompany['tenant_license_count'] = (int) ($stats['total'] ?? 0);
                        $issuerCompany['tenant_last_license_date'] = $stats['last_issued_at'] ? date('M j, Y', strtotime($stats['last_issued_at'])) : null;
                    }

                    $latestLicense = $db->pdo()->prepare(
                        'SELECT license_key, plan, status
                         FROM licenses
                         WHERE company_id = :cid
                         ORDER BY created_at DESC
                         LIMIT 1'
                    );
                    $latestLicense->execute(['cid' => (int) $issuerCompany['company_id']]);
                    $latest = $latestLicense->fetch();
                    if ($latest) {
                        $issuerCompany['tenant_last_license_plan'] = trim((string) ($latest['plan'] ?? '')) ?: null;
                        $issuerCompany['tenant_last_license_key'] = trim((string) ($latest['license_key'] ?? '')) ?: null;
                    }

                    $activeLicense = $db->pdo()->prepare(
                        'SELECT license_key, plan
                         FROM licenses
                         WHERE company_id = :cid
                           AND status = :status
                         ORDER BY created_at DESC
                         LIMIT 1'
                    );
                    $activeLicense->execute(['cid' => (int) $issuerCompany['company_id'], 'status' => 'active']);
                    $active = $activeLicense->fetch();
                    if ($active) {
                        $issuerCompany['tenant_active_license_plan'] = trim((string) ($active['plan'] ?? '')) ?: null;
                    }
                } catch (\Throwable $e) {
                    // ignore license summary failures
                }

                $issuerCompany['tenant_user_count'] = 0;
                $issuerCompany['tenant_last_login_at'] = null;
                try {
                    $userStats = $db->pdo()->prepare(
                        'SELECT COUNT(*) AS total, MAX(last_login_at) AS last_login_at
                         FROM users
                         WHERE company_id = :cid'
                    );
                    $userStats->execute(['cid' => (int) $issuerCompany['company_id']]);
                    $userRow = $userStats->fetch();
                    if ($userRow) {
                        $issuerCompany['tenant_user_count'] = (int) ($userRow['total'] ?? 0);
                        $issuerCompany['tenant_last_login_at'] = $userRow['last_login_at'] ? date('M j, Y', strtotime($userRow['last_login_at'])) : null;
                    }
                } catch (\Throwable $e) {
                    // ignore user summary failures
                }

                $issuerCompany['activity_count'] = $this->tenantActivityCount($db, (int) $issuerCompany['company_id']);
                return $issuerCompany;
            }, $issuerCompanies);

            $issuerCompanies = array_filter($issuerCompanies, fn(array $issuerCompany) => (int) ($issuerCompany['company_id'] ?? 0) !== $companyId);

            if ($statusFilter !== 'all') {
                $issuerCompanies = array_filter($issuerCompanies, fn(array $issuerCompany) => ($issuerCompany['status'] ?? '') === $statusFilter);
            }
        }

        return view('settings.license', [
            'company' => $company,
            'license' => $license,
            'plans' => $plans,
            'showIssuerUI' => $showIssuerUI,
            'issuerCompanies' => $issuerCompanies,
            'statusFilter' => $statusFilter,
        ]);
    }

    public function sendTenantReset(Request $request, RequestContext $context, Database $db)
    {
        if (!$this->isLicenseIssuer($context)) {
            return response('Not authorized.', 403);
        }

        $validated = $request->validate([
            'company_id' => 'required|integer|min:1',
        ]);

        $companyId = (int) $validated['company_id'];
        $issuerCompanyId = (int) ($context->company()['company_id'] ?? 0);
        if ($companyId === $issuerCompanyId) {
            return back()->withErrors(['company_id' => 'Please select a tenant company, not the issuer account.'])->withInput();
        }

        $companyModel = new Company($db->pdo());
        $targetCompany = $companyModel->findById($companyId);
        if (!$targetCompany) {
            return back()->withErrors(['company_id' => 'Selected company was not found.'])->withInput();
        }

        if (($targetCompany['status'] ?? '') === 'deleted') {
            return back()->withErrors(['company_id' => 'Cannot send reset instructions to a deleted tenant. Restore the tenant first.'])->withInput();
        }

        $email = $this->resolveTenantContactEmail($db, $targetCompany);
        if ($email === '') {
            return back()->withErrors(['company_id' => 'No valid contact email is configured for this tenant. Please update the tenant contact email first.']);
        }

        $baseDomain = $context->appConfig()['base_domain'] ?? 'skyare.space';
        $tenantHost = trim((string) ($targetCompany['subdomain'] ?? '')) !== ''
            ? trim((string) ($targetCompany['subdomain'] ?? '')) . '.' . $baseDomain
            : $baseDomain;
        $resetLink = request()->getScheme() . '://' . $tenantHost . '/forgot-password';

        $body = '<h2>Password reset information for ' . htmlspecialchars($targetCompany['company_name'] ?? 'your tenant', ENT_QUOTES) . '</h2>'
              . '<p>This message was sent by the license issuer for your tenant workspace.</p>'
              . '<p>To reset a password for your tenant, use the link below:</p>'
              . '<p><a href="' . htmlspecialchars($resetLink, ENT_QUOTES) . '" style="display:inline-block;padding:12px 24px;background:#12807a;color:#fff;border-radius:8px;text-decoration:none;font-weight:bold;">Reset Tenant Password</a></p>'
              . '<p>If you did not expect this email, please contact your tenant administrator.</p>';

        try {
            Mailer::send($email, 'Tenant Password Reset Instructions', $body);
        } catch (\Throwable $e) {
            error_log('[tenant_reset_email_failed] to=' . $email . ' error=' . $e->getMessage());
            return back()->withErrors(['company_id' => 'Unable to send email to tenant contact.']);
        }

        return back()->with('success', 'Password reset instructions sent to ' . $email);
    }

    public function updateTenantEmail(Request $request, RequestContext $context, Database $db)
    {
        if (!$this->isLicenseIssuer($context)) {
            return response('Not authorized.', 403);
        }

        $validated = $request->validate([
            'company_id' => 'required|integer|min:1',
            'email' => 'required|email|max:191',
        ]);

        $companyId = (int) $validated['company_id'];
        $issuerCompanyId = (int) ($context->company()['company_id'] ?? 0);
        if ($companyId === $issuerCompanyId) {
            return back()->withErrors(['company_id' => 'Please select a tenant company, not the issuer account.'])->withInput();
        }

        $companyModel = new Company($db->pdo());
        $targetCompany = $companyModel->findById($companyId);
        if (!$targetCompany) {
            return back()->withErrors(['company_id' => 'Selected tenant company was not found.'])->withInput();
        }

        if (($targetCompany['status'] ?? '') === 'deleted') {
            return back()->withErrors(['company_id' => 'Cannot update contact email for a deleted tenant. Restore the tenant first.'])->withInput();
        }

        $newEmail = strtolower(trim($validated['email']));
        $userModel = new \App\Models\User($db->pdo());
        $targetUser = null;
        $currentEmail = trim((string) ($targetCompany['email'] ?? ''));

        if ($currentEmail !== '' && filter_var($currentEmail, FILTER_VALIDATE_EMAIL)) {
            $targetUser = $userModel->findByEmailAndCompany($currentEmail, $companyId);
        }

        if (!$targetUser) {
            $targetUser = $userModel->findPrimaryByCompany($companyId);
        }

        try {
            $pdo = $db->pdo();
            $pdo->beginTransaction();

            if ($targetUser) {
                if ($targetUser['email'] !== $newEmail) {
                    $existingUser = $userModel->findByEmail($newEmail);
                    if ($existingUser && (int) $existingUser['user_id'] !== (int) $targetUser['user_id']) {
                        $pdo->rollBack();
                        return back()->withErrors(['email' => 'That email is already in use by another user.'])->withInput();
                    }

                    if (!$userModel->updateEmail((int) $targetUser['user_id'], $newEmail)) {
                        $pdo->rollBack();
                        return back()->withErrors(['email' => 'Unable to update tenant login email.'])->withInput();
                    }
                }
            }

            if (!$companyModel->updateProfile($companyId, ['email' => $newEmail])) {
                $pdo->rollBack();
                return back()->withErrors(['email' => 'Unable to save tenant contact email.'])->withInput();
            }

            $pdo->commit();
        } catch (\Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            return back()->withErrors(['email' => 'Unable to update tenant email. Please try again.'])->withInput();
        }

        return back()->with('success', 'Tenant contact and login email updated to ' . $newEmail);
    }

    private function resolveTenantContactEmail(Database $db, array $company): string
    {
        $email = trim((string) ($company['email'] ?? ''));
        if ($email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return $email;
        }

        try {
            $userStmt = $db->pdo()->prepare(
                'SELECT email
                 FROM users
                 WHERE company_id = :cid
                   AND email IS NOT NULL
                   AND email != ""
                 ORDER BY FIELD(role_key, "admin", "manager", "user", "viewer") DESC, id ASC
                 LIMIT 1'
            );
            $userStmt->execute(['cid' => (int) ($company['company_id'] ?? 0)]);
            $userEmailRow = $userStmt->fetch();
            if ($userEmailRow && !empty($userEmailRow['email']) && filter_var(trim((string) $userEmailRow['email']), FILTER_VALIDATE_EMAIL)) {
                return trim((string) $userEmailRow['email']);
            }
        } catch (\Throwable $e) {
            // ignore fallback email failures
        }

        return '';
    }

    public function licenseHistory(int $companyId, RequestContext $context, Database $db)
    {
        if (!$this->isLicenseIssuer($context)) {
            return response('Not authorized.', 403);
        }

        $companyModel = new Company($db->pdo());
        $company = $companyModel->findById($companyId);
        if (!$company) {
            return response('Tenant company not found.', 404);
        }

        $licenseModel = new License($db->pdo());
        $licenses = $licenseModel->listByCompany($companyId);

        return view('settings.license-history', [
            'company' => $company,
            'licenses' => $licenses,
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

    public function deleteTenant(Request $request, RequestContext $context, Database $db)
    {
        if (!$this->isLicenseIssuer($context)) {
            return response('Not authorized.', 403);
        }

        $validated = $request->validate([
            'company_id' => 'required|integer|min:1',
        ]);

        $companyId = (int) $validated['company_id'];
        $issuerCompanyId = (int) ($context->company()['company_id'] ?? 0);
        if ($companyId === $issuerCompanyId) {
            return back()->withErrors(['company_id' => 'Cannot delete the issuer tenant.'])->withInput();
        }

        $companyModel = new Company($db->pdo());
        $targetCompany = $companyModel->findById($companyId);
        if (!$targetCompany) {
            return back()->withErrors(['company_id' => 'Selected company was not found.'])->withInput();
        }

        if ($targetCompany['status'] === 'deleted') {
            $request->session()->flash('success', 'Tenant is already deleted.');
            return back();
        }

        if (!$companyModel->updateStatus($companyId, 'deleted')) {
            return back()->withErrors(['company_id' => 'Unable to delete tenant.'])->withInput();
        }

        return redirect('/settings/license')->with('success', 'Tenant deleted successfully.');
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
