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

    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'file_name' => $this->file_name,
            'template_id' => $this->template_id,
        ];
    }

}
