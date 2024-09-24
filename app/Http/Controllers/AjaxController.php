<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\{
    Attach,
    Category,
    Logs,
    Process,
    ReadySent,
    Schedule,
    ScheduleCategory,
    Subscribers,
    Subscriptions,
    Templates
};
use App\Helpers\{SendEmailHelper, SettingsHelper, StringHelper, UpdateHelper};
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\JsonResponse;
use Cookie;
use Config;
use Artisan;
use ZipArchive;
use DateTime;
use Auth;

class AjaxController extends Controller
{
    /**
     * @param Request $request
     * @return JsonResponse
     * @throws \PHPMailer\PHPMailer\Exception
     */
    public function action(Request $request): JsonResponse
    {
        @set_time_limit(0);

        try {
            $update = new UpdateHelper(app()->getLocale(), env('VERSION'));

            if ($request->input('action')) {
                switch ($request->input('action')) {
                    case 'start_update':
                        if ($request->p === 'start') {
                            $download_completed = false;

                            if ($update->getUpdateLink()) {
                                if (Storage::disk('public')->put('update.zip', file_get_contents($update->getUpdateLink() . 'update.zip'))) {
                                    $download_completed = true;
                                }
                            }

                            if ($download_completed === true) {
                                $content['status'] = trans('frontend.msg.download_completed');
                                $content['result'] = true;
                            } else {
                                $content['status'] = trans('frontend.msg.failed_to_update');
                                $content['result'] = false;
                            }
                        }

                        if ($request->p === 'uploap_files_2') {
                            $download_completed = false;

                            if ($update->getUpdateLink()) {
                                if (Storage::disk('public')->put('public.zip', file_get_contents($update->getUpdateLink() . 'public.zip'))) {
                                    $download_completed = true;
                                }
                            }

                            if ($download_completed === true) {
                                $content['status'] = trans('frontend.msg.download_completed');
                                $content['result'] = true;
                            } else {
                                $content['status'] = trans('frontend.msg.failed_to_update');
                                $content['result'] = false;
                            }
                        }

                        if ($request->p === 'uploap_files_3') {
                            $download_completed = false;

                            if ($update->getUpdateLink()) {
                                if (Storage::disk('public')->put('vendor.zip', file_get_contents($update->getUpdateLink() . 'vendor.zip'))) {
                                    $download_completed = true;
                                }
                            }

                            if ($download_completed === true) {
                                $content['status'] = trans('frontend.msg.download_completed');
                                $content['result'] = true;
                            } else {
                                $content['status'] = trans('frontend.msg.failed_to_update');
                                $content['result'] = false;
                            }
                        }

                        if ($request->p === 'update_files') {
                            $zip = new ZipArchive();

                            if (Storage::disk('public')->exists('update.zip') && $zip->open(Storage::disk('public')->path('update.zip')) === true) {
                                if (is_writeable(base_path())) {
                                    $zip->extractTo(base_path());
                                    $content['status'] = trans('frontend.msg.files_unzipped_successfully');
                                    $content['result'] = true;
                                    $zip->close();
                                } else {
                                    $content['status'] = trans('frontend.msg.directory_not_writeable');
                                    $content['result'] = false;
                                }
                            } else {
                                $content['status'] = trans('frontend.msg.cannot_read_zip_archive');
                                $content['result'] = false;
                            }
                        }

                        if ($request->p === 'update_files_2') {
                            $zip = new ZipArchive();

                            if (Storage::disk('public')->exists('public.zip') && $zip->open(Storage::disk('public')->path('public.zip')) === true) {
                                if (is_writeable(base_path())) {
                                    $zip->extractTo(base_path());
                                    $content['status'] = trans('frontend.msg.files_unzipped_successfully');
                                    $content['result'] = true;
                                    $zip->close();
                                } else {
                                    $content['status'] = trans('frontend.msg.directory_not_writeable');
                                    $content['result'] = false;
                                }
                            } else {
                                $content['status'] = trans('frontend.msg.cannot_read_zip_archive');
                                $content['result'] = false;
                            }
                        }

                        if ($request->p === 'update_files_3') {
                            $zip = new ZipArchive();

                            if (Storage::disk('public')->exists('vendor.zip') && $zip->open(Storage::disk('public')->path('vendor.zip')) === true) {
                                if (is_writeable(base_path())) {
                                    $zip->extractTo(base_path());
                                    $content['status'] = trans('frontend.msg.files_unzipped_successfully');
                                    $content['result'] = true;
                                    $zip->close();
                                } else {
                                    $content['status'] = trans('frontend.msg.directory_not_writeable');
                                    $content['result'] = false;
                                }
                            } else {
                                $content['status'] = trans('frontend.msg.cannot_read_zip_archive');
                                $content['result'] = false;
                            }
                        }

                        if ($request->p === 'update_bd') {
                            Artisan::call('migrate', ['--force' => true]);
                            $content['status'] = trans('frontend.msg.update_completed');
                            $content['result'] = true;
                        }

                        if ($request->p === 'clear_cache') {
                            StringHelper::setEnvironmentValue('VERSION', $update->getUpgradeVersion());
                            Artisan::call('cache:clear');
                            Artisan::call('route:cache');
                            Artisan::call('route:clear');
                            Artisan::call('view:clear');

                            $content['status'] = $update->getUpgradeVersion();
                            $content['result'] = true;
                        }

                        return response()->json($content);

                    case 'alert_update':
                        if ($update->checkNewVersion()) {
                            $update_warning = str_replace('%SCRIPTNAME%', trans('frontend.str.script_name'), trans('frontend.str.update_warning'));
                            $update_warning = str_replace('%VERSION%', $update->getVersion(), $update_warning);
                            $update_warning = str_replace('%CREATED%', $update->getCreated(), $update_warning);
                            $update_warning = str_replace('%DOWNLOADLINK%', $update->getDownloadLink(), $update_warning);
                            $update_warning = str_replace('%MESSAGE%', $update->getMessage(), $update_warning);

                            return response()->json(["msg" => $update_warning]);
                        }

                        return response()->json(["msg" => null]);

                    case 'remove_schedule':
                        Schedule::find($request->input('id'))->delete();
                        ScheduleCategory::where('schedule_id', $request->input('id'))->delete();

                        return response()->json([
                            'result' => true,
                            'id' => $request->input('id')
                        ]);

                    case 'change_lng':
                        if ($request->input('locale')) {
                            if (in_array($request->input('locale'), Config::get('app.locales'))) {
                                Cookie::queue(
                                    Cookie::forever('lang', $request->input('locale')));
                            }
                        }

                        return response()->json([
                            'result' => true
                        ]);

                    case 'remove_attach':
                        Attach::find($request->id)->remove();

                        return response()->json([
                            'result' => true
                        ]);

                    case 'send_test_email':
                        $subject = $request->input('name');
                        $body = $request->input('body');
                        $prior = $request->input('prior');
                        $email = $request->input('email');

                        $errors = [];

                        if (empty($subject)) $errors[] = trans('validation.empty_name');
                        if (empty($body)) $errors[] = trans('validation.empty_template');
                        if (empty($email)) $errors[] = trans('validation.empty_email');
                        if (!empty($email) && StringHelper::isEmail($email) === false) $errors[] = trans('validation.wrong_email');

                        if (count($errors) === 0) {
                            $sendEmail = new SendEmailHelper();
                            $sendEmail->body = $body;
                            $sendEmail->subject = $subject;
                            $sendEmail->prior = $prior;
                            $sendEmail->email = $email;
                            $sendEmail->token = StringHelper::token();
                            $sendEmail->templateId = 0;
                            $sendEmail->tracking = false;
                            $result = $sendEmail->sendEmail();
                            $result_send = ['result' => $result['result'], 'msg' => $result['error'] ? trans('frontend.msg.email_wasnt_sent') : trans('frontend.msg.email_sent')];
                        } else {
                            $msg = implode(",", $errors);

                            return response()->json([
                                'result' => false,
                                'msg' => $msg
                            ]);
                        }

                        $data['subscriber_id'] = 0;
                        $data['email'] = $email;
                        $data['template_id'] = 0;
                        $data['template'] = $subject;
                        $data['success'] = isset($result['result']) && $result['result'] !== true ? 0 : 1;
                        $data['errorMsg'] = isset($result['result']) && $result['result'] !== true ? $result['error'] : '';
                        $data['schedule_id'] = 0;
                        $data['log_id'] = 0;

                        ReadySent::create($data);

                        return response()->json(
                            $result_send
                        );

                    case 'send_out':
                        //  $fh = fopen(__FILE__, 'r');

                        //  if (!flock($fh, LOCK_EX | LOCK_NB)) {
                        //  exit('Script is already running');
                        //  }

                        if (!$request->templateId || !$request->categoryId) {
                            return response()->json([
                                'result' => false,
                            ]);
                        }

                        $logId = $request->input('logId');

                        if ($logId === 0) {
                            return response()->json([
                                'result' => false,
                            ]);
                        }

                        $this->updateProcess('start');

                        $mailcount = 0;

                        $order = (int)SettingsHelper::getInstance()->getValueForKey('RANDOM_SEND') === 1 ? 'RAND()' : 'subscribers.id';
                        $limit = (int)SettingsHelper::getInstance()->getValueForKey('LIMIT_SEND') === 1 ? (int)SettingsHelper::getInstance()->getValueForKey('LIMIT_NUMBER') : null;

                        switch (SettingsHelper::getInstance()->getValueForKey('INTERVAL_TYPE')) {
                            case "minute":
                                $interval = "(subscribers.timeSent < NOW() - INTERVAL '" . (int)SettingsHelper::getInstance()->getValueForKey('INTERVAL_NUMBER') . "' MINUTE)";
                                break;
                            case "hour":
                                $interval = "(subscribers.timeSent < NOW() - INTERVAL '" . (int)SettingsHelper::getInstance()->getValueForKey('INTERVAL_NUMBER') . "' HOUR)";
                                break;
                            case "day":
                                $interval = "(subscribers.timeSent < NOW() - INTERVAL '" . (int)SettingsHelper::getInstance()->getValueForKey('INTERVAL_NUMBER') . "' DAY)";
                                break;
                            default:
                                $interval = null;
                        }

                        $categoryId = [];

                        foreach ($request->categoryId ?? [] as $id) {
                            if (is_numeric($id)) {
                                $categoryId[] = $id;
                            }
                        }

                        $templateId = [];

                        foreach ($request->templateId ?? [] as $id) {
                            if (is_numeric($id)) {
                                $templateId[] = $id;
                            }
                        }

                        $templates = Templates::whereIN('id', $templateId)->get();

                        foreach ($templates ?? [] as $template) {
                            if ($interval) {
                                $subscribers = Subscribers::select('subscribers.email', 'subscribers.token', 'subscribers.id', 'subscribers.name')
                                    ->join('subscriptions', 'subscribers.id', '=', 'subscriptions.subscriber_id')
                                    ->leftJoin('ready_sent', function ($join) use ($template, $logId) {
                                        $join->on('subscribers.id', '=', 'ready_sent.subscriber_id')
                                            ->where('ready_sent.template_id', '=', $template->id)
                                            ->where('ready_sent.log_id', '=', $logId)
                                            ->where(function ($query) {
                                                $query->where('ready_sent.success', 1)
                                                    ->orWhere('ready_sent.success', 0);
                                            });
                                    })
                                    ->whereIN('subscriptions.category_id', $categoryId)
                                    ->where('subscribers.active', 1)
                                    ->whereRaw($interval)
                                    ->groupBy('subscribers.id')
                                    ->groupBy('subscribers.email')
                                    ->groupBy('subscribers.token')
                                    ->groupBy('subscribers.name')
                                    ->orderByRaw($order)
                                    ->take($limit)
                                    ->get();
                            } else {
                                $subscribers = Subscribers::select('subscribers.email', 'subscribers.token', 'subscribers.id', 'subscribers.name')
                                    ->join('subscriptions', 'subscribers.id', '=', 'subscriptions.subscriber_id')
                                    ->leftJoin('ready_sent', function ($join) use ($template, $logId) {
                                        $join->on('subscribers.id', '=', 'ready_sent.subscriber_id')
                                            ->where('ready_sent.template_id', $template->id)
                                            ->where('ready_sent.log_id', $logId)
                                            ->where(function ($query) {
                                                $query->where('ready_sent.success', 1)
                                                    ->orWhere('ready_sent.success', 0);
                                            });
                                    })
                                    ->whereIN('subscriptions.category_id', $categoryId)
                                    ->where('subscribers.active', 1)
                                    ->groupBy('subscribers.id')
                                    ->groupBy('subscribers.email')
                                    ->groupBy('subscribers.token')
                                    ->groupBy('subscribers.name')
                                    ->orderByRaw($order)
                                    ->take($limit)
                                    ->get();
                            }

                            foreach ($subscribers ?? [] as $subscriber) {
                                if ($this->getProcess() === 'stop' || $this->getProcess() === 'pause') {
                                    return response()->json([
                                        'result' => true,
                                        'completed' => true,
                                    ]);
                                }

                                if (SettingsHelper::getInstance()->getValueForKey('sleep') > 0)
                                    sleep(SettingsHelper::getInstance()->getValueForKey('sleep'));

                                $sendEmail = new SendEmailHelper();
                                $sendEmail->body = $template->body;
                                $sendEmail->subject = $template->name;
                                $sendEmail->prior = $template->prior;
                                $sendEmail->email = $subscriber->email;
                                $sendEmail->token = $subscriber->token;
                                $sendEmail->subscriberId = $subscriber->id;
                                $sendEmail->name = $subscriber->name;
                                $sendEmail->templateId = $template->id;
                                $result = $sendEmail->sendEmail();

                                if ($result['result'] === true) {
                                    $data = [
                                        'subscriber_id' => $subscriber->id,
                                        'email' => $subscriber->email,
                                        'template_id' => $template->id,
                                        'template' => $template->name,
                                        'success' => 1,
                                        'schedule_id' => 0,
                                        'log_id' => $logId,
                                    ];

                                    $mailcount++;

                                    Subscribers::find($subscriber->id)->update(['timeSent' => date('Y-m-d H:i:s')]);
                                } else {
                                    $data = [
                                        'subscriber_id' => $subscriber->id,
                                        'email' => $subscriber->email,
                                        'template_id' => $template->id,
                                        'template' => $template->name,
                                        'success' => 0,
                                        'errorMsg' => $result['error'],
                                        'schedule_id' => 0,
                                        'log_id' => $logId,
                                    ];
                                }

                                ReadySent::create($data);

                                unset($data);

                                if ((int)SettingsHelper::getInstance()->getValueForKey('LIMIT_SEND') === 1 && (int)SettingsHelper::getInstance()->getValueForKey('LIMIT_NUMBER') === $mailcount) {

                                    $this->updateProcess('stop');

                                    return response()->json([
                                        'result' => true,
                                        'completed' => true,
                                    ]);
                                }
                            }
                        }

                        if ((int)SettingsHelper::getInstance()->getValueForKey('LIMIT_SEND') === 1 && (int)SettingsHelper::getInstance()->getValueForKey('LIMIT_NUMBER') === $mailcount) {
                            $this->updateProcess('stop');

                            return response()->json([
                                'result' => true,
                                'completed' => true,
                            ]);
                        }

                        $this->updateProcess('stop');

                        return response()->json([
                            'result' => true,
                            'completed' => true,
                        ]);

                    case 'count_send':
                        if (!$request->logId || !$request->categoryId) {
                            return response()->jsone([
                                'result' => false,
                            ]);
                        }

                        $categoryId = [];

                        foreach ($request->categoryId ?? [] as $id) {
                            if (is_numeric($id)) {
                                $categoryId[] = $id;
                            }
                        }

                        $logId = $request->input('logId');

                        $limit = (int)SettingsHelper::getInstance()->getValueForKey('LIMIT_SEND') === 1 ? (int)SettingsHelper::getInstance()->getValueForKey('LIMIT_NUMBER') : null;

                        switch (SettingsHelper::getInstance()->getValueForKey('INTERVAL_TYPE')) {
                            case "minute":
                                $interval = "(subscribers.timeSent < NOW() - INTERVAL '" . (int)SettingsHelper::getInstance()->getValueForKey('INTERVAL_NUMBER') . "' MINUTE)";
                                break;
                            case "hour":
                                $interval = "(subscribers.timeSent < NOW() - INTERVAL '" . (int)SettingsHelper::getInstance()->getValueForKey('INTERVAL_NUMBER') . "' HOUR)";
                                break;
                            case "day":
                                $interval = "(subscribers.timeSent < NOW() - INTERVAL '" . (int)SettingsHelper::getInstance()->getValueForKey('INTERVAL_NUMBER') . "' DAY)";
                                break;
                            default:
                                $interval = null;
                        }

                        if ($interval) {
                            $total = Subscriptions::select('subscribers.id')
                                ->join('subscribers', 'subscriptions.subscriber_id', '=', 'subscribers.id')
                                ->where('subscribers.active', 1)
                                ->whereIN('subscriptions.category_id', $categoryId)
                                ->whereRaw($interval)
                                ->groupBy('subscribers.id')
                                ->take($limit)
                                ->get()
                                ->count();
                        } else {
                            $total = Subscriptions::select('subscribers.id')
                                ->join('subscribers', 'subscriptions.subscriber_id', '=', 'subscribers.id')
                                ->where('subscribers.active', 1)
                                ->whereIN('subscriptions.category_id', $categoryId)
                                ->groupBy('subscribers.id')
                                ->take($limit)
                                ->get()
                                ->count();
                        }

                        $success = ReadySent::where('log_id', $logId)
                            ->where('success', 1)
                            ->count();

                        $unsuccess = ReadySent::where('log_id', $logId)
                            ->where('success', 0)
                            ->count();

                        $sleep = (int)SettingsHelper::getInstance()->getValueForKey('sleep') === 0 ? 0.5 : (int)SettingsHelper::getInstance()->getValueForKey('sleep');
                        $timesec = intval(($total - ($success + $unsuccess)) * $sleep);

                        $datetime = new DateTime();
                        $datetime->setTime(0, 0, $timesec);

                        return response()->json([
                            'result' => true,
                            'status' => 1,
                            'total' => $total,
                            'success' => $success,
                            'unsuccessful' => $unsuccess,
                            'time' => $datetime->format('H:i:s'),
                            'leftsend' => $total > 0 ? round(($success + $unsuccess) / $total * 100, 2) : 0,
                        ]);

                    case 'log_online':
                        $readySent = ReadySent::orderBy('id', 'desc')
                            ->where('log_id', '>', 0)
                            ->limit(5)
                            ->get();

                        if ($readySent) {
                            $rows = [];

                            foreach ($readySent ?? [] as $row) {
                                $rows[] = [
                                    'subscriber_id' => $row->subscriber_id,
                                    "email" => $row->email,
                                    "status" => $row->success == 1 ? trans('frontend.str.sent') : trans('frontend.str.not_sent'),
                                ];
                            }

                            return response()->json([
                                'result' => true,
                                'item' => $rows
                            ]);

                        } else {
                            return response()->json([
                                'result' => false,
                            ]);
                        }

                    case 'start_mailing':
                        $log = Logs::create(['time' => date('Y-m-d H:i:s')]);
                        $logId = $log->id;

                        return response()->json([
                            'result' => true,
                            'logId' => $logId
                        ]);

                    case 'get_categories':
                        $category = Category::select('name', 'id')->get();

                        return response()->json([
                            'items' => $category
                        ]);

                    case 'process':
                        if ($request->command) {
                            $this->updateProcess($request->command);

                            return response()->json([
                                'result' => true,
                                'command' => $request->command
                            ]);

                        } else {
                            return response()->json([
                                'result' => false,
                            ]);
                        }
                }
            }
        } catch (\Exception $e) {
            return response()->json([
                'result' => false,
                'errors' => $e->getMessage()
            ]);
        }
    }

    /**
     * @return string
     */
    private function getProcess(): string
    {
        $process = Process::where('user_id', Auth::user('web')->id)->first();

        if ($process) {
            return $process->command;
        } else {
            $process = new Process();
            $process->command = 'start';
            $process->user_id = Auth::user('web')->id;
            $process->save();

            return 'start';
        }
    }

    /**
     * @param $command
     */
    private function updateProcess($command): void
    {
        $result = Process::where('user_id', Auth::user('web')->id);

        if ($result->first()) {
            $result->update(['command' => $command]);
        } else {
            $process = new Process();
            $process->command = $command;
            $process->user_id = Auth::user('web')->id;
            $process->save();
        }
    }
}
