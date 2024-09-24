<?php

namespace App\Http\Controllers\Admin;

use Illuminate\View\View;
use App\Helpers\StringHelper;
use Blade;

class PagesController extends Controller
{
    /**
     * @return View
     */
    public function faq(): View
    {
        return view('admin.pages.faq')->with('title', 'FAQ');
    }

    /**
     * @return View
     */
    public function cronJobList(): View
    {
        $infoAlert = trans('frontend.hint.cron_job_list') ?? null;
        $path = base_path() ? base_path() . '/artisan' : ' /home/phpnewsletter/artisan';

        $cronJob[] = ['description' => 'Task Scheduling', 'cron' => '/usr/bin/php -q ' . $path . ' schedule:run >/dev/null 2>&1'];

        return view('admin.pages.cron_job_list', compact('cronJob', 'infoAlert'))->with('title', 'Crontab');
    }

    /**
     * @return View
     */
    public function phpinfo(): View
    {
        $phpinfo = StringHelper::phpinfoArray();
        $infoAlert = trans('frontend.hint.phpinfo') ?? null;

        return view('admin.pages.phpinfo', compact('phpinfo', 'infoAlert'))->with('title', 'PHP Info');
    }

    /**
     * @return View
     */
    public function subscriptionForm(): View
    {
        $infoAlert = trans('frontend.hint.phpinfo') ?? null;

        $subform = view('include.subform');
        $subformJs = view('include.subform_js');

        $subform = preg_replace('/<input name="_token" type="hidden"([^>]+)>/si', '', $subform);

        return view('admin.pages.subscription_form', compact('infoAlert','subform','subformJs'))->with('title', trans('frontend.title.subscription_form'));
    }
}
