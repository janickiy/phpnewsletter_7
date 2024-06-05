<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\Admin\Category\{
    StoreRequest,
    EditRequest
};
use App\Models\{
    Category,
    Subscriptions,
    ScheduleCategory,
};
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;


class CategoryController extends Controller
{
    /**
     * @return View
     */
    public function index(): View
    {
        $infoAlert = trans('frontend.hint.category_index') ? trans('frontend.hint.category_index') : null;

        return view('admin.category.index', compact('infoAlert'))->with('title', 'Категория подписчиков');
    }

    /**
     * @return View
     */
    public function create(): View
    {
        $infoAlert = trans('frontend.hint.category_create') ? trans('frontend.hint.category_create') : null;

        return view('admin.category.create_edit', compact('infoAlert'))->with('title', 'Добавление категории');
    }

    /**
     * @param StoreRequest $request
     * @return RedirectResponse
     */
    public function store(StoreRequest $request): RedirectResponse
    {

        Category::create($request->all());

        return redirect()->route('admin.category.index')->with('success', 'Информация успешно добавлена');
    }

    /**
     * @param int $id
     * @return View
     */
    public function edit(int $id): View
    {
        $row = Category::find($id);

        if (!$row) abort(404);

        $infoAlert = trans('frontend.hint.category_create') ? trans('frontend.hint.category_create') : null;

        return view('admin.category.create_edit', compact('row', 'infoAlert'))->with('title', 'Редактирование категории');
    }

    /**
     * @param EditRequest $request
     * @return RedirectResponse
     */
    public function update(EditRequest $request): RedirectResponse
    {
        $row = Category::find($request->id);

        if (!$row) abort(404);

        $row->name = $request->input('name');
        $row->save();

        return redirect()->route('admin.category.index')->with('success', 'Данные обновлены');
    }

    /**
     * @param Request $request
     * @return void
     */
    public function destroy(Request $request): void
    {
        Subscriptions::where('category_id', $request->id)->delete();
        ScheduleCategory::where('category_id', $request->id)->delete();
        Category::find($request->id)->delete();
    }

}
