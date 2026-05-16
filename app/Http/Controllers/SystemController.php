<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\License;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SystemController extends Controller
{
    public function home()
    {
        return redirect('/dashboard');
    }

    public function licenseAlias()
    {
        return redirect('/license-required');
    }

    public function licenseRequired(Request $request)
    {
        $host = $request->getHost();
        $baseDomain = trim((string) config('app.base_domain', 'skyare.space'));
        $subdomain = explode('.', $host)[0] ?? $host;

        $companyModel = new Company(DB::connection()->getPdo());
        $company = $companyModel->findBySubdomain($subdomain);

        if ($company === null) {
            $company = $companyModel->findBySubdomain('www')
                ?? $companyModel->findBySubdomain('default')
                ?? $companyModel->findAny();
        }

        if ($host === $baseDomain) {
            return view('system.license_required', [
                'message' => 'License verification failed.',
                'company' => null,
                'host' => $host,
                'grace_until' => null,
            ]);
        }

        $message = 'License verification failed.';
        $graceUntil = null;

        if ($company === null) {
            $message = 'Company not found for this request.';
            return view('system.license_required', [
                'message' => $message,
                'company' => null,
                'host' => $host,
                'grace_until' => null,
            ]);
        }

        $licenseModel = new License(DB::connection()->getPdo());
        $license = $licenseModel->findActiveByCompanyAndDomain(
            (int) ($company['company_id'] ?? 0),
            (string) ($company['company_name'] ?? ''),
            $host
        );

        if ($license === null) {
            $message = 'No active license found for this domain.';
            return view('system.license_required', [
                'message' => $message,
                'company' => $company,
                'host' => $host,
                'grace_until' => null,
            ]);
        }

        $expiryDate = trim((string) ($license['expiry_date'] ?? $license['valid_until'] ?? ''));
        if ($expiryDate !== '' && $expiryDate < date('Y-m-d')) {
            $message = 'License has expired.';
            return view('system.license_required', [
                'message' => $message,
                'company' => $company,
                'host' => $host,
                'grace_until' => null,
            ]);
        }

        $message = 'License is active and valid.';

        return view('system.license_required', [
            'message' => $message,
            'company' => $company,
            'host' => $host,
            'grace_until' => $graceUntil,
        ]);
    }

    private function buildTenantLoginUrl(string $subdomain, Request $request): string
    {
        $baseDomain = trim((string) config('app.base_domain', 'skyare.space'));
        $scheme = $request->getScheme();
        $host = strtolower((string) $request->getHost());

        if ($host === $baseDomain || str_ends_with($host, '.' . $baseDomain)) {
            return sprintf('%s://%s.%s/login', $scheme, $subdomain, $baseDomain);
        }

        return sprintf('%s://%s.%s/login', $scheme, $subdomain, $baseDomain);
    }

    public function notFound()
    {
        return response(
            '<h1>404 Not Found</h1><p>Unknown module or page. Go back to <a href="/dashboard">Dashboard</a>.</p>',
            404
        );
    }

    public function registerCompany(Request $request)
    {
        $request->validate([
            'company_name'   => 'required|string|max:100',
            'admin_email'    => 'required|email|max:100',
            'subdomain'      => 'required|alpha_num|min:3|max:32',
            'admin_password' => 'nullable|string|min:8|max:128',
        ]);

        $companyName   = trim($request->input('company_name'));
        $adminEmail    = strtolower(trim($request->input('admin_email')));
        $subdomain     = strtolower(trim($request->input('subdomain')));
        $adminPassword = $request->input('admin_password');
        $passwordProvided = $adminPassword !== null && strlen($adminPassword) >= 8;
        $plainPassword = $passwordProvided ? $adminPassword : \Illuminate\Support\Str::random(12);

        $pdo = DB::connection()->getPdo();
        $companyModel = new \App\Models\Company($pdo);
        $licenseModel = new \App\Models\License($pdo);

        // Check email uniqueness across tenants
        $existingEmail = DB::table('users')->where('email', $adminEmail)->first();
        if ($existingEmail) {
            return back()->withErrors(['admin_email' => 'An account with that email already exists.'])->withInput();
        }

        // Check subdomain uniqueness
        if ($companyModel->findBySubdomain($subdomain)) {
            return back()->withErrors(['subdomain' => 'That subdomain is already taken.'])->withInput();
        }

        $hashed = bcrypt($plainPassword);

        // Create company
        $companyId = $companyModel->create($companyName, $subdomain);

        // Create admin user with all required columns
        DB::table('users')->insert([
            'company_id'    => $companyId,
            'name'          => $companyName . ' Admin',
            'full_name'     => $companyName . ' Admin',
            'email'         => $adminEmail,
            'password'      => $hashed,
            'password_hash' => $hashed,
            'role_key'      => 'admin',
            'is_active'     => 1,
            'created_at'    => now(),
            'updated_at'    => now(),
        ]);

        // Create trial license
        $baseDomain = trim((string) config('app.base_domain', 'skyare.space'), '.');
        $domain = $subdomain . '.' . $baseDomain;
        $licenseModel->createTrialForCompany($companyId, $domain, 7);

        // Send welcome/credentials email if password was auto-generated
        if (!$passwordProvided) {
            try {
                $loginUrl = 'https://' . $domain . '/login';
                $body = '<h2>Welcome to Skyare Accounting!</h2>'
                    . '<p>Your company <strong>' . htmlspecialchars($companyName, ENT_QUOTES) . '</strong> has been registered.</p>'
                    . '<p><strong>Login URL:</strong> <a href="' . $loginUrl . '">' . $loginUrl . '</a></p>'
                    . '<p><strong>Email:</strong> ' . htmlspecialchars($adminEmail, ENT_QUOTES) . '</p>'
                    . '<p><strong>Temporary Password:</strong> ' . htmlspecialchars($plainPassword, ENT_QUOTES) . '</p>'
                    . '<p>Please log in and change your password immediately.</p>';
                \App\Core\Mailer::send($adminEmail, 'Welcome to Skyare — Your Login Details', $body);
            } catch (\Throwable $e) {
                error_log('[registerCompany_email_failed] ' . $e->getMessage());
            }
        }

        return redirect('https://' . $domain . '/login')->with('success', 'Company registered! ' . ($passwordProvided ? 'You can now log in.' : 'Your login credentials have been emailed to you.'));
    }
}
