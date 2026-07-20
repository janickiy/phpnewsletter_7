<?php

namespace App\Console\Commands;

use App\Models\ReadySent;
use App\Repositories\ScheduleRepository;
use App\Repositories\SubscriberRepository;
use App\Services\MailingOptionsResolver;
use App\Services\MailingProgressReporter;
use App\Services\MailSender;
use App\Services\SubscriberSentTimeUpdater;
use Illuminate\Console\Command;
use Illuminate\Contracts\Console\Isolatable;

class SendUnsentEmails extends Command implements Isolatable
{
    protected $signature = 'emails:unsent';

    protected $description = 'Send unsent emails to subscribers';

    public function __construct(
        private readonly ScheduleRepository $scheduleRepository,
        private readonly SubscriberRepository $subscribersRepository,
        private readonly MailSender $mailSender,
        private readonly MailingOptionsResolver $mailingOptionsResolver,
        private readonly MailingProgressReporter $progressReporter,
        private readonly SubscriberSentTimeUpdater $subscriberSentTimeUpdater,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        @set_time_limit(0);

        $this->line('start of mailing ...');

        $mailCountNo = 0;
        $mailCount = 0;

        $schedule = $this->scheduleRepository->getScheduleEvent();
        $options = $this->mailingOptionsResolver->resolve();

        foreach ($schedule ?? [] as $row) {
            if (! $row->template) {
                continue;
            }

            $subscribers = $this->subscribersRepository->getSubscribersUnSent(
                $row->id,
                $options->order,
                $options->limit,
                $options->interval,
            );

            $sentSubscriberIds = [];
            $subscriberUpdates = [];
            $progressBar = $this->progressReporter->start($this->output, count($subscribers ?? []));

            foreach ($subscribers ?? [] as $subscriber) {
                $result = $this->mailSender->sendTemplate($row->template, $subscriber);

                if ($result['result'] === true) {
                    $subscriberUpdates[$subscriber->id] = now()->format('Y-m-d H:i:s');
                    $sentSubscriberIds[] = $subscriber->id;
                    $mailCount++;
                    $status = 'success';
                } else {
                    $mailCountNo++;
                    $status = 'failed';
                }

                $this->progressReporter->advance($this->output, $progressBar, $subscriber->email, $status);

                if ($options->limitReached($mailCount)) {
                    break;
                }
            }

            $this->progressReporter->finish($this->output, $progressBar);
            $this->resultSend($row->id, $sentSubscriberIds, $subscriberUpdates);

            if ($options->limitReached($mailCount)) {
                break;
            }
        }

        $this->line('sent: '.$mailCount);
        $this->line('no sent: '.$mailCountNo);

        return self::SUCCESS;
    }

    private function resultSend(int $scheduleId, array $sentSubscriberIds, array $subscriberUpdates): void
    {
        $this->subscriberSentTimeUpdater->update($subscriberUpdates);

        if ($sentSubscriberIds !== []) {
            ReadySent::query()
                ->where('schedule_id', $scheduleId)
                ->whereIn('subscriber_id', array_unique($sentSubscriberIds))
                ->where('success', 0)
                ->update([
                    'success' => 1,
                    'errorMsg' => null,
                    'updated_at' => now(),
                ]);
        }
    }
}
