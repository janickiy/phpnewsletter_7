<?php

namespace App\Http\Controllers\Admin;

use App\Models\Logs;
use App\Models\ReadySent;
use App\Services\DownloadService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class LogController extends Controller
{
    public function __construct(private readonly DownloadService $logService)
    {
        parent::__construct();
    }

    /**
     * @return View
     */
    public function index(): View
    {
        return view('admin.log.index', [
            'infoAlert' => __('frontend.hint.log_index'),
            'title' => __('frontend.title.log_index'),
        ]);
    }

    public function clear(): RedirectResponse
    {
        try {
            DB::transaction(function () {
                ReadySent::truncate();
                Logs::truncate();
            });
        } catch (\Throwable $e) {
            report($e);

            return back()->with('error', $e->getMessage());
        }

        return to_route('admin.log.index')
            ->with('success', __('message.log_cleared'));
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
        return view('admin.log.info', [
            'id' => $id,
            'infoAlert' => __('frontend.hint.log_info'),
            'title' => __('frontend.title.log_info'),
        ]);
    }
}
