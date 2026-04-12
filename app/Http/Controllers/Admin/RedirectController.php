<?php

namespace App\Http\Controllers\Admin;


use App\Services\DownloadService;
use App\Models\Redirect;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;


class RedirectController  extends Controller
{
    public function __construct(private DownloadService $downloadService)
    {
        parent::__construct();
    }

    /**
     * @return View
     */
    public function index(): View
    {
        return view('admin.redirect.index', [
            'infoAlert' => __('frontend.hint.redirect_index'),
            'title' => __('frontend.title.redirect_index'),
        ]);
    }

    /**
     * @return JsonResponse
     */
    public function clear(): JsonResponse
    {
        try {
            Redirect::truncate();

            return response()->json([
                'success' => true,
                'message' => __('message.statistics_cleared'),
            ]);
        } catch (\Throwable $e) {
            report($e);

            return response()->json([
                'success' => false,
                'message' => __('frontend.str.delete_error'),
            ], 500);
        }
    }

    /**
     * @param string $url
     * @return StreamedResponse
     */
    public function download(string $url): StreamedResponse
    {
        return $this->downloadService->redirect($url);
    }

    /**
     * @param string $url
     * @return View
     */
    public function info(string $url): View
    {
        return view('admin.redirect.info', [
            'url' => $url,
            'infoAlert' => __('frontend.hint.redirectlog_info'),
            'title' => __('frontend.title.redirect_info'),
        ]);
    }
}
