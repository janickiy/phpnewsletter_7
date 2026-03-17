<?php

namespace App\DTO;

use Carbon\CarbonInterface;

class RedirectCreateData
{
    public function __construct(
        public readonly string $url,
        public readonly CarbonInterface|string $time,
        public readonly string $email,
    ) {
    }

    public function toArray(): array
    {
        return [
            'url' => $this->url,
            'time' => $this->time,
            'email' => $this->email,
        ];
    }
}
