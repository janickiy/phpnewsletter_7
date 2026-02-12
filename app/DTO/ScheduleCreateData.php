<?php

namespace App\DTO;

final class ScheduleCreateData
{
    public function __construct(
        public readonly string $event_name,
        public readonly string $event_start,
        public readonly string $event_end,
        public readonly int    $templateId,
    )
    {
    }
}
