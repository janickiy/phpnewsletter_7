<?php

namespace App\Http\Controllers\Admin;


use App\Http\Requests\Admin\Category\DeleteRequest;
use App\Http\Requests\Admin\Category\EditRequest;
use App\Http\Requests\Admin\Category\StoreRequest;
use App\Repositories\CategoryRepository;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class CategoryController extends Controller
{
    public function __construct(private readonly CategoryRepository $categoryRepository)
    {
        parent::__construct();
    }

    /**
     * @return View
     */
    public function index(): View
    {
        return view('admin.category.index', [
            'infoAlert' => __('frontend.hint.category_index'),
            'title' => __('frontend.title.category_index'),
        ]);
    }

    /**
     * @return View
     */
    public function create(): View
    {
        return view('admin.category.create_edit', [
            'infoAlert' => __('frontend.hint.category_create'),
            'title' => __('frontend.title.category_create'),
        ]);
    }

    /**
     * @param StoreRequest $request
     * @return RedirectResponse
     */
    public function store(StoreRequest $request): RedirectResponse
    {
        try {
            $this->categoryRepository->create($request->validated());
        } catch (\Throwable $e) {
            report($e);

            return back()
                ->with('error', $e->getMessage())
                ->withInput();
        }

        return to_route('admin.category.index')
            ->with('success', __('message.information_successfully_added'));
    }

    /**
     * @param int $id
     * @return View
     */
    public function edit(int $id): View
    {
        $row = $this->categoryRepository->find($id);

        abort_if(!$row, 404);

        return view('admin.category.create_edit', [
            'row' => $row,
            'infoAlert' => __('frontend.hint.category_create'),
            'title' => __('frontend.title.category_edit'),
        ]);
    }

    /**
     * @param EditRequest $request
     * @return RedirectResponse
     */
    public function update(EditRequest $request): RedirectResponse
    {
        try {
            $this->categoryRepository->updateWithMapping(
                (int) $request->id,
                $request->safe()->except(['id'])
            );
        } catch (\Throwable $e) {
            report($e);

            return back()
                ->with('error', $e->getMessage())
                ->withInput();
        }

        return to_route('admin.category.index')
            ->with('success', __('message.data_updated'));
    }

    /**
     * @param DeleteRequest $request
     * @return RedirectResponse
     */
    public function destroy(DeleteRequest $request): RedirectResponse
    {
        try {
            $this->categoryRepository->delete((int) $request->id);
        } catch (\Throwable $e) {
            report($e);

            return back()
                ->with('error', $e->getMessage());
        }

        return to_route('admin.category.index')
            ->with('success', __('message.data_deleted'));
    }
}
