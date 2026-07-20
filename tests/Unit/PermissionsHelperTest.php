<?php

namespace Tests\Unit;

use App\Helpers\PermissionsHelper;
use App\Models\User;
use Tests\TestCase;

class PermissionsHelperTest extends TestCase
{
    public function test_guest_has_no_permissions(): void
    {
        $this->assertFalse(PermissionsHelper::hasPermission(User::ROLE_EDITOR));
    }

    public function test_admin_has_every_permission(): void
    {
        $this->actingAs($this->user(User::ROLE_ADMIN));

        $this->assertTrue(PermissionsHelper::hasPermission(User::ROLE_EDITOR));
    }

    public function test_role_must_be_in_the_allowed_list(): void
    {
        $this->actingAs($this->user(User::ROLE_MODERATOR));

        $this->assertTrue(PermissionsHelper::hasPermission('admin|moderator'));
        $this->assertFalse(PermissionsHelper::hasPermission(User::ROLE_EDITOR));
    }

    private function user(string $role): User
    {
        $user = new User([
            'name' => 'Permission test user',
            'login' => 'permission-'.$role,
            'role' => $role,
            'password' => 'unused',
        ]);
        $user->id = 100;
        $user->exists = true;

        return $user;
    }
}
