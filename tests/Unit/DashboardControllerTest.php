<?php

namespace Tests\Unit;

use App\Controllers\DashboardController;
use App\Core\RequestContext;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

class DashboardControllerTest extends TestCase
{
    public function test_build_company_url_uses_base_domain(): void
    {
        $context = new RequestContext([
            'base_domain' => 'skyare.space',
            'default_subdomain' => 'www',
        ]);

        $_SERVER['HTTPS'] = 'on';

        $reflection = new ReflectionClass(DashboardController::class);
        $method = $reflection->getMethod('buildCompanyUrl');
        $method->setAccessible(true);

        $hostProperty = (new ReflectionClass($context))->getProperty('host');
        $hostProperty->setAccessible(true);
        $hostProperty->setValue($context, 'accounting.skyare.space');

        $url = $method->invoke(null, 'sales', $context);

        $this->assertSame('https://sales.skyare.space/dashboard', $url);
    }

    public function test_build_company_url_supports_localhost(): void
    {
        $context = new RequestContext([
            'base_domain' => 'skyare.space',
            'default_subdomain' => 'www',
        ]);

        unset($_SERVER['HTTPS'], $_SERVER['HTTP_X_FORWARDED_PROTO']);

        $reflection = new ReflectionClass(DashboardController::class);
        $method = $reflection->getMethod('buildCompanyUrl');
        $method->setAccessible(true);

        $hostProperty = (new ReflectionClass($context))->getProperty('host');
        $hostProperty->setAccessible(true);
        $hostProperty->setValue($context, 'localhost');

        $url = $method->invoke(null, 'sales', $context);

        $this->assertSame('http://sales.localhost/dashboard', $url);
    }
}