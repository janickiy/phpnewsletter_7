<?php

namespace App\Http\Controllers\Admin;

use App\Helpers\UpdateHelper;
use Illuminate\View\View;

class UpdateController extends Controller
{
    public function index(): View
    {
        $update = new UpdateHelper(app()->getLocale(), env('VERSION'));

        $button_update = '';
        $msg_no_update = '';

        if ($update->checkUpgrade() && $update->checkTree()) {
            $button_update = trans('frontend.str.button_update');
            $button_update = str_replace('%NEW_VERSION%', $update->getUpgradeVersion(), $button_update);
            $button_update = str_replace('%SCRIPT_NAME%', trans('frontend.str.script_name'), $button_update);
        } else {
            $msg_no_update = trans('frontend.str.no_updates');
            $msg_no_update = str_replace('%SCRIPT_NAME%', trans('frontend.str.script_name'), $msg_no_update);
            $msg_no_update = str_replace('%NEW_VERSION%', env('VERSION'), $msg_no_update);
        }

        $infoAlert = trans('frontend.hint.update_index') ?? null;

        return view('admin.update.index', compact('button_update', 'msg_no_update', 'infoAlert'))->with('title', trans('frontend.title.update'));
    }
}
