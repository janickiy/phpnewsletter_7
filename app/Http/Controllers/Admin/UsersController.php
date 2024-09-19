<?php

namespace App\Http\Controllers\Admin;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use App\Http\Requests\Admin\Users\StoreRequest;
use App\Http\Requests\Admin\Users\UpdateRequest;
use Hash;

class UsersController extends Controller
{
    /**
     * @return View
     */
    public function index(): View
    {
        $infoAlert = trans('frontend.hint.users_index') ?? null;

        return view('admin.users.index', compact('infoAlert'))->with('title', trans('frontend.title.users_index'));
    }

    /**
     * @return View
     */
    public function create(): View
    {
        $options = [
            'admin' => trans('frontend.str.admin'),
            'moderator' => trans('frontend.str.moderator'),
            'editor' => trans('frontend.str.editor'),
        ];

        $infoAlert = trans('frontend.hint.users_create') ?? null;

        return view('admin.users.create_edit', compact('options', 'infoAlert'))->with('title', trans('frontend.title.users_create'));
    }

    /**
     * @param StoreRequest $request
     * @return RedirectResponse
     */
    public function store(StoreRequest $request): RedirectResponse
    {
        User::create(array_merge($request->all(), ['password' => Hash::make($request->password)]));

        return redirect()->route('admin.users.index')->with('success', trans('message.information_successfully_added'));
    }

    /**
     * @param int $id
     * @return View
     */
    public function edit(int $id): View
    {
        $row = User::find($id);

        if (!$row) abort(404);

        $options = [
            'admin' => trans('frontend.str.admin'),
            'moderator' => trans('frontend.str.moderator'),
            'editor' => trans('frontend.str.editor'),
        ];

        $infoAlert = trans('frontend.hint.users_edit') ?? null;

        return view('admin.users.create_edit', compact('row', 'options', 'infoAlert'))->with('title', trans('frontend.title.users_edit'));
    }

    /**
     * @param UpdateRequest $request
     * @return RedirectResponse
     */
    public function update(UpdateRequest $request): RedirectResponse
    {
        $user = User::find($request->id);

        if (!$user) abort(404);

        $user->login = $request->input('login');
        $user->name = $request->input('name');

        if (!empty($request->role)) $user->role = $request->input('role');

        if (!empty($request->password)) {
            $user->password = Hash::make($request->password);
        }

        $user->save();

        return redirect()->route('admin.users.index')->with('success', trans('message.data_updated'));
    }

    /**
     * @param Request $request
     * @return void
     */
    public function destroy(Request $request): void
    {
        if ($request->id != Auth::id()) User::find($request->id)->delete();
    }
}
