<?php

namespace App\DTO;

final class AttachCreateData
{
    public function __construct(
        public readonly string $name,
        public readonly string $file_name,
        public readonly int    $template_id,
    )
    {
    }
}
