<?php

namespace App\Console\Commands;


use App\DTO\ReadySentCreateData;
use App\Helpers\SendEmailHelper;
use App\Helpers\SettingsHelper;
use App\Models\Subscribers;
use App\Repositories\ReadySentRepository;
use App\Repositories\ScheduleRepository;
use App\Repositories\SubscriberRepository;
use Illuminate\Console\Command;
use Illuminate\Contracts\Console\Isolatable;
use Illuminate\Support\Facades\DB;

class SendEmails extends Command implements Isolatable
{
    protected $signature = 'emails:send';

    protected $description = 'Send emails to subscribers';

    public function __construct(
        private readonly ScheduleRepository   $scheduleRepository,
        private readonly SubscriberRepository $subscribersRepository,
        private readonly ReadySentRepository  $readySentRepository,
    )
    {
        parent::__construct();
    }

    public function handle(): int
    {
        @set_time_limit(0);

        $mailCountNo = 0;
        $mailCount = 0;

        $schedule = $this->scheduleRepository->getScheduleEvent();

        foreach ($schedule ?? [] as $row) {
            if (!$row->template) {
                continue;
            }

            $order = (int)SettingsHelper::getInstance()->getValueForKey('RANDOM_SEND') === 1
                ? 'RAND()'
                : 'subscribers.id';

            $limit = (int)SettingsHelper::getInstance()->getValueForKey('LIMIT_SEND') === 1
                ? (int)SettingsHelper::getInstance()->getValueForKey('LIMIT_NUMBER')
                : null;

            $interval = $this->resolveInterval(
                (string)SettingsHelper::getInstance()->getValueForKey('INTERVAL_TYPE'),
                (int)SettingsHelper::getInstance()->getValueForKey('INTERVAL_NUMBER')
            );

            $subscribers = $this->subscribersRepository->getSubscribersNotReadySent(
                $row->id,
                $order,
                $limit,
                $interval
            );

            $subscriberUpdates = [];

            foreach ($subscribers ?? [] as $subscriber) {
                if ((int)SettingsHelper::getInstance()->getValueForKey('sleep') > 0) {
                    sleep((int)SettingsHelper::getInstance()->getValueForKey('sleep'));
                }

                $result = $this->sendToSubscriber($row, $subscriber);

                $this->readySentRepository->add(new ReadySentCreateData(
                    subscriberId: $subscriber->id,
                    templateId: $row->template_id,
                    success: $result['result'] === true ? 1 : 0,
                    scheduleId: $row->id,
                    logId: 0,
                    email: $subscriber->email,
                    template: $row->template->name,
                    errorMsg: $result['error'] ?? null,
                    readMail: null,
                ));

                if ($result['result'] === true) {
                    $subscriberUpdates[$subscriber->id] = now()->format('Y-m-d H:i:s');
                    $mailCount++;
                } else {
                    $mailCountNo++;
                }

                if (
                    (int)SettingsHelper::getInstance()->getValueForKey('LIMIT_SEND') === 1
                    && $mailCount >= (int)SettingsHelper::getInstance()->getValueForKey('LIMIT_NUMBER')
                ) {
                    $this->resultSend($subscriberUpdates);
                    break;
                }
            }

            $this->resultSend($subscriberUpdates);

            if (
                (int)SettingsHelper::getInstance()->getValueForKey('LIMIT_SEND') === 1
                && $mailCount >= (int)SettingsHelper::getInstance()->getValueForKey('LIMIT_NUMBER')
            ) {
                break;
            }
        }

        $this->line('sent: ' . $mailCount);
        $this->line('no sent: ' . $mailCountNo);

        return self::SUCCESS;
    }

    private function sendToSubscriber(object $schedule, object $subscriber): array
    {
        $sendEmail = new SendEmailHelper();
        $sendEmail->body = $schedule->template->body;
        $sendEmail->subject = $schedule->template->name;
        $sendEmail->prior = $schedule->template->prior;
        $sendEmail->email = $subscriber->email;
        $sendEmail->token = $subscriber->token;
        $sendEmail->subscriberId = $subscriber->id;
        $sendEmail->name = $subscriber->name;
        $sendEmail->templateId = $schedule->template->id;

        return $sendEmail->sendEmail();
    }

    private function resolveInterval(string $intervalType, int $intervalNumber): ?string
    {
        if ($intervalNumber <= 0) {
            return null;
        }

        return match ($intervalType) {
            'minute' => "(subscribers.timeSent < NOW() - INTERVAL '{$intervalNumber}' MINUTE)",
            'hour' => "(subscribers.timeSent < NOW() - INTERVAL '{$intervalNumber}' HOUR)",
            'day' => "(subscribers.timeSent < NOW() - INTERVAL '{$intervalNumber}' DAY)",
            default => null,
        };
    }

    private function resultSend(array $subscriberUpdates): void
    {
        if ($subscriberUpdates === []) {
            return;
        }

        $ids = array_keys($subscriberUpdates);
        $caseSql = 'CASE id ';
        $bindings = [];

        foreach ($subscriberUpdates as $id => $ts) {
            $caseSql .= 'WHEN ? THEN ? ';
            $bindings[] = (int)$id;
            $bindings[] = $ts;
        }

        $caseSql .= 'END';

        $inSql = implode(',', array_fill(0, count($ids), '?'));
        $bindings = array_merge($bindings, array_map('intval', $ids));

        DB::statement(
            'UPDATE ' . Subscribers::getTableName() . " SET timeSent = {$caseSql} WHERE id IN ({$inSql})",
            $bindings
        );
    }
}
