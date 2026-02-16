<?php

namespace App\Http\Controllers\Admin;

use App\Models\User;
use App\Repositories\UserRepository;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use App\Http\Requests\Admin\Users\StoreRequest;
use App\Http\Requests\Admin\Users\UpdateRequest;

class UsersController extends Controller
{
    public function __construct(private UserRepository $userRepository)
    {
        parent::__construct();
    }

    /**
     * @return View
     */
    public function index(): View
    {
        $infoAlert = __('frontend.hint.users_index') ?? null;

        return view('admin.users.index', compact('infoAlert'))->with('title', __('frontend.title.users_index'));
    }

    /**
     * @return View
     */
    public function create(): View
    {
        $options = User::getOptions();
        $infoAlert = __('frontend.hint.users_create') ?? null;

        return view('admin.users.create_edit', compact('options', 'infoAlert'))->with('title', __('frontend.title.users_create'));
    }

    /**
     * @param StoreRequest $request
     * @return RedirectResponse
     */
    public function store(StoreRequest $request): RedirectResponse
    {
        $this->userRepository->createWithMapping($request->all());

        return redirect()->route('admin.users.index')->with('success', __('message.information_successfully_added'));
    }

    /**
     * @param int $id
     * @return View
     */
    public function edit(int $id): View
    {
        $row = $this->userRepository->find($id);

        if (!$row) abort(404);

        $options = User::getOptions();
        $infoAlert = __('frontend.hint.users_edit') ?? null;

        return view('admin.users.create_edit', compact('row', 'options', 'infoAlert'))->with('title', __('frontend.title.users_edit'));
    }

    /**
     * @param UpdateRequest $request
     * @return RedirectResponse
     */
    public function update(UpdateRequest $request): RedirectResponse
    {
        $this->userRepository->updateWithMapping($request->id, $request->all());

        return redirect()->route('admin.users.index')->with('success', __('message.data_updated'));
    }

    /**
     * @param Request $request
     * @return void
     */
    public function destroy(Request $request): void
    {
        if ($request->id !== Auth::id()) $this->userRepository->delete($request->id);
    }
}
