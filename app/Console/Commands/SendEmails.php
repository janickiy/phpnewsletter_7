<?php

namespace App\Console\Commands;


use App\DTO\ReadySentCreateData;
use App\Repositories\ReadySentRepository;
use App\Repositories\ScheduleRepository;
use App\Repositories\SubscriberRepository;
use App\Helpers\{SendEmailHelper, SettingsHelper};
use App\Models\Subscribers;
use Illuminate\Console\Command;
use Illuminate\Contracts\Console\Isolatable;
use URL;

class SendEmails extends Command implements Isolatable
{
    /**
     * @param ScheduleRepository $scheduleRepository
     * @param SubscriberRepository $subscribersRepository
     * @param ReadySentRepository $readySentRepository
     */
    public function __construct(
        private ScheduleRepository   $scheduleRepository,
        private SubscriberRepository $subscribersRepository,
        private ReadySentRepository  $readySentRepository,
    )
    {
        parent::__construct();
    }

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'emails:send';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send emails to subscribers';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        @set_time_limit(0);

        $this->line(URL::to('/'));

        $mailCountNo = 0;
        $mailCount = 0;

        $schedule = $this->scheduleRepository->getScheduleEvent();

        foreach ($schedule ?? [] as $row) {
            $order = (int)SettingsHelper::getInstance()->getValueForKey('RANDOM_SEND') === 1 ? 'RAND()' : 'subscribers.id';
            $limit = (int)SettingsHelper::getInstance()->getValueForKey('LIMIT_SEND') === 1 ? SettingsHelper::getInstance()->getValueForKey('LIMIT_NUMBER') : null;

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

            $subscribers = $this->subscribersRepository->getSubscribersNotReadySent($order, $limit, $interval);

            foreach ($subscribers ?? [] as $subscriber) {
                if ((int)SettingsHelper::getInstance()->getValueForKey('sleep') > 0) {
                    sleep((int)SettingsHelper::getInstance()->getValueForKey('sleep'));
                }

                $sendEmail = new SendEmailHelper();
                $sendEmail->body = $row->template->body;
                $sendEmail->subject = $row->template->name;
                $sendEmail->prior = $row->template->prior;
                $sendEmail->email = $subscriber->email;
                $sendEmail->token = $subscriber->token;
                $sendEmail->subscriberId = $subscriber->id;
                $sendEmail->name = $subscriber->name;
                $sendEmail->templateId = $row->template->id;
                $result = $sendEmail->sendEmail();

                if ($result['result'] === true) {
                    $this->readySentRepository->add(new ReadySentCreateData(
                        subscriberId: $subscriber->id,
                        templateId: $row->template_id,
                        success: 1,
                        scheduleId: $row->id,
                        logId: 0,
                        email: $subscriber->email,
                        template: $row->template->name,
                        errorMsg: $result['error'],
                        readMail: null
                    ));

                    Subscribers::where('id', $subscriber->id)->update(['timeSent' => date('Y-m-d H:i:s')]);

                    $mailCount++;
                } else {
                    $this->readySentRepository->add(new ReadySentCreateData(
                        subscriberId: $subscriber->id,
                        templateId: $row->template_id,
                        success: 0,
                        scheduleId: $row->id,
                        logId: 0,
                        email: $subscriber->email,
                        template: $row->template->name,
                        errorMsg: $result['error'],
                        readMail: null
                    ));

                    $mailCountNo++;
                }

                if ((int)SettingsHelper::getInstance()->getValueForKey('LIMIT_SEND') === 1 && (int)SettingsHelper::getInstance()->getValueForKey('LIMIT_NUMBER') === $mailCount) {
                    break;
                }
            }

            if ((int)SettingsHelper::getInstance()->getValueForKey('LIMIT_SEND') === 1 && (int)SettingsHelper::getInstance()->getValueForKey('LIMIT_NUMBER') === $mailCount) {
                break;
            }
        }

        $this->line("sent: " . $mailCount);
        $this->line("no sent: " . $mailCountNo);
    }
}
