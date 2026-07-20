<?php

namespace App\Helpers;

use App\Models\User;
use Illuminate\Support\Facades\Auth;

final class PermissionsHelper
{
    public static function hasPermission(string $permissions = ''): bool
    {
        $role = Auth::user()?->role;

        if ($role === null) {
            return false;
        }

        return $role === User::ROLE_ADMIN
            || in_array($role, explode('|', $permissions), true);
    }
}
