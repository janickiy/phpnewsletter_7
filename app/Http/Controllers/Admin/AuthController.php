<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\Admin\Auth\LoginRequest;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function __construct()
    {
        $this->middleware('guest:web')->except('logout');
    }

    /**
     * @return View
     */
    public function showLoginForm(): View
    {
        return view('admin.login', [
            'title' => __('frontend.str.auth'),
        ]);
    }

    /**
     * @param LoginRequest $request
     * @return RedirectResponse
     */
    public function login(LoginRequest $request): RedirectResponse
    {
        $credentials = $request->safe()->only(['login', 'password']);
        $remember = $request->boolean('remember');

        if (Auth::guard('web')->attempt($credentials, $remember)) {
            $request->session()->regenerate();

            return redirect()->intended(route('admin.templates.index'));
        }

        return back()
            ->withErrors([
                'login' => __('auth.failed'),
            ])
            ->withInput($request->only('login', 'remember'));
    }

    /**
     * @param Request $request
     * @param mixed $user
     * @return RedirectResponse
     */
    protected function authenticated(Request $request, mixed $user): RedirectResponse
    {
        return to_route('admin.templates.index');
    }

    /**
     * @param Request $request
     * @return RedirectResponse
     */
    public function logout(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return to_route('login');
    }
}
