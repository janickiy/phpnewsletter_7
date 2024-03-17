<?php

namespace App\Helpers;

class PermissionsHelper
{
    /**
     * @param string $permissions
     * @return bool
     */
    public static function has_permission($permissions = '')
    {
        if (\Auth::user()->role == 'admin') return true;

        $permissions = explode('|', $permissions);

        if (in_array(\Auth::user()->role, $permissions)) {
            return true;
        } else {
            return false;
        }
    }
}
