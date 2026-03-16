<?php

namespace App\Http\Controllers\Admin;


use App\Http\Requests\Admin\Smtp\DeleteRequest;
use App\Http\Requests\Admin\Smtp\EditRequest;
use App\Http\Requests\Admin\Smtp\StatusRequest;
use App\Http\Requests\Admin\Smtp\StoreRequest;
use App\Repositories\SmtpRepository;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class SmtpController extends Controller
{
    public function __construct(
        private readonly SmtpRepository $smtpRepository
    ) {
        parent::__construct();
    }

    /**
     * @return View
     */
    public function index(): View
    {
        return view('admin.smtp.index', [
            'infoAlert' => __('frontend.hint.smtp_index'),
            'title' => __('frontend.title.smtp_index'),
        ]);
    }

    /**
     * @return View
     */
    public function create(): View
    {
        return view('admin.smtp.create_edit', [
            'infoAlert' => __('frontend.hint.smtp_create'),
            'title' => __('frontend.title.smtp_create'),
        ]);
    }

    /**
     * @param StoreRequest $request
     * @return RedirectResponse
     */
    public function store(StoreRequest $request): RedirectResponse
    {
        try {
            $this->smtpRepository->createWithMapping(
                $request->validated()
            );
        } catch (\Throwable $e) {
            report($e);

            return back()
                ->with('error', $e->getMessage())
                ->withInput();
        }

        return to_route('admin.smtp.index')
            ->with('success', __('message.information_successfully_added'));
    }

    /**
     * @param int $id
     * @return View
     */
    public function edit(int $id): View
    {
        $row = $this->smtpRepository->find($id);

        abort_if(!$row, 404);

        return view('admin.smtp.create_edit', [
            'row' => $row,
            'infoAlert' => __('frontend.hint.smtp_edit'),
            'title' => __('frontend.title.smtp_edit'),
        ]);
    }

    /**
     * @param EditRequest $request
     * @return RedirectResponse
     */
    public function update(EditRequest $request): RedirectResponse
    {
        try {
            $this->smtpRepository->updateWithMapping(
                (int) $request->id,
                $request->safe()->except(['id'])
            );
        } catch (\Throwable $e) {
            report($e);

            return back()
                ->with('error', $e->getMessage())
                ->withInput();
        }

        return to_route('admin.smtp.index')
            ->with('success', __('message.data_updated'));
    }

    /**
     * @param DeleteRequest $request
     * @return RedirectResponse
     */
    public function destroy(DeleteRequest $request): RedirectResponse
    {
        try {
            $this->smtpRepository->delete((int) $request->id);
        } catch (\Throwable $e) {
            report($e);

            return back()
                ->with('error', $e->getMessage());
        }

        return to_route('admin.smtp.index')
            ->with('success', __('message.data_deleted'));
    }

    /**
     * @param StatusRequest $request
     * @return RedirectResponse
     */
    public function status(StatusRequest $request): RedirectResponse
    {
        try {
            $this->smtpRepository->updateStatus(
                (int) $request->action,
                $request->activate
            );
        } catch (\Throwable $e) {
            report($e);

            return back()
                ->with('error', $e->getMessage());
        }

        return to_route('admin.smtp.index')
            ->with('success', __('message.actions_completed'));
    }
}
