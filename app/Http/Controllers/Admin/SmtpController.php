<?php

namespace App\Http\Controllers\Admin;


use App\Repositories\SmtpRepository;
use App\Http\Requests\Admin\Smtp\EditRequest;
use App\Http\Requests\Admin\Smtp\StoreRequest;
use App\Models\Smtp;
use App\Http\Requests\Admin\Smtp\StatusRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Exception;

class SmtpController extends Controller
{
    public function __construct(private SmtpRepository $smtpRepository)
    {
        parent::__construct();
    }

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
     */
    public function store(StoreRequest $request): RedirectResponse
    {
        try {
            $this->smtpRepository->create($request->all());
        } catch (Exception $e) {
            report($e);

            return redirect()
                ->back()
                ->with('error', $e->getMessage())
                ->withInput();
        }

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
     */
    public function update(EditRequest $request): RedirectResponse
    {
        try {
            $this->smtpRepository->updateWithMapping($request->id, $request->all());
        } catch (Exception $e) {
            report($e);

            return redirect()
                ->back()
                ->with('error', $e->getMessage())
                ->withInput();
        }

        return redirect()->route('admin.smtp.index')->with('success', __('message.data_updated'));
    }

    /**
     * @param Request $request
     * @return void
     */
    public function destroy(Request $request): void
    {
        $this->smtpRepository->delete($request->id);
    }

    /**
     * @param StatusRequest $request
     * @return RedirectResponse
     */
    public function status(StatusRequest $request): RedirectResponse
    {
        $this->smtpRepository->updateStatus($request->action, $request->activate);

        return redirect()->route('admin.smtp.index')->with('success', __('message.actions_completed'));
    }
}
