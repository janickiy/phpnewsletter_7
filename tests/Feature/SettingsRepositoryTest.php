<?php

namespace Tests\Feature;

use App\Helpers\SettingsHelper;
use App\Models\CustomHeaders;
use App\Models\Settings;
use App\Repositories\SettingsRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SettingsRepositoryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        SettingsHelper::cacheClear();
    }

    protected function tearDown(): void
    {
        SettingsHelper::cacheClear();

        parent::tearDown();
    }

    public function test_it_updates_only_known_settings_and_replaces_headers_atomically(): void
    {
        Settings::query()->create(['name' => 'SLEEP', 'value' => '0']);
        Settings::query()->create(['name' => 'ORGANIZATION', 'value' => 'Old name']);
        Settings::query()->create(['name' => 'REQUEST_REPLY', 'value' => '1']);

        CustomHeaders::query()->create([
            'name' => 'X-Old',
            'value' => 'obsolete',
        ]);

        $this->assertSame('0', SettingsHelper::get('SLEEP'));

        app(SettingsRepository::class)->setSettings([
            'SLEEP' => 7,
            'ORGANIZATION' => 'Example Inc.',
            'FROM' => null,
            'SHOW_UNSUBSCRIBE_LINK' => true,
            'REQUEST_REPLY' => false,
            'UNSUPPORTED_SETTING' => 'must-not-be-saved',
            'header_name' => ['X-Campaign', 'x-campaign', 'Invalid header'],
            'header_value' => ['first', 'latest', 'ignored'],
        ]);

        $this->assertDatabaseHas('settings', ['name' => 'SLEEP', 'value' => '7']);
        $this->assertDatabaseHas('settings', ['name' => 'ORGANIZATION', 'value' => 'Example Inc.']);
        $this->assertDatabaseHas('settings', ['name' => 'FROM', 'value' => '']);
        $this->assertDatabaseHas('settings', ['name' => 'SHOW_UNSUBSCRIBE_LINK', 'value' => '1']);
        $this->assertDatabaseHas('settings', ['name' => 'REQUEST_REPLY', 'value' => '0']);
        $this->assertDatabaseMissing('settings', ['name' => 'UNSUPPORTED_SETTING']);

        $this->assertDatabaseCount('customheaders', 1);
        $this->assertDatabaseHas('customheaders', [
            'name' => 'x-campaign',
            'value' => 'latest',
        ]);

        $this->assertSame('7', SettingsHelper::get('SLEEP'));
    }
}
