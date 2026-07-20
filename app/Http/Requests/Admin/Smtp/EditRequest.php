<?php

namespace App\Http\Requests\Admin\Smtp;

use App\Models\Smtp;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;

class EditRequest extends SmtpRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return array_merge(parent::rules(), [
            'id' => [
                'required',
                'integer',
                Rule::exists(Smtp::getTableName(), 'id'),
            ],
        ]);
    }

    /**
     * @return array<int, string>
     */
    protected function passwordRules(): array
    {
        return ['nullable', 'string', 'max:4096'];
    }

    protected function connectionPassword(): ?string
    {
        return parent::connectionPassword()
            ?? Smtp::query()->whereKey((int) $this->input('id'))->value('password');
    }
}
