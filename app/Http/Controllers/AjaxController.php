<?php

namespace App\Http\Controllers;

use App\Helpers\UpdateHelper;
use App\Models\Category;
use App\Models\Logs;
use App\Repositories\AttachRepository;
use App\Repositories\ProcessRepository;
use App\Repositories\ReadySentRepository;
use App\Repositories\ScheduleRepository;
use App\Services\SendMailService;
use App\Services\UpdateService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Cookie;

class AjaxController extends Controller
{
    public function __construct(
        private readonly UpdateService $updateService,
        private readonly ScheduleRepository $scheduleRepository,
        private readonly AttachRepository $attachRepository,
        private readonly SendMailService $sendMailService,
        private readonly ReadySentRepository $readySentRepository,
        private readonly ProcessRepository $processRepository,
    ) {
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function action(Request $request): JsonResponse
    {
        @set_time_limit(0);

        try {
            return response()->json($this->getResult($request));
        } catch (\Throwable $e) {
            report($e);

            return response()->json([
                'result' => false,
                'errors' => $e->getMessage(),
            ]);
        }
    }

    /**
     * @param Request $request
     * @return array|false[]|true[]
     * @throws \PHPMailer\PHPMailer\Exception
     * @throws \Throwable
     */
    private function getResult(Request $request): array
    {
        $action = (string) $request->input('action');

        if ($action === '') {
            return [];
        }

        $update = new UpdateHelper(app()->getLocale(), env('VERSION'));

        return match ($action) {
            'start_update' => $this->updateService->startUpdate($update, $request),

            'alert_update' => $this->updateService->alertUpdate($update),

            'remove_schedule' => [
                'result' => $this->scheduleRepository->removeSchedule((int) $request->input('id')),
                'id' => (int) $request->input('id'),
            ],

            'change_lng' => $this->changeLanguage($request),

            'remove_attach' => $this->removeAttach($request),

            'send_test_email' => $this->sendMailService->sendTest($request),

            'send_out' => $this->sendMailService->sendOut($request),

            'count_send' => $this->sendMailService->countSend($request),

            'log_online' => $this->readySentRepository->logOnline(5),

            'start_mailing' => $this->startMailing(),

            'get_categories' => [
                'items' => Category::query()->get(),
            ],

            'process' => $this->processCommand($request),

            default => [],
        };
    }

    /**
     * @param Request $request
     * @return true[]
     */
    private function changeLanguage(Request $request): array
    {
        $locale = (string) $request->input('locale');

        if ($locale !== '' && in_array($locale, Config::get('app.locales', []), true)) {
            Cookie::queue(
                Cookie::forever('lang', $locale)
            );
        }

        return ['result' => true];
    }

    /**
     * @param Request $request
     * @return true[]
     */
    private function removeAttach(Request $request): array
    {
        $this->attachRepository->remove((int) $request->input('id'));

        return ['result' => true];
    }

    /**
     * @return array
     */
    private function startMailing(): array
    {
        $log = Logs::query()->create([
            'time' => now(),
        ]);

        return [
            'result' => true,
            'logId' => $log->id,
        ];
    }

    /**
     * @param Request $request
     * @return array|false[]
     */
    private function processCommand(Request $request): array
    {
        $command = $request->input('command');

        if (empty($command)) {
            return ['result' => false];
        }

        $userId = Auth::id();

        if (!$userId) {
            return ['result' => false];
        }

        $this->processRepository->updateByUserId($userId, $command);

        return [
            'result' => true,
            'command' => $command,
        ];
    }
}
