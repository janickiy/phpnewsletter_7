<?php

namespace Tests\Feature;

use App\Helpers\SendEmailHelper;
use App\Helpers\SettingsHelper;
use App\Http\Requests\Admin\Smtp\EditRequest;
use App\Http\Requests\Admin\Smtp\StoreRequest;
use App\Models\Settings;
use App\Models\Smtp;
use App\Services\SmtpConfigurationResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class SmtpConfigurationTest extends TestCase
{
    use RefreshDatabase;

    public function test_smtp_password_is_hidden_from_serialization_and_edit_form(): void
    {
        $smtp = new Smtp([
            'host' => 'smtp.example.com',
            'email' => 'mailer@example.com',
            'username' => 'mailer',
            'password' => 'super-secret-password',
            'port' => 587,
            'authentication' => Smtp::AUTH_CRAM_MD5,
            'secure' => Smtp::SECURE_TLS,
            'timeout' => 5,
            'active' => 1,
        ]);
        $smtp->id = 10;

        $formView = file_get_contents(resource_path('views/admin/smtp/create_edit.blade.php'));
        $indexView = file_get_contents(resource_path('views/admin/smtp/index.blade.php'));

        $this->assertArrayNotHasKey('password', $smtp->toArray());
        $this->assertTrue($smtp->active);
        $this->assertIsString($formView);
        $this->assertIsString($indexView);
        $this->assertStringNotContainsString('$row->password', $formView);
        $this->assertStringContainsString("Form::password('password'", $formView);
        $this->assertStringContainsString("Form::radio('authentication', 'cram-md5'", $formView);
        $this->assertStringContainsString("if (!data['activeStatus'])", $indexView);
    }

    public function test_smtp_requests_share_consistent_authentication_and_password_rules(): void
    {
        $payload = [
            'host' => 'smtp.example.com',
            'email' => 'mailer@example.com',
            'username' => 'mailer',
            'password' => 'secret',
            'port' => 587,
            'authentication' => Smtp::AUTH_CRAM_MD5,
            'secure' => Smtp::SECURE_TLS,
            'timeout' => 5,
        ];

        $validator = Validator::make($payload, (new StoreRequest)->rules());

        $this->assertFalse($validator->fails(), $validator->errors()->first());
        $this->assertContains('required', (new StoreRequest)->rules()['password']);
        $this->assertContains('nullable', (new EditRequest)->rules()['password']);

        $payload['authentication'] = 'crammd5';
        $legacyValidator = Validator::make($payload, (new StoreRequest)->rules());

        $this->assertTrue($legacyValidator->fails());
        $this->assertArrayHasKey('authentication', $legacyValidator->errors()->toArray());
    }

    public function test_legacy_cram_md5_value_is_still_used_for_sending(): void
    {
        $smtp = new Smtp(['authentication' => 'crammd5']);

        $this->assertSame('CRAM-MD5', $smtp->phpMailerAuthType());
    }

    public function test_login_and_legacy_no_values_explicitly_use_login(): void
    {
        $this->assertSame(
            'LOGIN',
            (new Smtp(['authentication' => Smtp::AUTH_LOGIN]))->phpMailerAuthType(),
        );
        $this->assertSame(
            'LOGIN',
            (new Smtp(['authentication' => 'no']))->phpMailerAuthType(),
        );
    }

    public function test_sending_resolver_uses_only_active_smtp_servers(): void
    {
        $inactive = $this->smtp(['host' => 'inactive.example.com', 'active' => false]);
        $active = $this->smtp(['host' => 'active.example.com', 'active' => true]);
        $resolver = new SmtpConfigurationResolver;

        $this->assertTrue($active->is($resolver->resolve()));

        $active->update(['active' => false]);

        $this->assertNull($resolver->resolve());
        $this->assertFalse($inactive->active);
    }

    public function test_sending_returns_a_controlled_error_without_active_smtp(): void
    {
        Settings::query()->create(['name' => 'HOW_TO_SEND', 'value' => 'smtp']);
        SettingsHelper::refresh();

        try {
            $helper = new SendEmailHelper(new SmtpConfigurationResolver);
            $helper->subject = 'Subject';
            $helper->body = 'Body';
            $helper->email = 'recipient@example.com';

            $result = $helper->sendEmail();

            $this->assertFalse($result['result']);
            $this->assertSame('No active SMTP server is configured.', $result['error']);
        } finally {
            SettingsHelper::cacheClear();
        }
    }

    private function smtp(array $overrides): Smtp
    {
        return Smtp::query()->create(array_merge([
            'host' => 'smtp.example.com',
            'email' => 'mailer@example.com',
            'username' => 'mailer',
            'password' => 'secret',
            'port' => 587,
            'authentication' => Smtp::AUTH_LOGIN,
            'secure' => Smtp::SECURE_TLS,
            'timeout' => 5,
            'active' => true,
        ], $overrides));
    }
}
