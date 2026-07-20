<?php

namespace App\Http\Requests\Admin\Smtp;

class StoreRequest extends SmtpRequest
{
    /**
     * @return array<int, string>
     */
    protected function passwordRules(): array
    {
        return ['required', 'string', 'max:4096'];
    }
}
