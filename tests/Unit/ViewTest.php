<?php

namespace Tests\Unit;

use App\Core\View;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

class ViewTest extends TestCase
{
    protected function tearDown(): void
    {
        $_SESSION = [];
        parent::tearDown();
    }

    public function test_injects_company_switcher_for_signed_in_users(): void
    {
        $_SESSION['user'] = ['company_id' => 2];
        $_SESSION['available_companies'] = [
            [
                'company_id' => 1,
                'company_name' => 'Alpha Ltd',
                'subdomain' => 'alpha',
                'tenant_url' => 'https://alpha.skyare.space/dashboard',
            ],
            [
                'company_id' => 2,
                'company_name' => 'Beta Ltd',
                'subdomain' => 'beta',
                'tenant_url' => 'https://beta.skyare.space/dashboard',
            ],
        ];

        $reflection = new ReflectionClass(View::class);
        $method = $reflection->getMethod('injectCompanySwitcher');
        $method->setAccessible(true);

        $html = '<html><body><main>Content</main></body></html>';
        $result = $method->invoke(null, $html, ['company' => ['company_id' => 2]]);

        $this->assertStringContainsString('global-company-switcher', $result);
        $this->assertStringContainsString('Beta Ltd (beta)', $result);
        $this->assertStringContainsString('selected', $result);
    }
}