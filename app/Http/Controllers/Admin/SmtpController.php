<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\Admin\Smtp\EditRequest;
use App\Http\Requests\Admin\Smtp\StoreRequest;
use App\Models\Smtp;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SmtpController extends Controller
{
    /**
     * @return View
     */
    public function index(): View
    {
        $infoAlert = __('frontend.hint.smtp_index') ?? null;

        return view('admin.smtp.index', compact('infoAlert'))->with('title', __('frontend.title.smtp_index'));
    }

    /**
     * @return View
     */
    public function create(): View
    {
        $infoAlert = __('frontend.hint.smtp_create') ?? null;

        return view('admin.smtp.create_edit', compact('infoAlert'))->with('title', __('frontend.title.smtp_create'));
    }

    /**
     * @param StoreRequest $request
     * @return RedirectResponse
     * @throws \PHPMailer\PHPMailer\Exception
     */
    public function store(StoreRequest $request): RedirectResponse
    {
        Smtp::create($request->all());

        return redirect()->route('admin.smtp.index')->with('success', __('message.information_successfully_added'));
    }

    /**
     * @param int $id
     * @return View
     */
    public function edit(int $id): View
    {
        $row = Smtp::find($id);

        if (!$row) abort(404);

        $infoAlert = __('frontend.hint.smtp_edit') ?? null;

        return view('admin.smtp.create_edit', compact('row', 'infoAlert'))->with('title', __('frontend.title.smtp_edit'));
    }

    /**
     * @param EditRequest $request
     * @return RedirectResponse
     * @throws \PHPMailer\PHPMailer\Exception
     */
    public function update(EditRequest $request): RedirectResponse
    {
        $row = Smtp::find($request->id);

        if (!$row) abort(404);

        $row->host = $request->input('host');
        $row->email = $request->input('email');
        $row->username = $request->input('username');
        $row->password = $request->input('password');
        $row->port = $request->input('port');
        $row->authentication = $request->input('authentication');
        $row->secure = $request->input('secure');
        $row->timeout = $request->input('timeout');
        $row->save();

        return redirect()->route('admin.smtp.index')->with('success', __('message.data_updated'));
    }

    /**
     * @param Request $request
     * @return void
     */
    public function destroy(Request $request): void
    {
        Smtp::find($request->id)->delete();
    }

    /**
     * @param Request $request
     * @return RedirectResponse
     */
    public function status(Request $request)
    {
        $temp = [];

        foreach ($request->activate ?? [] as $id) {
            if (is_numeric($id)) {
                $temp[] = $id;
            }
        }

        switch ($request->action) {
            case  0 :
            case  1 :
                Smtp::whereIN('id', $temp)->update(['active' => $request->action]);
                break;

            case 2 :
                Smtp::whereIN('id', $temp)->delete();
                break;
        }

        return redirect()->route('admin.smtp.index')->with('success', __('message.actions_completed'));
    }
}
