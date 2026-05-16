<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AuthTest extends TestCase
{
    public function test_user_can_login_and_redirect_to_dashboard(): void
    {
        try {
            $companyId = DB::table('companies')->insertGetId([
                'company_name' => 'AuthTest Company',
                'subdomain' => 'www',
                'status' => 'active',
            ]);

            $userId = DB::table('users')->insertGetId([
                'company_id' => $companyId,
                'full_name' => 'Auth Test User',
                'email' => 'admin@test.com',
                'password_hash' => password_hash('password123', PASSWORD_DEFAULT),
                'role_key' => 'admin',
                'is_active' => 1,
            ]);
        } catch (\Throwable $e) {
            $this->markTestSkipped('Database unavailable for auth test: ' . $e->getMessage());
            return;
        }

        $this->withServerVariables(['HTTP_HOST' => 'www.localhost']);

        $response = $this->post('/login', [
            'email' => 'admin@test.com',
            'password' => 'password123',
            '_token' => \App\Middleware\CsrfMiddleware::token(),
        ]);

        $response->assertStatus(302);
        $response->assertRedirect('/dashboard');

        $this->assertTrue(session()->has('user'));
        $this->assertEquals('admin@test.com', session('user.email'));
    }
}
