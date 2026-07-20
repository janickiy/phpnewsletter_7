<?php

namespace Tests\Feature;

use App\Models\Macros;
use App\Models\Settings;
use App\Models\Smtp;
use App\Models\Subscribers;
use App\Models\Templates;
use App\Models\User;
use Database\Seeders\LocalDemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class LocalDemoSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeder_is_idempotent_and_preserves_matching_rows(): void
    {
        $this->createRowsThatMustNotBeOverwritten();

        $this->seed(LocalDemoSeeder::class);

        $this->assertDemoDatasetWasCreated();
        $this->assertMatchingRowsWerePreserved();

        $counts = $this->tableCounts();

        $this->seed(LocalDemoSeeder::class);

        $this->assertSame($counts, $this->tableCounts());
        $this->assertMatchingRowsWerePreserved();
    }

    public function test_user_factory_matches_the_project_schema(): void
    {
        $user = User::factory()->admin()->create();

        $this->assertSame(User::ROLE_ADMIN, $user->role);
        $this->assertNotEmpty($user->login);
        $this->assertDatabaseHas('users', ['id' => $user->id, 'login' => $user->login]);
    }

    private function createRowsThatMustNotBeOverwritten(): void
    {
        User::query()->create([
            'name' => 'Existing administrator',
            'login' => 'admin',
            'role' => User::ROLE_EDITOR,
            'password' => 'existing-password',
        ]);

        Settings::query()->create(['name' => 'EMAIL', 'value' => 'existing@example.test']);

        Templates::query()->create([
            'name' => 'Welcome email for new subscribers',
            'body' => '<p>Existing template body</p>',
            'prior' => 2,
        ]);

        Subscribers::query()->create([
            'name' => 'Existing subscriber',
            'email' => 'demo.subscriber001@phpnewsletter.test',
            'active' => 1,
            'token' => str_repeat('x', 32),
        ]);

        Macros::query()->create([
            'name' => '%NAME%',
            'value' => 'Existing macro value',
            'type' => Macros::TYPE_WRAP_PHRASE,
        ]);

        Smtp::query()->create([
            'host' => 'smtp.mailtrap.local',
            'username' => 'mailtrap-demo',
            'email' => 'existing-smtp@example.test',
            'password' => 'existing-password',
            'port' => 1025,
            'authentication' => Smtp::AUTH_PLAIN,
            'secure' => Smtp::SECURE_NONE,
            'timeout' => 10,
            'active' => 0,
        ]);
    }

    private function assertDemoDatasetWasCreated(): void
    {
        $this->assertDatabaseCount('users', 1);
        $this->assertDatabaseCount('charsets', 32);
        $this->assertDatabaseCount('categories', 9);
        $this->assertDatabaseCount('templates', 5);
        $this->assertDatabaseCount('subscribers', 200);
        $this->assertDatabaseCount('subscriptions', 399);
        $this->assertDatabaseCount('macros', 5);
        $this->assertDatabaseCount('smtp', 3);
        $this->assertDatabaseCount('schedule', 5);
        $this->assertDatabaseCount('schedule_category', 10);
        $this->assertDatabaseCount('logs', 3);
        $this->assertDatabaseCount('ready_sent', 135);
        $this->assertDatabaseCount('redirect', 70);
    }

    private function assertMatchingRowsWerePreserved(): void
    {
        $this->assertDatabaseHas('users', [
            'login' => 'admin',
            'name' => 'Existing administrator',
            'role' => User::ROLE_EDITOR,
        ]);
        $this->assertDatabaseHas('settings', [
            'name' => 'EMAIL',
            'value' => 'existing@example.test',
        ]);
        $this->assertDatabaseHas('templates', [
            'name' => 'Welcome email for new subscribers',
            'body' => '<p>Existing template body</p>',
            'prior' => 2,
        ]);
        $this->assertDatabaseHas('subscribers', [
            'email' => 'demo.subscriber001@phpnewsletter.test',
            'name' => 'Existing subscriber',
            'active' => 1,
            'token' => str_repeat('x', 32),
        ]);
        $this->assertDatabaseHas('macros', [
            'name' => '%NAME%',
            'value' => 'Existing macro value',
            'type' => Macros::TYPE_WRAP_PHRASE,
        ]);
        $this->assertDatabaseHas('smtp', [
            'host' => 'smtp.mailtrap.local',
            'username' => 'mailtrap-demo',
            'email' => 'existing-smtp@example.test',
            'port' => 1025,
            'active' => 0,
        ]);
    }

    /**
     * @return array<string, int>
     */
    private function tableCounts(): array
    {
        $tables = [
            'users',
            'charsets',
            'categories',
            'settings',
            'templates',
            'subscribers',
            'subscriptions',
            'macros',
            'smtp',
            'schedule',
            'schedule_category',
            'logs',
            'ready_sent',
            'redirect',
        ];

        return collect($tables)
            ->mapWithKeys(fn (string $table): array => [$table => DB::table($table)->count()])
            ->all();
    }
}
