<?php

namespace App\Http\Controllers;

use App\Services\UpdateService;
use App\Services\SendMailService;
use App\Repositories\{
    ScheduleRepository,
    AttachRepository,
    ReadySentRepository,
    ProcessRepository,
};
use App\Models\{
    Category,
    Logs
};
use App\Helpers\{UpdateHelper};
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Cookie;
use Config;
use Auth;
use Exception;

class AjaxController extends Controller
{
    public function __construct(
        private UpdateService       $updateService,
        private ScheduleRepository  $scheduleRepository,
        private AttachRepository    $attachRepository,
        private SendMailService     $sendMailService,
        private ReadySentRepository $readySentRepository,
        private ProcessRepository   $processRepository,
    )
    {
    }

    /**
     * @param Request $request
     * @return JsonResponse
     * @throws \PHPMailer\PHPMailer\Exception
     */
    public function action(Request $request): JsonResponse
    {
        @set_time_limit(0);

        try {
            return response()->json($this->getResult($request));
        } catch (Exception $e) {
            report($e);

            return response()->json([
                'result' => false,
                'errors' => $e->getMessage()
            ]);
        }
    }

    /**
     * @param Request $request
     * @return array
     * @throws \PHPMailer\PHPMailer\Exception
     */
    private function getResult(Request $request): array
    {
        $update = new UpdateHelper(app()->getLocale(), env('VERSION'));

        if ($request->input('action')) {
            switch ($request->input('action')) {
                case 'start_update':
                    return $this->updateService->startUpdate($update, $request);

                case 'alert_update':
                    return $this->updateService->alertUpdate($update);

                case 'remove_schedule':
                    return [
                        'result' => $this->scheduleRepository->removeSchedule($request->input('id')),
                        'id' => $request->input('id')
                    ];

                case 'change_lng':
                    if ($request->input('locale')) {
                        if (in_array($request->input('locale'), Config::get('app.locales'))) {
                            Cookie::queue(
                                Cookie::forever('lang', $request->input('locale')));
                        }
                    }

                    return ['result' => true];

                case 'remove_attach':
                    $this->attachRepository->remove($request->input('id'));

                    return ['result' => true];

                case 'send_test_email':
                    return $this->sendMailService->sendTest($request);

                case 'send_out':
                    //  $fh = fopen(__FILE__, 'r');

                    //  if (!flock($fh, LOCK_EX | LOCK_NB)) {
                    //  exit('Script is already running');
                    //  }

                    return $this->sendMailService->sendOut($request);

                case 'count_send':
                    return $this->sendMailService->countSend($request);

                case 'log_online':
                    return $this->readySentRepository->logOnline(5);

                case 'start_mailing':
                    $log = Logs::create(['time' => date('Y-m-d H:i:s')]);
                    $logId = $log->id;

                    return [
                        'result' => true,
                        'logId' => $logId
                    ];

                case 'get_categories':
                    $category = Category::query()->get();

                    return [
                        'items' => $category
                    ];

                case 'process':
                    if ($request->command) {
                        $this->processRepository->updateByUserId(Auth::user('web')->id, $request->command);

                        return [
                            'result' => true,
                            'command' => $request->command
                        ];
                    } else {
                        return ['result' => false];
                    }

                default:
                    return [];
            }
        }

        return [];
    }
}
