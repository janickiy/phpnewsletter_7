<?php

namespace Tests\Feature;

use App\Http\Requests\Admin\Settings\UpdateRequest;
use App\Models\Settings;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class SettingsRequestTest extends TestCase
{
    public function test_it_normalizes_unchecked_options_and_accepts_valid_settings(): void
    {
        $request = TestableSettingsUpdateRequest::create('/settings/update', 'PUT', [
            'EMAIL' => 'sender@example.com',
            'INTERVAL_NUMBER' => '1',
            'INTERVAL_TYPE' => 'hour',
            'LIMIT_NUMBER' => '100',
            'SLEEP' => '2',
            'DAYS_FOR_REMOVE_SUBSCRIBER' => '30',
            'PRECEDENCE' => 'bulk',
            'CHARSET' => 'utf-8',
            'CONTENT_TYPE' => 'html',
            'HOW_TO_SEND' => 'smtp',
            'URL' => 'http://localhost:8081',
            'header_name' => ['X-Campaign-ID'],
            'header_value' => ['demo-42'],
        ]);

        $request->normalizeInput();

        $validator = Validator::make($request->all(), $request->rules());

        $this->assertFalse($validator->fails(), $validator->errors()->first());

        foreach (Settings::EDITABLE_KEYS as $key) {
            $this->assertArrayHasKey($key, $request->rules());
        }

        foreach (Settings::BOOLEAN_KEYS as $key) {
            $this->assertFalse($request->boolean($key));
        }
    }

    public function test_it_rejects_invalid_delays_and_header_injection(): void
    {
        $request = TestableSettingsUpdateRequest::create('/settings/update', 'PUT', [
            'INTERVAL_NUMBER' => '1',
            'INTERVAL_TYPE' => 'minute',
            'LIMIT_NUMBER' => '100',
            'SLEEP' => '-1',
            'DAYS_FOR_REMOVE_SUBSCRIBER' => '7',
            'PRECEDENCE' => 'no',
            'CHARSET' => 'utf-8',
            'CONTENT_TYPE' => 'plain',
            'HOW_TO_SEND' => 'php',
            'header_name' => ['X-Safe'],
            'header_value' => ["safe\r\nBcc: victim@example.com"],
        ]);

        $request->normalizeInput();

        $validator = Validator::make($request->all(), $request->rules());

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('SLEEP', $validator->errors()->toArray());
        $this->assertArrayHasKey('header_value.0', $validator->errors()->toArray());
    }
}

class TestableSettingsUpdateRequest extends UpdateRequest
{
    public function normalizeInput(): void
    {
        $this->prepareForValidation();
    }
}
