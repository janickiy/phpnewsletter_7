<?php

namespace App\Http\Controllers\Admin;

use App\Repositories\CategoryRepository;
use App\Http\Requests\Admin\Category\{
    StoreRequest,
    EditRequest,
    DeleteRequest
};

use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Exception;

class CategoryController extends Controller
{
    public function __construct(private CategoryRepository $categoryRepository)
    {
        parent::__construct();
    }

    /**
     * @return View
     */
    public function index(): View
    {
        $infoAlert = __('frontend.hint.category_index') ?? null;

        return view('admin.category.index', compact('infoAlert'))->with('title', __('frontend.title.category_index'));
    }

    /**
     * @return View
     */
    public function create(): View
    {
        $infoAlert = __('frontend.hint.category_create') ?? null;

        return view('admin.category.create_edit', compact('infoAlert'))->with('title', __('frontend.title.category_create'));
    }

    /**
     * @param StoreRequest $request
     * @return RedirectResponse
     */
    public function store(StoreRequest $request): RedirectResponse
    {
        try {
            $this->categoryRepository->create($request->all());
        } catch (Exception $e) {
            report($e);

            return redirect()
                ->back()
                ->with('error', $e->getMessage())
                ->withInput();
        }

        return redirect()->route('admin.category.index')->with('success', __('message.information_successfully_added'));
    }

    /**
     * @param int $id
     * @return View
     */
    public function edit(int $id): View
    {
        $row = $this->categoryRepository->find($id);

        if (!$row) abort(404);

        $infoAlert = __('frontend.hint.category_create') ?? null;

        return view('admin.category.create_edit', compact('row', 'infoAlert'))->with('title', __('frontend.title.category_edit'));
    }

    /**
     * @param EditRequest $request
     * @return RedirectResponse
     */
    public function update(EditRequest $request): RedirectResponse
    {
        try {
            $this->categoryRepository->update($request->id, $request->all());
        } catch (Exception $e) {
            report($e);

            return redirect()
                ->back()
                ->with('error', $e->getMessage())
                ->withInput();
        }

        return redirect()->route('admin.category.index')->with('success', __('message.data_updated'));
    }

    /**
     * @param DeleteRequest $request
     * @return void
     */
    public function destroy(DeleteRequest $request): void
    {
        $this->categoryRepository->delete($request->id);
    }
}
