<?php

namespace App\Http\Controllers\Admin;

use App\Helpers\SendEmailHelper;
use App\Http\Requests\Admin\Smtp\EditCategoryRequest;
use App\Http\Requests\Admin\Smtp\StoreCategoryRequest;
use App\Models\Smtp;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use URL;

class SmtpController extends Controller
{
    /**
     * @return View
     */
    public function index(): View
    {
        return view('admin.smtp.index')->with('title', 'Список SMTP');
    }

    /**
     * @return View
     */
    public function create(): View
    {
        return view('admin.smtp.create_edit')->with('title', 'Добавление SMTP');
    }

    /**
     * @param StoreCategoryRequest $request
     * @return RedirectResponse
     * @throws \PHPMailer\PHPMailer\Exception
     */
    public function store(StoreCategoryRequest $request): RedirectResponse
    {
        $validator = $request->validated();

        if (SendEmailHelper::checkConnection($request->host, $request->email, $request->username, $request->password, $request->port, $request->authentication, $request->secure, $request->timeout) === false) {
            $validator->after(function ($validator) {
                $validator->errors()->add('connection', trans('message.unable_connect_to_smtp'));
            });
        }

        if ($validator->fails()) return back()->withErrors($validator)->withInput();

        Smtp::create($request->all());

        return redirect(URL::route('admin.smtp.index'))->with('success', trans('message.information_successfully_added'));
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

        return view('admin.category.create_edit', compact('row', 'infoAlert'))->with('title', 'Редактирование категории');
    }

    /**
     * @param EditCategoryRequest $request
     * @return RedirectResponse
     * @throws \PHPMailer\PHPMailer\Exception
     */
    public function update(EditCategoryRequest $request): RedirectResponse
    {
        $validator = $request->validated();

        if (SendEmailHelper::checkConnection($request->host, $request->email, $request->username, $request->password, $request->port, $request->authentication, $request->secure, $request->timeout) === false) {
            $validator->after(function ($validator) {
                $validator->errors()->add('connection', trans('message.unable_connect_to_smtp'));
            });
        }

        if ($validator->fails()) return back()->withErrors($validator)->withInput();

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

        return redirect(URL::route('admin.category.index'))->with('success', trans('message.data_updated'));
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
