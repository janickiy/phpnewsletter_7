<?php

namespace App\Http\Controllers\Admin;


use App\Services\DownloadService;
use App\Models\Redirect;
use Illuminate\Http\Response;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

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
        $infoAlert = __('frontend.hint.redirect_index') ?? null;

        return view('admin.redirect.index', compact('infoAlert'))->with('title',__('frontend.title.redirect_index'));
    }

    /**
     * @return RedirectResponse
     */
    public function clear(): RedirectResponse
    {
        Redirect::truncate();

        return redirect()->route('admin.redirect.index')->with('success', __('message.statistics_cleared'));
    }

    /**
     * @param string $url
     * @return Response
     */
    public function download(string $url): Response
    {
        return $this->downloadService->redirect($url);
    }

    /**
     * @param string $url
     * @return View
     */
    public function info(string $url): View
    {
        $infoAlert = trans('frontend.hint.redirectlog_info') ?? null;

        return view('admin.redirect.info', compact('url','infoAlert'))->with('title', trans('frontend.title.redirect_info'));
    }
}
