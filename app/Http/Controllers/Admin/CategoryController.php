<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\Admin\Category\{
    StoreSettingsRequest,
    EditSettingsRequest
};
use App\Models\Category;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use URL;


class CategoryController extends Controller
{
    /**
     * @return View
     */
    public function index(): View
    {


        return view('admin.category.index')->with('title', 'Категория подписчиков');
    }

    /**
     * @return View
     */
    public function create(): View
    {
        return view('admin.category.create_edit')->with('title', 'Добавление категории');
    }

    /**
     * @param StoreSettingsRequest $request
     * @return RedirectResponse
     */
    public function store(StoreSettingsRequest $request): RedirectResponse
    {
        $validator = $request->validated();

        if ($validator->fails()) return back()->withErrors($validator)->withInput();

        Category::create($request->all());

        return redirect(URL::route('admin.category.index'))->with('success', 'Информация успешно добавлена');
    }

    /**
     * @param int $id
     * @return View
     */
    public function edit(int $id): View
    {
        $row = Category::find($id);

        if (!$row) abort(404);

        return view('admin.category.create_edit', compact('row'))->with('title', 'Редактирование категории');
    }

    /**
     * @param EditSettingsRequest $request
     * @return RedirectResponse
     */
    public function update(EditSettingsRequest $request): RedirectResponse
    {
        $validator = $request->validated();

        if ($validator->fails()) return back()->withErrors($validator)->withInput();

        $row = Category::find($request->id);

        if (!$row) abort(404);

        $row->name = $request->input('name');
        $row->save();

        return redirect(URL::route('admin.category.index'))->with('success', 'Данные обновлены');
    }

    /**
     * @param Request $request
     * @return void
     */
    public function destroy(Request $request): void
    {
        Category::find($request->id)->delete();
    }

}
