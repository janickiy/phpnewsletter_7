<?php

namespace App\Http\Middleware;

use App\Helpers\PermissionsHelper;
use Closure;

class CheckPermission
{
    protected $helper;

    /**
     * Creates a new instance of the middleware.
     *
     * @param PermissionsHelper $helper
     */
    public function __construct(PermissionsHelper $helper)
    {
        $this->helper = $helper;
    }


    /**
     * @param $request
     * @param Closure $next
     * @param $permissions
     * @return mixed|void
     */
    public function handle($request, Closure $next, $permissions)
    {
        if($this->helper->has_permission($permissions)){
            return $next($request);
        }else{
            abort(403);
        }

    }
}
