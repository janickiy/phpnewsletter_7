<?php

namespace App\Http\Controllers\Admin;

use App\Helpers\StringHelper;
use App\Models\{Templates, Attach};
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use App\Http\Requests\Admin\Templates\StoreRequest;
use App\Http\Requests\Admin\Templates\UpdateRequest;
use Storage;
use URL;

class TemplatesController extends Controller
{
    /**
     * @return View
     */
    public function index(): View
    {
        return view('admin.templates.index')->with('title', trans('frontend.title.template_index'));
    }

    /**
     * @return View
     */
    public function create(): View
    {
        $infoAlert = trans('frontend.hint.template_create') ? trans('frontend.hint.template_create') : null;

        return view('admin.templates.create_edit', compact('infoAlert'))->with('title', trans('frontend.title.template_create'));
    }

    /**
     * @param StoreRequest $request
     * @return RedirectResponse
     */
    public function store(StoreRequest $request): RedirectResponse
    {
        $id = Templates::create($request->all())->id;

        $attachFile = $request->file('attachfile');

        if (isset($attachFile)) {
            foreach ($attachFile as $file) {
                $filename = StringHelper::randomText(10) . '.' . $file->getClientOriginalExtension();

                if (Storage::putFileAs(Attach::DIRECTORY, $file, $filename)) {
                    $attach = [
                        'name' => $file->getClientOriginalName(),
                        'file_name' => $filename,
                        'templateId' => $id,
                    ];

                    Attach::create($attach);
                }
            }
        }

        return redirect(URL::route('admin.templates.index'))->with('success', trans('message.information_successfully_added'));
    }

    /**
     * @param int $id
     * @return View
     */
    public function edit(int $id): View
    {
        $template = Templates::find($id);

        if (!$template) abort(404);

        $attachment = $template->attach;

        $infoAlert = trans('frontend.hint.template_edit') ? trans('frontend.hint.template_edit') : null;

        return view('admin.templates.create_edit', compact('template', 'attachment', 'infoAlert'))->with('title', trans('frontend.title.template_edit'));
    }

    /**
     * @param UpdateRequest $request
     * @return RedirectResponse
     */
    public function update(UpdateRequest $request): RedirectResponse
    {
        $templates = Templates::find($request->id);

        if (!$templates) abort(404);

        $attachFile = $request->file('attachfile');

        if (isset($attachFile)) {
            foreach ($attachFile as $file) {
                $filename = StringHelper::randomText(10) . '.' . $file->getClientOriginalExtension();

                if (Storage::putFileAs(Attach::DIRECTORY, $file, $filename)) {
                    $attach = [
                        'name' => $file->getClientOriginalName(),
                        'file_name' => $filename,
                        'template_id' => $request->id,
                    ];

                    Attach::create($attach);
                }
            }
        }

        $templates->name = $request->input('name');
        $templates->body = $request->input('body');
        $templates->prior = $request->input('prior');

        $templates->save();

        return redirect(URL::route('admin.templates.index'))->with('success', trans('message.data_updated'));

    }

    /**
     * @param Request $request
     * @return void
     */
    public function destroy(Request $request): void
    {
        Templates::find($request->id)->remove();
    }

    /**
     * @param Request $request
     * @return RedirectResponse
     */
    public function status(Request $request): RedirectResponse
    {
        $templateId = [];

        foreach ($request->templateId as $id) {
            if (is_numeric($id)) {
                $templateId[] = $id;
            }
        }

        switch ($request->action) {
            case  1 :

                $templates = Templates::whereIN('id', $templateId)->get();

                foreach ($templates as $template) {
                    $template->remove();
                }

                break;
        }

        return redirect(URL::route('admin.templates.index'))->with('success', trans('message.actions_completed'));
    }
}
