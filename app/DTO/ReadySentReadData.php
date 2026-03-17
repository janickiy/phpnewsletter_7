<?php

namespace App\DTO;

class ReadySentReadData
{
    public function __construct(
        public readonly int $subscriberId,
        public readonly int $templateId,
        public readonly int $readMail = 1,
    ) {
    }
}
