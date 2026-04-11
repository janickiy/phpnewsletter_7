<?php

namespace App\Http\Controllers\Admin;


use App\Models\Logs;
use App\Services\DownloadService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class LogController extends Controller
{
    public function __construct(private readonly DownloadService $downloadService)
    {
        parent::__construct();
    }

    /**
     * Display a listing of the logs.
     */
    public function index(): View
    {
        return view('admin.log.index', [
            'title' => __('frontend.title.log_index'),
        ]);
    }

    /**
     * @param int $id
     * @return View
     */
    public function info(int $id): View
    {
        return view('admin.log.info', [
            'id' => $id,
            'infoAlert' => __('frontend.hint.log_info') ?? null,
            'title' => __('frontend.title.log_info'),
        ]);
    }

    /**
     * @param int $id
     * @return Response
     */
    public function download(int $id): Response
    {
        return $this->downloadService->log($id);
    }

    /**
     * Clear all logs
     */
    public function clear(): RedirectResponse
    {
        try {
            Logs::query()->delete();
        } catch (\Throwable $e) {
            report($e);

            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', __('message.data_deleted'));
    }
}
