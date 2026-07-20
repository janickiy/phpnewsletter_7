<?php

namespace Tests\Unit;

use App\Services\UrlHostResolver;
use PHPUnit\Framework\TestCase;

class UrlHostResolverTest extends TestCase
{
    public function test_configured_url_has_priority(): void
    {
        $resolver = new UrlHostResolver;

        $this->assertSame(
            'newsletter.example.com',
            $resolver->resolve(
                'https://newsletter.example.com/path',
                'http://localhost:8081',
            ),
        );
    }

    public function test_application_url_is_used_when_setting_is_empty(): void
    {
        $resolver = new UrlHostResolver;

        $this->assertSame(
            'localhost',
            $resolver->resolve('', 'http://localhost:8081'),
        );
    }

    public function test_safe_default_is_used_when_no_url_has_a_host(): void
    {
        $resolver = new UrlHostResolver;

        $this->assertSame('localhost', $resolver->resolve('', 'invalid-url'));
    }
}
