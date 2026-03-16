<?php

namespace App\Http\Controllers\Admin;

use App\Helpers\UpdateHelper;
use Illuminate\View\View;

class UpdateController extends Controller
{
    public function index(): View
    {
        $update = new UpdateHelper(app()->getLocale(), env('VERSION'));

        $buttonUpdate = '';
        $msgNoUpdate = '';

        if ($update->checkUpgrade() && $update->checkTree()) {
            $buttonUpdate = str_replace(
                ['%NEW_VERSION%', '%SCRIPT_NAME%'],
                [$update->getUpgradeVersion(), __('frontend.str.script_name')],
                __('frontend.str.button_update')
            );
        } else {
            $msgNoUpdate = str_replace(
                ['%SCRIPT_NAME%', '%NEW_VERSION%'],
                [__('frontend.str.script_name'), env('VERSION')],
                __('frontend.str.no_updates')
            );
        }

        return view('admin.update.index', [
            'button_update' => $buttonUpdate,
            'msg_no_update' => $msgNoUpdate,
            'infoAlert' => __('frontend.hint.update_index'),
            'title' => __('frontend.title.update'),
        ]);
    }
}
