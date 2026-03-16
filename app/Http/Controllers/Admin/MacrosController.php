<?php

namespace App\Http\Controllers\Admin;


use App\Http\Requests\Admin\Macros\DeleteRequest;
use App\Http\Requests\Admin\Macros\EditRequest;
use App\Http\Requests\Admin\Macros\StoreRequest;
use App\Models\Macros;
use App\Repositories\MacrosRepository;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class MacrosController extends Controller
{
    public function __construct(
        private readonly MacrosRepository $macrosRepository
    ) {
        parent::__construct();
    }

    /**
     * @return View
     */
    public function index(): View
    {
        return view('admin.macros.index', [
            'infoAlert' => __('frontend.hint.macros_index'),
            'title' => __('frontend.title.macros_index'),
        ]);
    }

    /**
     * @return View
     */
    public function create(): View
    {
        return view('admin.macros.create_edit', [
            'infoAlert' => __('frontend.hint.macros_create'),
            'options' => Macros::getOption(),
            'title' => __('frontend.title.macros_create'),
        ]);
    }

    /**
     * @param StoreRequest $request
     * @return RedirectResponse
     */
    public function store(StoreRequest $request): RedirectResponse
    {
        try {
            $this->macrosRepository->create($request->validated());
        } catch (\Throwable $e) {
            report($e);

            return back()
                ->with('error', $e->getMessage())
                ->withInput();
        }

        return to_route('admin.macros.index')
            ->with('success', __('message.information_successfully_added'));
    }

    /**
     * @param int $id
     * @return View
     */
    public function edit(int $id): View
    {
        $row = $this->macrosRepository->find($id);

        abort_if(!$row, 404);

        return view('admin.macros.create_edit', [
            'row' => $row,
            'infoAlert' => __('frontend.hint.macros_create'),
            'options' => Macros::getOption(),
            'title' => __('frontend.title.macros_edit'),
        ]);
    }

    /**
     * @param EditRequest $request
     * @return RedirectResponse
     */
    public function update(EditRequest $request): RedirectResponse
    {
        try {
            $this->macrosRepository->updateWithMapping(
                (int) $request->id,
                $request->safe()->except(['id'])
            );
        } catch (\Throwable $e) {
            report($e);

            return back()
                ->with('error', $e->getMessage())
                ->withInput();
        }

        return to_route('admin.macros.index')
            ->with('success', __('message.data_updated'));
    }

    /**
     * @param DeleteRequest $request
     * @return RedirectResponse
     */
    public function destroy(DeleteRequest $request): RedirectResponse
    {
        try {
            $this->macrosRepository->delete((int) $request->id);
        } catch (\Throwable $e) {
            report($e);

            return back()
                ->with('error', $e->getMessage());
        }

        return to_route('admin.macros.index')
            ->with('success', __('message.data_deleted'));
    }
}
