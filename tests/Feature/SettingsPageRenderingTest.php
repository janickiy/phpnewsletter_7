<?php

namespace Tests\Feature;

use App\Helpers\SettingsHelper;
use App\Models\Settings;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SettingsPageRenderingTest extends TestCase
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

    public function test_admin_can_render_the_settings_form(): void
    {
        $this->withoutExceptionHandling();

        $admin = User::query()->create([
            'name' => 'Admin',
            'login' => 'settings-admin',
            'role' => User::ROLE_ADMIN,
            'password' => 'secret123',
        ]);
        Settings::query()->create(['name' => 'EMAIL', 'value' => 'sender@example.com']);

        $this->actingAs($admin)
            ->get(route('admin.settings.index'))
            ->assertOk()
            ->assertSee('name="EMAIL"', false)
            ->assertSee('sender@example.com');
    }
}
