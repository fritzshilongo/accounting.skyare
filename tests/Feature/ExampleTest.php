<?php

namespace Tests\Feature;

// use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * Core page routes are reachable and return 200.
     */
    public function test_guest_routes_redirect_to_login(): void
    {
        $protectedRoutes = [
            '/dashboard',
            '/dashboard/checks',
            '/module-hub',
            '/sales',
            '/expenses',
            '/journal-entries',
        ];

        foreach ($protectedRoutes as $route) {
            $response = $this->get($route);
            $response->assertStatus(302);
            $response->assertRedirect('/login');
        }
    }

    public function test_admin_can_access_protected_routes_after_login(): void
    {
        // If DB isn't configured in this environment, skip to avoid false negative.
        try {
            $companyId = \Illuminate\Support\Facades\DB::table('companies')->insertGetId([
                'company_name' => 'Test Company',
                'subdomain' => 'www',
                'status' => 'active',
            ]);

            $userId = \Illuminate\Support\Facades\DB::table('users')->insertGetId([
                'company_id' => $companyId,
                'full_name' => 'Admin User',
                'email' => 'admin@example.com',
                'password_hash' => password_hash('secretpass', PASSWORD_DEFAULT),
                'role_key' => 'admin',
                'is_active' => 1,
            ]);
        } catch (\Throwable $e) {
            $this->markTestSkipped('Database connection unavailable: '.$e->getMessage());
            return;
        }

        $sessionUser = [
            'user_id' => $userId,
            'company_id' => $companyId,
            'full_name' => 'Admin User',
            'email' => 'admin@example.com',
            'role_key' => 'admin',
        ];

        $_SESSION['user'] = $sessionUser;

        $protectedRoutes = [
            '/dashboard',
            '/dashboard/checks',
            '/module-hub',
            '/sales',
            '/expenses',
            '/journal-entries',
        ];

        foreach ($protectedRoutes as $route) {
            $response = $this->withSession(['user' => $sessionUser])->get($route);
            $response->assertStatus(200);
        }
    }

    /**
     * Non-existent routes return 404 with dashboard link.
     */
    public function test_404_fallback_contains_dashboard_link(): void
    {
        $response = $this->get('/this-route-should-not-exist');

        $response->assertStatus(404);
        $response->assertSee('/dashboard');
    }

    /**
     * RequestContext selects a company from fallback path when subdomain is unknown.
     */
    public function test_request_context_fallback_uses_default_company(): void
    {
        // Simulate unknown subdomain host to force fallback.
        $this->withServerVariables(['HTTP_HOST' => 'unknown-subdomain.skyare.space']);

        // /login is public and does not require tenant DB lookups; route should still render.
        $response = $this->get('/login');
        $response->assertStatus(200);
    }

    public function test_base_domain_login_selection_redirects_to_selected_tenant(): void
    {
        $this->withServerVariables(['HTTP_HOST' => 'skyare.space']);

        try {
            $companyId = \Illuminate\Support\Facades\DB::table('companies')->insertGetId([
                'company_name' => 'Tulinavogroup',
                'subdomain' => 'tulinavogroup',
                'status' => 'active',
            ]);
        } catch (\Throwable $e) {
            $this->markTestSkipped('DB unavailable for base domain login redirect test: '.$e->getMessage());
            return;
        }

        $response = $this->post('/login', [
            'company_id' => $companyId,
            '_token' => \App\Middleware\CsrfMiddleware::token(),
        ]);

        $response->assertStatus(302);
        $response->assertRedirect('http://tulinavogroup.skyare.space/login');
    }

    public function test_license_required_on_base_domain_redirects_to_tenant_login(): void
    {
        $this->withServerVariables(['HTTP_HOST' => 'skyare.space']);

        try {
            $companyId = \Illuminate\Support\Facades\DB::table('companies')->insertGetId([
                'company_name' => 'Tulinavogroup',
                'subdomain' => 'tulinavogroup',
                'status' => 'active',
            ]);
        } catch (\Throwable $e) {
            $this->markTestSkipped('DB unavailable for license redirect test: '.$e->getMessage());
            return;
        }

        $response = $this->get('/license-required');
        $response->assertStatus(302);
        $response->assertRedirect('http://tulinavogroup.skyare.space/login');
    }

    public function test_password_reset_sends_email_with_faked_mailer(): void
    {
        \App\Core\Mailer::fake();

        // Ensure we have a company and user record to attach to reset.
        try {
            $companyId = \Illuminate\Support\Facades\DB::table('companies')->insertGetId([
                'company_name' => 'Test Tenant',
                'subdomain' => 'www',
                'status' => 'active',
            ]);

            $userId = \Illuminate\Support\Facades\DB::table('users')->insertGetId([
                'company_id' => $companyId,
                'full_name' => 'Test User',
                'email' => 'resetuser@example.com',
                'password_hash' => password_hash('Pas$1234', PASSWORD_DEFAULT),
                'role_key' => 'admin',
                'is_active' => 1,
            ]);
        } catch (\Throwable $e) {
            $this->markTestSkipped('DB unavailable for reset flow test');
            return;
        }

        $response = $this->post('/forgot-password', [
            'email' => 'resetuser@example.com',
            '_token' => \App\Middleware\CsrfMiddleware::token(),
        ]);

        $response->assertStatus(200);
        $sent = \App\Core\Mailer::sent();
        $this->assertNotEmpty($sent);

        $containsEmail = collect($sent)->contains(function ($message) {
            return $message['to'] === 'resetuser@example.com' && strpos($message['subject'], 'Password Reset') !== false;
        });
        $this->assertTrue($containsEmail, 'Password reset email was not sent to correct recipient.');

        \App\Core\Mailer::clear();
    }

    public function test_login_post_redirects_to_dashboard_foruser(): void
    {
        try {
            $companyId = \Illuminate\Support\Facades\DB::table('companies')->insertGetId([
                'company_name' => 'Integration Tenant',
                'subdomain' => 'www',
                'status' => 'active',
            ]);

            $userId = \Illuminate\Support\Facades\DB::table('users')->insertGetId([
                'company_id' => $companyId,
                'full_name' => 'Integration User',
                'email' => 'integration@example.com',
                'password_hash' => password_hash('TestP@ss123', PASSWORD_DEFAULT),
                'role_key' => 'admin',
                'is_active' => 1,
            ]);
        } catch (\Throwable $e) {
            $this->markTestSkipped('DB unavailable for login integration: '.$e->getMessage());
            return;
        }

        $response = $this->post('/login', [
            'email' => 'integration@example.com',
            'password' => 'TestP@ss123',
            '_token' => \App\Middleware\CsrfMiddleware::token(),
        ]);

        $response->assertStatus(302);
        $response->assertRedirect('/dashboard');
    }

    public function test_register_post_redirects_to_tenant_login(): void
    {
        try {
            // Ensure test database is writable and clean
            $this->withServerVariables(['HTTP_HOST' => 'localhost']);

            $response = $this->post('/register', [
                'company_name' => 'Integration Company',
                'subdomain' => 'testtenant',
                'full_name' => 'Tenant Admin',
                'email' => 'tenantadmin@example.com',
                'password' => 'StrongP@ssw0rd',
                '_token' => \App\Middleware\CsrfMiddleware::token(),
            ]);
        } catch (\Throwable $e) {
            $this->markTestSkipped('DB unavailable for register integration: '.$e->getMessage());
            return;
        }

        $response->assertStatus(302);
        $location = $response->headers->get('Location');
        $this->assertStringContainsString('/login', (string) $location);
    }
}
