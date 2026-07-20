<?php

namespace App\Console\Commands;

use App\DTO\ReadySentCreateData;
use App\Models\Logs;
use App\Repositories\ReadySentRepository;
use App\Repositories\ScheduleRepository;
use App\Repositories\SubscriberRepository;
use App\Services\MailingOptionsResolver;
use App\Services\MailingProgressReporter;
use App\Services\MailSender;
use App\Services\SubscriberSentTimeUpdater;
use Illuminate\Console\Command;
use Illuminate\Contracts\Console\Isolatable;

class SendEmails extends Command implements Isolatable
{
    protected $signature = 'emails:send';

    protected $description = 'Send emails to subscribers';

    public function __construct(
        private readonly ScheduleRepository        $scheduleRepository,
        private readonly SubscriberRepository      $subscribersRepository,
        private readonly ReadySentRepository       $readySentRepository,
        private readonly MailSender                $mailSender,
        private readonly MailingOptionsResolver    $mailingOptionsResolver,
        private readonly MailingProgressReporter   $progressReporter,
        private readonly SubscriberSentTimeUpdater $subscriberSentTimeUpdater,
    )
    {
        parent::__construct();
    }

    public function handle(): int
    {
        @set_time_limit(0);

        $mailCountNo = 0;
        $mailCount = 0;

        $this->line('start of mailing ...');

        $log = Logs::query()->create([
            'time' => now(),
        ]);

        $schedule = $this->scheduleRepository->getScheduleEvent();
        $options = $this->mailingOptionsResolver->resolve();

        foreach ($schedule ?? [] as $row) {
            if (!$row->template) {
                continue;
            }

            $subscribers = $this->subscribersRepository->getSubscribersNotReadySent(
                $row->id,
                $options->order,
                $options->limit,
                $options->interval,
            );

            $subscriberUpdates = [];
            $progressBar = $this->progressReporter->start($this->output, count($subscribers ?? []));

            foreach ($subscribers ?? [] as $subscriber) {
                $result = $this->mailSender->sendTemplate($row->template, $subscriber);

                $this->readySentRepository->add(new ReadySentCreateData(
                    subscriberId: $subscriber->id,
                    templateId: $row->template_id,
                    success: $result['result'] === true ? 1 : 0,
                    scheduleId: $row->id,
                    logId: $log->id,
                    email: $subscriber->email,
                    template: $row->template->name,
                    errorMsg: $result['error'] ?? null,
                    readMail: null,
                ));

                if ($result['result'] === true) {
                    $subscriberUpdates[$subscriber->id] = now()->format('Y-m-d H:i:s');
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
            $this->subscriberSentTimeUpdater->update($subscriberUpdates);

            if ($options->limitReached($mailCount)) {
                break;
            }
        }

        $this->line('sent: ' . $mailCount);
        $this->line('no sent: ' . $mailCountNo);

        return self::SUCCESS;
    }
}
