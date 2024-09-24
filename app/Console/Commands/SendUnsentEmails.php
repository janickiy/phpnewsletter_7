<?php

namespace App\Console\Commands;

use App\Helpers\{SendEmailHelper, SettingsHelper};
use App\Models\{ReadySent, Schedule, Subscribers};
use Illuminate\Console\Command;
use Illuminate\Contracts\Console\Isolatable;

class SendUnsentEmails extends Command implements Isolatable
{
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

        $mailcountno = 0;
        $mailcount = 0;

        $schedule = Schedule::where('event_start', '<=', date('Y-m-d H:i:s'))
            ->where('event_end', '>=', date('Y-m-d H:i:s'))
            ->get();

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

            if ($interval) {
                $subscribers = Subscribers::select(['subscribers.email', 'subscribers.id', 'subscribers.token', 'subscribers.name'])
                    ->join('subscriptions', 'subscribers.id', '=', 'subscriptions.subscriber_id')
                    ->join('schedule_category', function ($join) use ($row) {
                        $join->on('subscriptions.category_id', '=', 'schedule_category.category_id')
                            ->where('schedule_category.schedule_id', $row->id);
                    })
                    ->leftJoin('ready_sent', function ($join) use ($row) {
                        $join->on('subscribers.id', '=', 'ready_sent.subscriber_id')
                            ->where('ready_sent.schedule_id', $row->id)
                            ->where(function ($query) {
                                $query->where('ready_sent.success', 0);
                            });
                    })
                    ->whereNull('ready_sent.subscriber_id')
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
                $subscribers = Subscribers::select(['subscribers.email', 'subscribers.id', 'subscribers.token', 'subscribers.name'])
                    ->join('subscriptions', 'subscribers.id', '=', 'subscriptions.subscriber_id')
                    ->join('schedule_category', function ($join) use ($row) {
                        $join->on('subscriptions.category_id', '=', 'schedule_category.category_id')
                            ->where('schedule_category.schedule_id',  $row->id);
                    })
                    ->leftJoin('ready_sent', function ($join) use ($row) {
                        $join->on('subscribers.id', '=', 'ready_sent.subscriber_id')
                            ->where('ready_sent.schedule_id', $row->id)
                            ->where(function ($query) {
                                $query->where('ready_sent.success', 0);
                            });
                    })
                    ->whereNull('ready_sent.subscriber_id')
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
                    ReadySent::where('schedule_id', $row->id)->update(['success' => 1]);
                    Subscribers::where('id', $subscriber->id)->update(['timeSent' => date('Y-m-d H:i:s')]);

                    $mailcount++;
                } else {
                    $mailcountno++;
                }

                if ((int)SettingsHelper::getInstance()->getValueForKey('LIMIT_SEND') === 1 && (int)SettingsHelper::getInstance()->getValueForKey('LIMIT_NUMBER') === $mailcount) {
                    break;
                }
            }

            if ((int)SettingsHelper::getInstance()->getValueForKey('LIMIT_SEND') === 1 && (int)SettingsHelper::getInstance()->getValueForKey('LIMIT_NUMBER') === $mailcount) {
                break;
            }
        }

        $this->line("sent: " . $mailcount);
        $this->line("no sent: " . $mailcountno);
    }
}
