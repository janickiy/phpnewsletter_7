<?php

namespace App\Http\Controllers\Admin;


use App\Services\DownloadService;
use App\Models\{
    ReadySent,
    Logs
};
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Illuminate\View\View;

class LogController extends Controller
{
    public function __construct(private DownloadService $logService)
    {
        parent::__construct();
    }

    /**
     * @return View
     */
    public function index(): View
    {
        $infoAlert = __('frontend.hint.log_index') ?? null;

        return view('admin.log.index', compact('infoAlert'))->with('title', __('frontend.title.log_index'));
    }

    /**
     * @return RedirectResponse
     */
    public function clear(): RedirectResponse
    {
        ReadySent::truncate();
        Logs::truncate();

        return redirect()->route('admin.log.index')->with('success', __('message.log_cleared'));
    }

    /**
     * @param int $id
     * @return Response
     */
    public function download(int $id): Response
    {
        return $this->logService->log($id);
    }

    /**
     * @param int $id
     * @return View
     */
    public function info(int $id): View
    {
        $infoAlert = trans('frontend.hint.log_info') ?? null;

        return view('admin.log.info', compact('id', 'infoAlert'))->with('title', trans('frontend.title.log_info'));
    }
}
