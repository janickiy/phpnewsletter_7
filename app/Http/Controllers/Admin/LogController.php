<?php

namespace App\Http\Controllers\Admin;

use App\Models\ReadySent;
use App\Models\Logs;
use App\Services\DownloadService;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

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
            'infoAlert' => __('frontend.hint.log_info'),
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
            'infoAlert' => __('frontend.hint.log_info'),
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
     *
     * @return JsonResponse
     */
    public function clear(): JsonResponse
    {
        try {
            DB::transaction(function (): void {
                ReadySent::query()->delete();
                Logs::query()->delete();
            });

            return response()->json([
                'success' => true,
                'message' => __('frontend.msg.data_successfully_deleted'),
            ]);
        } catch (\Throwable $e) {
            report($e);

            return response()->json([
                'success' => false,
                'message' => __('frontend.str.delete_error'),
            ], 500);
        }
    }
}
