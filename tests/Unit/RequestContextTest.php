<?php

namespace Tests\Unit;

use App\Core\RequestContext;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

class RequestContextTest extends TestCase
{
    public function test_prefers_forwarded_host_when_present(): void
    {
        $reflection = new ReflectionClass(RequestContext::class);
        $method = $reflection->getMethod('resolveIncomingHost');
        $method->setAccessible(true);

        $host = $method->invoke(null, [
            'HTTP_X_FORWARDED_HOST' => 'accounting.skyare.space, proxy.internal',
            'HTTP_HOST' => 'proxy.internal',
        ]);

        $this->assertSame('accounting.skyare.space', $host);
    }

    public function test_resolves_subdomain_relative_to_base_domain(): void
    {
        $context = new RequestContext([
            'base_domain' => 'skyare.space',
            'default_subdomain' => 'www',
        ]);

        $reflection = new ReflectionClass($context);
        $method = $reflection->getMethod('resolveSubdomain');
        $method->setAccessible(true);

        $this->assertSame('accounting', $method->invoke($context, 'accounting.skyare.space'));
        $this->assertSame('www', $method->invoke($context, 'skyare.space'));
    }
}
