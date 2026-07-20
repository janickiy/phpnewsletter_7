<?php

namespace Tests\Feature;

use App\Enums\AjaxAction;
use App\Http\Middleware\RemoveSubscriber;
use App\Models\User;
use App\Repositories\AttachRepository;
use Mockery;
use RuntimeException;
use Tests\TestCase;

class AjaxActionSecurityTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Subscriber cleanup is unrelated to the AJAX contract and requires seeded settings.
        $this->withoutMiddleware(RemoveSubscriber::class);
    }

    public function test_guest_can_change_public_language(): void
    {
        $response = $this->postJson(route('admin.ajax.action'), [
            'action' => AjaxAction::ChangeLanguage->value,
            'locale' => 'en',
        ]);

        $response
            ->assertOk()
            ->assertExactJson(['result' => true])
            ->assertCookie('lang', 'en');
    }

    public function test_every_non_public_action_rejects_guests(): void
    {
        foreach (AjaxAction::cases() as $action) {
            if ($action->isPublic()) {
                continue;
            }

            $this->postJson(route('admin.ajax.action'), [
                'action' => $action->value,
            ])->assertForbidden();
        }
    }

    public function test_ajax_endpoint_rejects_get_requests(): void
    {
        $this->getJson(route('admin.ajax.action', [
            'action' => AjaxAction::ChangeLanguage->value,
            'locale' => 'en',
        ]))->assertMethodNotAllowed();
    }

    public function test_unknown_action_is_rejected_by_validation(): void
    {
        $this->postJson(route('admin.ajax.action'), [
            'action' => 'unknown_action',
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('action');
    }

    public function test_non_admin_user_cannot_start_an_update(): void
    {
        $this->actingAs($this->user(User::ROLE_MODERATOR));

        $this->postJson(route('admin.ajax.action'), [
            'action' => AjaxAction::StartUpdate->value,
            'p' => 'download_update',
        ])->assertForbidden();
    }

    public function test_authenticated_user_can_execute_regular_admin_action(): void
    {
        $repository = Mockery::mock(AttachRepository::class);
        $repository
            ->shouldReceive('remove')
            ->once()
            ->with(42)
            ->andReturn(true);

        $this->app->instance(AttachRepository::class, $repository);
        $this->actingAs($this->user(User::ROLE_EDITOR));

        $this->postJson(route('admin.ajax.action'), [
            'action' => AjaxAction::RemoveAttach->value,
            'id' => 42,
        ])
            ->assertOk()
            ->assertExactJson(['result' => true]);
    }

    public function test_internal_exception_message_is_not_exposed(): void
    {
        $repository = Mockery::mock(AttachRepository::class);
        $repository
            ->shouldReceive('remove')
            ->once()
            ->andThrow(new RuntimeException('Sensitive filesystem details'));

        $this->app->instance(AttachRepository::class, $repository);
        $this->actingAs($this->user(User::ROLE_EDITOR));

        $response = $this->postJson(route('admin.ajax.action'), [
            'action' => AjaxAction::RemoveAttach->value,
            'id' => 42,
        ]);

        $response
            ->assertInternalServerError()
            ->assertExactJson([
                'result' => false,
                'errors' => __('frontend.str.error_server'),
            ]);

        $this->assertStringNotContainsString(
            'Sensitive filesystem details',
            (string) $response->getContent()
        );
    }

    private function user(string $role): User
    {
        $user = new User([
            'name' => 'AJAX test user',
            'login' => 'ajax-test-'.$role,
            'role' => $role,
            'password' => 'unused',
        ]);
        $user->id = 100;
        $user->exists = true;

        return $user;
    }
}
