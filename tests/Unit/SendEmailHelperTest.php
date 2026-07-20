<?php

namespace Tests\Unit;

use App\Helpers\SendEmailHelper;
use App\Services\SmtpConfigurationResolver;
use App\Services\UrlHostResolver;
use PHPUnit\Framework\TestCase;

class SendEmailHelperTest extends TestCase
{
    public function test_helper_has_safe_defaults_for_optional_send_context(): void
    {
        $helper = new SendEmailHelper(
            new SmtpConfigurationResolver,
            new UrlHostResolver,
        );

        $this->assertSame(0, $helper->prior);
    }
}
