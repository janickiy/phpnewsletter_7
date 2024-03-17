<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use URL;

class AuthController extends Controller
{
    public function __construct()
    {
        $this->middleware('guest:web', ['except' => ['logout']]);
    }

    /**
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
     */
    public function showLoginForm()
    {
        return view('admin.login')->with('title', 'Авторизация');
    }

    /**
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     * @throws \Illuminate\Validation\ValidationException
     */
    public function login(Request $request)
    {
        // Validate the form data
        $this->validate($request, [
            'login'   => 'required',
            'password' => 'required|min:6'
        ]);

        // Attempt to log the user in
        if (\Auth::guard('web')->attempt(['login' => $request->login, 'password' => $request->password], $request->remember)) {
            // if successful, then redirect to their intended location
            return redirect()->intended(route('admin.templates.index'));
        }
        // if unsuccessful, then redirect back to the login with the form data
        return redirect(URL::route('login'))->with('error', "Неверный логин или пароль!");
    }

    /**
     * @param $request
     * @param $user
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     */
    protected function authenticated($request, $user)
    {
        $redirect = redirect(URL::route('admin.templates.index'));

        return $redirect;
    }

    /**
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     */
    public function logout()
    {
        \Auth::guard('web')->logout();

        return redirect(URL::route('login'));
    }
}
