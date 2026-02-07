<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\Admin\Category\StoreRequest;
use App\Http\Requests\Admin\Category\EditRequest;
use App\Models\Macros;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MacrosController extends Controller
{
    /**
     * @return View
     */
    public function index(): View
    {
        $infoAlert = __('frontend.hint.macros_index') ?? null;

        return view('admin.macros.index', compact('infoAlert'))->with('title', __('frontend.title.macros_index'));
    }

    /**
     * @return View
     */
    public function create(): View
    {
        $infoAlert = __('frontend.hint.macros_create') ?? null;
        $options = Macros::getOption();

        return view('admin.macros.create_edit', compact('infoAlert', 'options'))->with('title', __('frontend.title.macros_create'));
    }

    /**
     * @param StoreRequest $request
     * @return RedirectResponse
     */
    public function store(StoreRequest $request): RedirectResponse
    {
        Macros::create($request->all());

        return redirect()->route('admin.macros.index')->with('success', __('message.information_successfully_added'));
    }

    /**
     * @param int $id
     * @return View
     */
    public function edit(int $id): View
    {
        $row = Macros::find($id);

        if (!$row) abort(404);

        $options = Macros::getOption();
        $infoAlert = __('frontend.hint.macros_create') ?? null;

        return view('admin.macros.create_edit', compact('row', 'infoAlert', 'options'))->with('title', __('frontend.title.macros_edit'));
    }

    /**
     * @param EditRequest $request
     * @return RedirectResponse
     */
    public function update(EditRequest $request): RedirectResponse
    {
        $row = Macros::find($request->id);

        if (!$row) abort(404);

        $row->name = $request->input('name');
        $row->value = $request->input('value');
        $row->type = $request->input('type');
        $row->save();

        return redirect()->route('admin.macros.index')->with('success', __('message.data_updated'));
    }

    /**
     * @param Request $request
     * @return void
     */
    public function destroy(Request $request): void
    {
        Macros::find($request->id)->delete();
    }
}
