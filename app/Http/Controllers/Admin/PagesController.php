<?php

namespace App\Http\Controllers\Admin;

use App\Helpers\StringHelper;
use Illuminate\View\View;

class PagesController extends Controller
{
    /**
     * @return View
     */
    public function faq(): View
    {
        return view('admin.pages.faq', [
            'title' => 'FAQ',
        ]);
    }

    /**
     * @return View
     */
    public function cronJobList(): View
    {
        $path = base_path() . '/artisan';

        return view('admin.pages.cron_job_list', [
            'cronJob' => [
                [
                    'description' => 'Task Scheduling',
                    'cron' => '/usr/bin/php -q ' . $path . ' schedule:run >/dev/null 2>&1',
                ],
            ],
            'infoAlert' => __('frontend.hint.cron_job_list'),
            'title' => 'Crontab',
        ]);
    }

    /**
     * @return View
     */
    public function phpinfo(): View
    {
        return view('admin.pages.phpinfo', [
            'phpinfo' => StringHelper::phpinfoArray(),
            'infoAlert' => __('frontend.hint.phpinfo'),
            'title' => 'PHP Info',
        ]);
    }

    /**
     * @return View
     * @throws \Throwable
     */
    public function subscriptionForm(): View
    {
        $subform = view('include.subform')->render();
        $subformJs = view('include.subform_js')->render();

        $subform = preg_replace('/<input name="_token" type="hidden"([^>]+)>/si', '', $subform);

        return view('admin.pages.subscription_form', [
            'infoAlert' => __('frontend.hint.phpinfo'),
            'subform' => $subform,
            'subformJs' => $subformJs,
            'title' => __('frontend.title.subscription_form'),
        ]);
    }
}
