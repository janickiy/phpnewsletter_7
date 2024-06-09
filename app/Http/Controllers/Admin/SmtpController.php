<?php

namespace App\Http\Controllers\Admin;

use App\Helpers\SendEmailHelper;
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
        $infoAlert = trans('frontend.hint.smtp_index') ? trans('frontend.hint.smtp_index') : null;

        return view('admin.smtp.index', compact('infoAlert'))->with('title', trans('frontend.title.smtp_index'));
    }

    /**
     * @return View
     */
    public function create(): View
    {
        $infoAlert = trans('frontend.hint.smtp_create') ? trans('frontend.hint.smtp_create') : null;

        return view('admin.smtp.create_edit', compact('infoAlert'))->with('title', trans('frontend.title.smtp_create'));
    }

    /**
     * @param StoreRequest $request
     * @return RedirectResponse
     * @throws \PHPMailer\PHPMailer\Exception
     */
    public function store(StoreRequest $request): RedirectResponse
    {
        Smtp::create($request->all());

        return redirect()->route('admin.smtp.index')->with('success', trans('message.information_successfully_added'));
    }

    /**
     * @param int $id
     * @return View
     */
    public function edit(int $id): View
    {
        $row = Smtp::find($id);

        if (!$row) abort(404);

        $infoAlert = trans('frontend.hint.smtp_edit') ? trans('frontend.hint.smtp_edit') : null;

        return view('admin.smtp.create_edit', compact('row', 'infoAlert'))->with('title', trans('frontend.title.smtp_edit'));
    }

    /**
     * @param EditRequest $request
     * @return RedirectResponse
     * @throws \PHPMailer\PHPMailer\Exception
     */
    public function update(EditRequest $request): RedirectResponse
    {
        $smtp = Smtp::find($request->id);

        if (!$smtp) abort(404);

        $smtp->host = $request->input('host');
        $smtp->email = $request->input('email');
        $smtp->username = $request->input('username');
        $smtp->password = $request->input('password');
        $smtp->port = $request->input('port');
        $smtp->authentication = $request->input('authentication');
        $smtp->secure = $request->input('secure');
        $smtp->timeout = $request->input('timeout');
        $smtp->save();

        return redirect()->route('admin.category.index')->with('success', trans('message.data_updated'));
    }

    /**
     * @param Request $request
     * @return void
     */
    public function destroy(Request $request): void
    {
        Smtp::find($request->id)->delete();
    }
}
