<?php

namespace App\Services;


use App\Enums\ProcessStatus;
use App\DTO\ReadySentCreateData;
use App\Helpers\SettingsHelper;
use App\Models\Subscribers;
use App\Models\Templates;
use App\Repositories\{
    ReadySentRepository,
    SubscriberRepository,
    ProcessRepository,
};
use App\Helpers\SendEmailHelper;
use App\Helpers\StringHelper;
use Illuminate\Http\Request;
use Auth;
use DateTime;


class SendMailService
{
    /**
     * @param ReadySentRepository $readySentRepository
     * @param SubscriberRepository $subscribersRepository
     * @param ProcessRepository $processRepository
     */
    public function __construct(
        private ReadySentRepository  $readySentRepository,
        private SubscriberRepository $subscribersRepository,
        private ProcessRepository    $processRepository,
    )
    {
    }

    /**
     * @param Request $request
     * @return array
     * @throws \PHPMailer\PHPMailer\Exception
     */
    public function sendTest(Request $request): array
    {
        $subject = $request->input('name');
        $body = $request->input('body');
        $prior = $request->input('prior');
        $email = $request->input('email');

        $errors = [];

        if (empty($subject)) $errors[] = __('validation.empty_name');
        if (empty($body)) $errors[] = __('validation.empty_template');
        if (empty($email)) $errors[] = __('validation.empty_email');
        if (!empty($email) && StringHelper::isEmail($email) === false) $errors[] = __('validation.wrong_email');

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

            $this->readySentRepository->add(new ReadySentCreateData(
                subscriberId: 0,
                templateId: 0,
                success: $result['result'] ?? 0,
                scheduleId: 0,
                logId: 0,
                email: $email,
                template: 0,
                errorMsg: $result['error'] ?? null,
                readMail: null
            ));

            return ['result' => $result['result'], 'msg' => $result['error'] ? __('frontend.msg.email_wasnt_sent') : __('frontend.msg.email_sent')];
        } else {
            $msg = implode(",", $errors);

            $this->readySentRepository->add(new ReadySentCreateData(
                subscriberId: 0,
                templateId: 0,
                success: 0,
                scheduleId: 0,
                logId: 0,
                email: $email,
                template: 0,
                errorMsg: null,
                readMail: null
            ));

            return [
                'result' => false,
                'msg' => $msg
            ];
        }
    }


    /**
     * @param Request $request
     * @return array
     * @throws \PHPMailer\PHPMailer\Exception
     */
    public function sendOut(Request $request): array
    {
        if (!$request->templateId || !$request->categoryId) {
            return ['result' => false];
        }

        $logId = $request->input('logId');

        if ($logId === 0) {
            return ['result' => false];
        }

        $this->processRepository->updateByUserId(Auth::user('web')->id, ProcessStatus::Start->value);

        $mailCount = 0;

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

            $subscribers = $this->subscribersRepository->getSubscribers($logId, $template->id, $categoryId, $order, $limit, $interval);

            foreach ($subscribers ?? [] as $subscriber) {
                if ($this->processRepository->getProcess(Auth::user('web')->id) === 'stop' || $this->processRepository->getProcess(Auth::user('web')->id) === 'pause') {
                    return [
                        'result' => true,
                        'completed' => true,
                    ];
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
                    $this->readySentRepository->add(new ReadySentCreateData(
                        subscriberId: $subscriber->id,
                        templateId: $template->id,
                        success: 1,
                        scheduleId: 0,
                        logId: $logId,
                        email: $subscriber->email,
                        template: $template->name,
                        errorMsg: null,
                        readMail: null
                    ));

                    $mailCount++;

                    Subscribers::find($subscriber->id)->update(['timeSent' => date('Y-m-d H:i:s')]);
                } else {
                    $this->readySentRepository->add(new ReadySentCreateData(
                        subscriberId: $subscriber->id,
                        templateId: $template->id,
                        success: 0,
                        scheduleId: 0,
                        logId: $logId,
                        email: $subscriber->email,
                        template: $template->name,
                        errorMsg: $result['error'],
                        readMail: null
                    ));
                }

                if ((int)SettingsHelper::getInstance()->getValueForKey('LIMIT_SEND') === 1 && (int)SettingsHelper::getInstance()->getValueForKey('LIMIT_NUMBER') === $mailCount) {
                    $this->processRepository->updateByUserId(Auth::user('web')->id, ProcessStatus::Stop->value);

                    return [
                        'result' => true,
                        'completed' => true,
                    ];
                }
            }
        }

        if ((int)SettingsHelper::getInstance()->getValueForKey('LIMIT_SEND') === 1 && (int)SettingsHelper::getInstance()->getValueForKey('LIMIT_NUMBER') === $mailCount) {
            $this->processRepository->updateByUserId(Auth::user('web')->id, ProcessStatus::Stop->value);

            return [
                'result' => true,
                'completed' => true,
            ];
        }

        $this->processRepository->updateByUserId(Auth::user('web')->id, ProcessStatus::Stop->value);

        return [
            'result' => true,
            'completed' => true,
        ];
    }

    /**
     * @param Request $request
     * @return array
     */
    public function countSend(Request $request): array
    {
        if (!$request->logId || !$request->categoryId) {
            return [
                'result' => false
            ];
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

        $total = $this->subscribersRepository->countSubscriptions($categoryId, $limit, $interval);
        $success = $this->readySentRepository->countStatus($logId, 1);
        $unsuccess = $this->readySentRepository->countStatus($logId, 0);

        $sleep = (int)SettingsHelper::getInstance()->getValueForKey('sleep') === 0 ? 0.5 : (int)SettingsHelper::getInstance()->getValueForKey('sleep');
        $timeSec = intval(($total - ($success + $unsuccess)) * $sleep);

        $datetime = new DateTime();
        $datetime->setTime(0, 0, $timeSec);

        return [
            'result' => true,
            'status' => 1,
            'total' => $total,
            'success' => $success,
            'unsuccessful' => $unsuccess,
            'time' => $datetime->format('H:i:s'),
            'leftsend' => $total > 0 ? round(($success + $unsuccess) / $total * 100, 2) : 0,
        ];
    }
}
