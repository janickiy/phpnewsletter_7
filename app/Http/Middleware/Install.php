<?php

namespace App\Http\Middleware;

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
        $installed = file_exists(base_path('.env'));
        $isInstallerPath = $request->is('install') || $request->is('install/*');
        $isInstallerCompletePath = $request->is('install/complete');

        if (!$installed && !$isInstallerPath) {
            \Auth::guard('web')->logout();
            return redirect()->route('install.start');
        }

        if ($installed && $isInstallerPath && !$isInstallerCompletePath) {
            return redirect()->route('admin.dashboard.index');
        }

        return $next($request);
    }
}
