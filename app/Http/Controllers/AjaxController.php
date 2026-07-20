<?php

namespace App\Http\Controllers;

use App\Enums\AjaxAction;
use App\Helpers\UpdateHelper;
use App\Http\Requests\AjaxActionRequest;
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
use Illuminate\Support\Facades\Cookie;
use PHPMailer\PHPMailer\Exception;

class AjaxController extends Controller
{
    /**
     * Inject services and repositories used by shared admin AJAX actions.
     */
    public function __construct(
        private readonly UpdateService $updateService,
        private readonly ScheduleRepository $scheduleRepository,
        private readonly AttachRepository $attachRepository,
        private readonly SendMailService $sendMailService,
        private readonly ReadySentRepository $readySentRepository,
        private readonly ProcessRepository $processRepository,
    ) {}

    /**
     * Dispatch an AJAX action and return a normalized JSON response.
     */
    public function action(AjaxActionRequest $request): JsonResponse
    {
        @set_time_limit(0);

        try {
            return response()->json($this->getResult($request->ajaxAction(), $request));
        } catch (\Throwable $e) {
            report($e);

            return response()->json([
                'result' => false,
                'errors' => __('frontend.str.error_server'),
            ], 500);
        }
    }

    /**
     * Resolve the requested AJAX action to the matching service or repository operation.
     *
     * @return array|false[]|true[]
     *
     * @throws Exception
     * @throws \Throwable
     */
    private function getResult(AjaxAction $action, Request $request): array
    {
        return match ($action) {
            AjaxAction::StartUpdate => $this->updateService->startUpdate($this->updateHelper(), $request),

            AjaxAction::AlertUpdate => $this->updateService->alertUpdate($this->updateHelper()),

            AjaxAction::RemoveSchedule => [
                'result' => $this->scheduleRepository->removeSchedule((int) $request->input('id')),
                'id' => (int) $request->input('id'),
            ],

            AjaxAction::ChangeLanguage => $this->changeLanguage($request),

            AjaxAction::RemoveAttach => $this->removeAttach($request),

            AjaxAction::SendTestEmail => $this->sendMailService->sendTest($request),

            AjaxAction::SendOut => $this->sendMailService->sendOut($request),

            AjaxAction::CountSend => $this->sendMailService->countSend($request),

            AjaxAction::LogOnline => $this->readySentRepository->logOnline(5),

            AjaxAction::StartMailing => $this->startMailing(),

            AjaxAction::GetCategories => [
                'items' => Category::query()->get(),
            ],

            AjaxAction::Process => $this->processCommand($request),
        };
    }

    /**
     * Build update metadata only for actions that actually need it.
     */
    private function updateHelper(): UpdateHelper
    {
        return new UpdateHelper(app()->getLocale(), env('VERSION'));
    }

    /**
     * Store the selected interface locale in a long-lived cookie.
     *
     * @return true[]
     */
    private function changeLanguage(Request $request): array
    {
        $locale = (string) $request->input('locale');

        Cookie::queue(
            Cookie::forever('lang', $locale)
        );

        return ['result' => true];
    }

    /**
     * Remove an uploaded template attachment by ID.
     *
     * @return true[]
     */
    private function removeAttach(Request $request): array
    {
        $this->attachRepository->remove((int) $request->input('id'));

        return ['result' => true];
    }

    /**
     * Create a log record that marks the start of a manual mailing run.
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
     * Persist the current user's long-running process command.
     *
     * @return array|false[]
     */
    private function processCommand(Request $request): array
    {
        $command = $request->input('command');

        if (empty($command)) {
            return ['result' => false];
        }

        $userId = Auth::id();

        if (! $userId) {
            return ['result' => false];
        }

        $this->processRepository->updateByUserId($userId, $command);

        return [
            'result' => true,
            'command' => $command,
        ];
    }
}
