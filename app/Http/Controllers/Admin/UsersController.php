<?php

namespace App\Http\Controllers\Admin;


use App\Http\Requests\Admin\Users\StoreRequest;
use App\Http\Requests\Admin\Users\UpdateRequest;
use App\Models\User;
use App\Repositories\UserRepository;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class UsersController extends Controller
{
    public function __construct(
        private readonly UserRepository $userRepository
    ) {
        parent::__construct();
    }

    /**
     * @return View
     */
    public function index(): View
    {
        return view('admin.users.index', [
            'infoAlert' => __('frontend.hint.users_index'),
            'title' => __('frontend.title.users_index'),
        ]);
    }

    /**
     * @return View
     */
    public function create(): View
    {
        return view('admin.users.create_edit', [
            'options' => User::getOptions(),
            'infoAlert' => __('frontend.hint.users_create'),
            'title' => __('frontend.title.users_create'),
        ]);
    }

    /**
     * @param StoreRequest $request
     * @return RedirectResponse
     */
    public function store(StoreRequest $request): RedirectResponse
    {
        try {
            $this->userRepository->createWithMapping(
                $request->safe()->except(['password_again'])
            );
        } catch (\Throwable $e) {
            report($e);

            return back()
                ->with('error', $e->getMessage())
                ->withInput();
        }

        return to_route('admin.users.index')
            ->with('success', __('message.information_successfully_added'));
    }

    public function edit(int $id): View
    {
        $row = $this->userRepository->find($id);

        abort_if(!$row, 404);

        return view('admin.users.create_edit', [
            'row' => $row,
            'options' => User::getOptions(),
            'infoAlert' => __('frontend.hint.users_edit'),
            'title' => __('frontend.title.users_edit'),
        ]);
    }

    /**
     * @param UpdateRequest $request
     * @return RedirectResponse
     */
    public function update(UpdateRequest $request): RedirectResponse
    {
        try {
            $this->userRepository->updateWithMapping(
                (int) $request->id,
                $request->safe()->except(['password_again', 'id'])
            );
        } catch (\Throwable $e) {
            report($e);

            return back()
                ->with('error', $e->getMessage())
                ->withInput();
        }

        return to_route('admin.users.index')
            ->with('success', __('message.data_updated'));
    }

    /**
     * @param Request $request
     * @return RedirectResponse
     */
    public function destroy(Request $request): RedirectResponse
    {
        try {
            if ((int) $request->id !== (int) Auth::id()) {
                $this->userRepository->delete((int) $request->id);
            }
        } catch (\Throwable $e) {
            report($e);

            return back()->with('error', $e->getMessage());
        }

        return to_route('admin.users.index')
            ->with('success', __('message.data_deleted'));
    }
}
