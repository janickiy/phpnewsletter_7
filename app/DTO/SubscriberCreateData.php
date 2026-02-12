<?php

namespace App\DTO;

final class SubscriberCreateData
{
    public function __construct(
        public readonly string  $email,
        public readonly int     $active,
        public readonly string  $token,
        public readonly string  $timeSent,
        public readonly ?string $name,
    )
    {
    }
}
