<?php

namespace Tests\Unit;

use App\Helpers\SendEmailHelper;
use PHPUnit\Framework\TestCase;

class SendEmailHelperTest extends TestCase
{
    public function test_helper_has_safe_defaults_for_optional_send_context(): void
    {
        $helper = new SendEmailHelper();

        $this->assertSame(0, $helper->prior);
    }
}
