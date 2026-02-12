<?php

namespace App\DTO;

final class SmtpCreateData
{
    public function __construct(
        public readonly string  $host,
        public readonly string  $username,
        public readonly string  $email,
        public readonly ?string $password,
        public readonly int     $port,
        public readonly string  $authentication,
        public readonly string  $secure,
        public readonly int     $timeout,
        public readonly int     $active,
    )
    {
    }
}
