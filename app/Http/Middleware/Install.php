<?php

namespace App\Http\Middleware;

use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Illuminate\Http\Request;
use Closure;

class Install
{
    /**
     * @param Request $request
     * @param Closure $next
     * @return \Illuminate\Http\RedirectResponse|mixed
     */
    public function handle(Request $request, Closure $next)
    {
        if (!file_exists(base_path('.env')) && !$request->is('install*')) {
            \Auth::guard('web')->logout();
            return redirect()->to('install');
        }

        if (file_exists(base_path('.env')) && $request->is('install*') && !$request->is('install/complete')) {
            throw new NotFoundHttpException;
        }

        return $next($request);
    }
}
