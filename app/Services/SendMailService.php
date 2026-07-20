<?php

namespace App\Services;

use App\DTO\MailMessageData;
use App\DTO\ReadySentCreateData;
use App\Enums\ProcessStatus;
use App\Helpers\SettingsHelper;
use App\Helpers\StringHelper;
use App\Models\Subscribers;
use App\Models\Templates;
use App\Repositories\ProcessRepository;
use App\Repositories\ReadySentRepository;
use App\Repositories\SubscriberRepository;
use Auth;
use DateTime;
use Illuminate\Http\Request;
use PHPMailer\PHPMailer\Exception;

class SendMailService
{
    public function __construct(
        private readonly ReadySentRepository $readySentRepository,
        private readonly SubscriberRepository $subscribersRepository,
        private readonly ProcessRepository $processRepository,
        private readonly MailSender $mailSender,
        private readonly MailingOptionsResolver $mailingOptionsResolver,
        private readonly SubscriberSentTimeUpdater $subscriberSentTimeUpdater,
    ) {}

    /**
     * @throws Exception
     */
    public function sendTest(Request $request): array
    {
        $subject = $request->input('name');
        $body = $request->input('body');
        $prior = $request->input('prior');
        $email = $request->input('email');

        $errors = [];

        if (empty($subject)) {
            $errors[] = __('validation.empty_name');
        }
        if (empty($body)) {
            $errors[] = __('validation.empty_template');
        }
        if (empty($email)) {
            $errors[] = __('validation.empty_email');
        }
        if (! empty($email) && StringHelper::isEmail($email) === false) {
            $errors[] = __('validation.wrong_email');
        }

        if (count($errors) === 0) {
            $result = $this->mailSender->send(new MailMessageData(
                subject: $subject,
                body: $body,
                email: $email,
                prior: (int) $prior,
                token: StringHelper::token(),
                tracking: false,
            ));

            // Test emails are not tied to real subscribers/templates/schedules/logs,
            // so we must not write fake foreign keys like 0 into ready_sent.
            return [
                'result' => (bool) ($result['result'] ?? false),
                'msg' => ! empty($result['error']) ? __('frontend.msg.email_wasnt_sent') : __('frontend.msg.email_sent'),
            ];
        }

        return [
            'result' => false,
            'msg' => implode(',', $errors),
        ];
    }

    /**
     * @throws Exception
     */
    public function sendOut(Request $request): array
    {
        $templateIds = collect((array) $request->input('templateId', []))
            ->filter(static fn ($id) => is_numeric($id))
            ->map(static fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();

        $categoryIds = collect((array) $request->input('categoryId', []))
            ->filter(static fn ($id) => is_numeric($id))
            ->map(static fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();

        $logId = (int) $request->input('logId');

        if (empty($templateIds) || empty($categoryIds) || $logId <= 0) {
            return [
                'result' => false,
                'errors' => __('frontend.str.error_server'),
            ];
        }

        $userId = Auth::user('web')->id;
        $this->processRepository->updateByUserId($userId, ProcessStatus::Start->value);
        $subscriberUpdates = [];

        try {
            $mailCount = 0;
            $options = $this->mailingOptionsResolver->resolve();

            $templates = Templates::whereIn('id', $templateIds)->get();

            foreach ($templates ?? [] as $template) {

                $subscribers = $this->subscribersRepository->getSubscribers(
                    $logId,
                    $template->id,
                    $categoryIds,
                    $options->order,
                    $options->limit,
                    $options->interval,
                );

                $subscriberUpdates = [];

                foreach ($subscribers ?? [] as $subscriber) {
                    $processStatus = $this->processRepository->getProcess($userId);

                    if (in_array($processStatus, [ProcessStatus::Stop->value, ProcessStatus::Pause->value], true)) {
                        $this->subscriberSentTimeUpdater->update($subscriberUpdates);

                        return [
                            'result' => true,
                            'completed' => true,
                        ];
                    }

                    $result = $this->mailSender->sendTemplate($template, $subscriber);
                    $sent = ($result['result'] ?? false) === true;

                    $this->readySentRepository->add(new ReadySentCreateData(
                        subscriberId: $subscriber->id,
                        templateId: $template->id,
                        success: $sent ? 1 : 0,
                        scheduleId: null,
                        logId: $logId,
                        email: $subscriber->email,
                        template: $template->name,
                        errorMsg: $sent ? null : ($result['error'] ?? null),
                        readMail: null,
                    ));

                    if ($sent) {
                        $mailCount++;
                        $subscriberUpdates[$subscriber->id] = now()->format('Y-m-d H:i:s');
                    }

                    if ($options->limitReached($mailCount)) {
                        $this->processRepository->updateByUserId($userId, ProcessStatus::Stop->value);
                        $this->subscriberSentTimeUpdater->update($subscriberUpdates);

                        return [
                            'result' => true,
                            'completed' => true,
                        ];
                    }
                }

                $this->subscriberSentTimeUpdater->update($subscriberUpdates);
            }

            $this->processRepository->updateByUserId($userId, ProcessStatus::Stop->value);

            return [
                'result' => true,
                'completed' => true,
            ];
        } catch (\Throwable $exception) {
            try {
                $this->subscriberSentTimeUpdater->update($subscriberUpdates);
            } catch (\Throwable $cleanupException) {
                report($cleanupException);
            }

            try {
                $this->processRepository->updateByUserId($userId, ProcessStatus::Stop->value);
            } catch (\Throwable $cleanupException) {
                report($cleanupException);
            }

            throw $exception;
        }
    }

    public function countSend(Request $request): array
    {
        if (! $request->logId || ! $request->categoryId) {
            return [
                'result' => false,
            ];
        }

        $categoryId = [];

        foreach ($request->categoryId ?? [] as $id) {
            if (is_numeric($id)) {
                $categoryId[] = $id;
            }
        }

        $logId = $request->input('logId');

        $options = $this->mailingOptionsResolver->resolve();

        $total = $this->subscribersRepository->countSubscriptions(
            $categoryId,
            $options->limit,
            $options->interval,
        );
        $success = $this->readySentRepository->countStatus($logId, 1);
        $unsuccess = $this->readySentRepository->countStatus($logId, 0);

        $sleepSetting = (int) SettingsHelper::getInstance()->getValueForKey('SLEEP');
        $sleep = $sleepSetting === 0 ? 0.5 : $sleepSetting;
        $timeSec = intval(($total - ($success + $unsuccess)) * $sleep);

        $datetime = new DateTime;
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

    /**
     * @throws Exception
     */
    public function sendFrontendSubscriberEmails(Subscribers $subscriber): void
    {
        $settings = SettingsHelper::getInstance();

        $requireConfirmation = (int) $settings->getValueForKey('REQUIRE_SUB_CONFIRMATION') === 1;
        $notifyNewSubscriber = (int) $settings->getValueForKey('NEW_SUBSCRIBER_NOTIFY') === 1;

        if ($requireConfirmation) {
            $confirmUrl = route('frontend.subscribe', [
                'subscriber' => $subscriber->id,
                'token' => $subscriber->token,
            ]);

            $message = str_replace(
                ["\r\n", "\r", "\n"],
                '<br>',
                $settings->getValueForKey('TEXT_CONFIRMATION')
            );

            $message = str_replace('%CONFIRM%', $confirmUrl, $message);

            $this->mailSender->send(new MailMessageData(
                subject: (string) $settings->getValueForKey('SUBJECT_TEXT_CONFIRM'),
                body: $message,
                email: (string) $subscriber->email,
                name: $subscriber->name,
                subscriberId: (int) $subscriber->id,
                token: (string) $subscriber->token,
                tracking: false,
                unsubscribe: false,
            ));
        }

        if ($notifyNewSubscriber) {
            $subject = str_replace(
                '%SITE%',
                request()->getHost(),
                __('frontend.str.notification_newuser')
            );

            $message = __('frontend.str.notification_newuser').
                "\nName: {$subscriber->name} \nE-mail: {$subscriber->email}\n";

            $message = str_replace('%SITE%', request()->getHost(), $message);

            $this->mailSender->send(new MailMessageData(
                subject: $subject,
                body: $message,
                email: (string) $settings->getValueForKey('EMAIL'),
                name: (string) $settings->getValueForKey('FROM'),
                tracking: false,
                unsubscribe: false,
            ));
        }
    }
}
