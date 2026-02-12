<?php

namespace App\DTO;

class TemplatesCreateData
{
    public function __construct(
        public readonly string $name,
        public readonly string $body,
        public readonly int    $prior,
    )
    {
    }
}
