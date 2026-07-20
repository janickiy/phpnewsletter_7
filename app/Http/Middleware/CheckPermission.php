<?php

namespace App\Http\Middleware;

use App\Helpers\PermissionsHelper;
use Closure;

class CheckPermission
{
    /**
     * @return mixed|void
     */
    public function handle($request, Closure $next, $permissions)
    {
        if (! PermissionsHelper::hasPermission($permissions)) {
            abort(403);
        }

        return $next($request);
    }
}
