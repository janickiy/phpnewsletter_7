<?php

namespace App\Services;

use App\Models\Smtp;

final class SmtpConfigurationResolver
{
    public function resolve(): ?Smtp
    {
        return Smtp::query()
            ->where('active', true)
            ->inRandomOrder()
            ->first();
    }
}
