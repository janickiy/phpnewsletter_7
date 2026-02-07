<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Auth;

class AuthController extends Controller
{
    public function __construct()
    {
        $this->middleware('guest:web', ['except' => ['logout']]);
    }

    /**
     * @return View
     */
    public function showLoginForm(): View
    {
        return view('admin.login')->with('title', __('frontend.str.auth'));
    }

    /**
     * @param Request $request
     * @return RedirectResponse|void
     */
    public function login(Request $request)
    {
        // Validate the form data
        $this->validate($request, [
            'login'   => 'required',
            'password' => 'required|min:6'
        ]);

        // Attempt to log the user in
        if (Auth::guard('web')->attempt(['login' => $request->login, 'password' => $request->password], $request->remember)) {
            // if successful, then redirect to their intended location
            return redirect()->intended(route('admin.templates.index'));
        }
        // if unsuccessful, then redirect back to the login with the form data
        redirect()->route('login')->with('error', __('auth.failed'));
    }

    /**
     * @param $request
     * @param $user
     * @return RedirectResponse
     */
    protected function authenticated($request, $user): RedirectResponse
    {
        return redirect()->route('admin.templates.index');
    }

    /**
     * @return RedirectResponse
     */
    public function logout(): RedirectResponse
    {
        Auth::guard('web')->logout();

        return redirect()->route('login');
    }
}
