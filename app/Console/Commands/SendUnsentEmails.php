<?php

namespace App\Console\Commands;


use App\Helpers\SendEmailHelper;
use App\Helpers\SettingsHelper;
use App\Models\ReadySent;
use App\Models\Subscribers;
use App\Repositories\ScheduleRepository;
use App\Repositories\SubscriberRepository;
use Illuminate\Console\Command;
use Illuminate\Contracts\Console\Isolatable;
use Illuminate\Support\Facades\DB;

class SendUnsentEmails extends Command implements Isolatable
{
    protected $signature = 'emails:unsent';

    protected $description = 'Send unsent emails to subscribers';

    public function __construct(
        private readonly ScheduleRepository $scheduleRepository,
        private readonly SubscriberRepository $subscribersRepository,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        @set_time_limit(0);

        $this->line('start unsent sending emails');

        $mailCountNo = 0;
        $mailCount = 0;

        $schedule = $this->scheduleRepository->getScheduleEvent();

        foreach ($schedule ?? [] as $row) {
            if (!$row->template) {
                continue;
            }

            $order = (int) SettingsHelper::getInstance()->getValueForKey('RANDOM_SEND') === 1
                ? 'RAND()'
                : 'subscribers.id';

            $limit = (int) SettingsHelper::getInstance()->getValueForKey('LIMIT_SEND') === 1
                ? (int) SettingsHelper::getInstance()->getValueForKey('LIMIT_NUMBER')
                : null;

            $interval = $this->resolveInterval(
                (string) SettingsHelper::getInstance()->getValueForKey('INTERVAL_TYPE'),
                (int) SettingsHelper::getInstance()->getValueForKey('INTERVAL_NUMBER')
            );

            $subscribers = $this->subscribersRepository->getSubscribersUnSent(
                $row->id,
                $order,
                $limit,
                $interval
            );

            $scheduleIds = [];
            $subscriberUpdates = [];

            foreach ($subscribers ?? [] as $subscriber) {
                if ((int) SettingsHelper::getInstance()->getValueForKey('sleep') > 0) {
                    sleep((int) SettingsHelper::getInstance()->getValueForKey('sleep'));
                }

                $result = $this->sendToSubscriber($row, $subscriber);

                if ($result['result'] === true) {
                    $subscriberUpdates[$subscriber->id] = now()->format('Y-m-d H:i:s');
                    $scheduleIds[] = $row->id;
                    $mailCount++;
                } else {
                    $mailCountNo++;
                }

                if (
                    (int) SettingsHelper::getInstance()->getValueForKey('LIMIT_SEND') === 1
                    && $mailCount >= (int) SettingsHelper::getInstance()->getValueForKey('LIMIT_NUMBER')
                ) {
                    $this->resultSend($scheduleIds, $subscriberUpdates);
                    break;
                }
            }

            $this->resultSend($scheduleIds, $subscriberUpdates);

            if (
                (int) SettingsHelper::getInstance()->getValueForKey('LIMIT_SEND') === 1
                && $mailCount >= (int) SettingsHelper::getInstance()->getValueForKey('LIMIT_NUMBER')
            ) {
                break;
            }
        }

        $this->line('sent: ' . $mailCount);
        $this->line('no sent: ' . $mailCountNo);

        return self::SUCCESS;
    }

    /**
     * @param object $schedule
     * @param object $subscriber
     * @return array
     * @throws \PHPMailer\PHPMailer\Exception
     */
    private function sendToSubscriber(object $schedule, object $subscriber): array
    {
        $sendMail = new SendEmailHelper();
        $sendMail->body = $schedule->template->body;
        $sendMail->subject = $schedule->template->name;
        $sendMail->prior = $schedule->template->prior;
        $sendMail->email = $subscriber->email;
        $sendMail->token = $subscriber->token;
        $sendMail->subscriberId = $subscriber->id;
        $sendMail->name = $subscriber->name;
        $sendMail->templateId = $schedule->template->id;

        return $sendMail->sendEmail();
    }

    /**
     * @param string $intervalType
     * @param int $intervalNumber
     * @return string|null
     */
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

    /**
     * @param array $scheduleIds
     * @param array $subscriberUpdates
     * @return void
     */
    private function resultSend(array $scheduleIds, array $subscriberUpdates): void
    {
        if ($subscriberUpdates !== []) {
            $ids = array_keys($subscriberUpdates);
            $caseSql = 'CASE id ';
            $bindings = [];

            foreach ($subscriberUpdates as $id => $ts) {
                $caseSql .= 'WHEN ? THEN ? ';
                $bindings[] = (int) $id;
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

        if ($scheduleIds !== []) {
            ReadySent::whereIn('schedule_id', array_unique($scheduleIds))
                ->update(['success' => 1]);
        }
    }
}
