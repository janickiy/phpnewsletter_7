<?php

namespace App\Console\Commands;


use App\Repositories\ScheduleRepository;
use App\Repositories\SubscriberRepository;
use App\Helpers\{SendEmailHelper, SettingsHelper};
use App\Models\{ReadySent, Subscribers};
use Illuminate\Console\Command;
use Illuminate\Contracts\Console\Isolatable;
use Illuminate\Support\Facades\DB;

class SendUnsentEmails extends Command implements Isolatable
{
    public function __construct(
        private ScheduleRepository   $scheduleRepository,
        private SubscriberRepository $subscribersRepository,
    )
    {
        parent::__construct();
    }

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'emails:unsent';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send unsent emails to subscribers';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        @set_time_limit(0);

        $this->line('start unsent sending emails');

        $mailCountNo = 0;
        $mailCount = 0;

        $schedule = $this->scheduleRepository->getScheduleEvent();

        foreach ($schedule ?? [] as $row) {
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

            $subscribers = $this->subscribersRepository->getSubscribersUnSent($row->id, $order, $limit, $interval);

            $scheduleIds = [];
            $subscriberUpdates = [];

            foreach ($subscribers ?? [] as $subscriber) {
                if ((int)SettingsHelper::getInstance()->getValueForKey('sleep') > 0) {
                    sleep((int)SettingsHelper::getInstance()->getValueForKey('sleep'));
                }

                $sendMail = new SendEmailHelper();
                $sendMail->body = $row->template->body;
                $sendMail->subject = $row->template->name;
                $sendMail->prior = $row->template->prior;
                $sendMail->email = $subscriber->email;
                $sendMail->token = $subscriber->token;
                $sendMail->subscriberId = $subscriber->id;
                $sendMail->name = $subscriber->name;
                $result = $sendMail->sendEmail();

                if ($result['result'] === true) {
                    $subscriberUpdates[$subscriber->id] = now()->format('Y-m-d H:i:s');
                    $scheduleIds[] = $row->id;
                    $mailCount++;
                } else {
                    $mailCountNo++;
                }

                if ((int)SettingsHelper::getInstance()->getValueForKey('LIMIT_SEND') === 1 && (int)SettingsHelper::getInstance()->getValueForKey('LIMIT_NUMBER') === $mailCount) {
                    $this->resultSend($scheduleIds, $subscriberUpdates);
                    break;
                }
            }

            $this->resultSend($scheduleIds, $subscriberUpdates);

            if ((int)SettingsHelper::getInstance()->getValueForKey('LIMIT_SEND') === 1 && (int)SettingsHelper::getInstance()->getValueForKey('LIMIT_NUMBER') === $mailCount) {
                break;
            }
        }

        $this->line("sent: " . $mailCount);
        $this->line("no sent: " . $mailCountNo);
    }

    /**
     * @param array $scheduleIds
     * @param array $subscriberUpdates
     * @return void
     */
    private function resultSend(array $scheduleIds, array $subscriberUpdates): void
    {
        if (!empty($subscriberUpdates)) {
            $ids = array_keys($subscriberUpdates);

            $caseSql  = "CASE id ";
            $bindings = [];

            foreach ($subscriberUpdates as $id => $ts) {
                $caseSql .= "WHEN ? THEN ? ";
                $bindings[] = (int)$id;
                $bindings[] = $ts;
            }
            $caseSql .= "END";

            $inSql = implode(',', array_fill(0, count($ids), '?'));
            $bindings = array_merge($bindings, $ids);

            DB::statement(
                "UPDATE " . Subscribers::getTableName() . " SET timeSent = {$caseSql} WHERE id IN ({$inSql})",
                $bindings
            );
        }

        ReadySent::whereIn('schedule_id', $scheduleIds)->update(['success' => 1]);
    }
}
