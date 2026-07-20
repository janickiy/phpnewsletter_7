<?php

namespace App\Services;

use Illuminate\Console\OutputStyle;
use Symfony\Component\Console\Helper\ProgressBar;

class MailingProgressReporter
{
    public function start(OutputStyle $output, int $max): ?ProgressBar
    {
        if ($max <= 0) {
            return null;
        }

        ProgressBar::setFormatDefinition(
            'mailing',
            ' %current%/%max% [%bar%] %percent:3s%% | %message%'
        );

        $progressBar = $output->createProgressBar($max);
        $progressBar->setFormat('mailing');
        $progressBar->setMessage('waiting...');
        $progressBar->start();

        return $progressBar;
    }

    public function advance(
        OutputStyle $output,
        ?ProgressBar $progressBar,
        string $email,
        string $status
    ): void {
        if ($progressBar === null) {
            $output->writeln($email.' - '.$status);

            return;
        }

        $progressBar->setMessage($email.' - '.$status);
        $progressBar->advance();
    }

    public function finish(OutputStyle $output, ?ProgressBar $progressBar): void
    {
        if ($progressBar === null) {
            return;
        }

        $progressBar->finish();
        $output->newLine(2);
    }
}
