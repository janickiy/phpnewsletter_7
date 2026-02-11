<?php

namespace App\DTO;

final class SubscriberCreateData
{
    public function __construct(


        public readonly int     $subscriberId,
        public readonly int     $templateId,
        public readonly int     $success,
        public readonly int     $scheduleId,
        public readonly int     $logId,
        public readonly string  $email,
        public readonly string  $template,
        public readonly ?string $errorMsg,
        public readonly ?int    $readMail,
    )
    {
    }
}
