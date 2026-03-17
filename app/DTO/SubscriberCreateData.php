<?php

namespace App\DTO;

use Carbon\CarbonInterface;

final class SubscriberCreateData
{
    public function __construct(
        public readonly string $email,
        public readonly string $name,
        public readonly int $active,
        public readonly string $token,
        public readonly CarbonInterface|string $timeSent,
        public readonly array $categoryIds = [],
    ) {
    }

    public function toArray(): array
    {
        return [
            'email' => $this->email,
            'name' => $this->name,
            'active' => $this->active,
            'token' => $this->token,
            'timeSent' => $this->timeSent,
        ];
    }
}
