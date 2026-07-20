<?php

namespace App\Http\Requests\Admin\Settings;

use App\Models\Settings;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Normalize unchecked HTML checkboxes before validation.
     */
    protected function prepareForValidation(): void
    {
        $booleanSettings = [];

        foreach (Settings::BOOLEAN_KEYS as $key) {
            $booleanSettings[$key] = $this->boolean($key);
        }

        $this->merge($booleanSettings);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $rules = [
            'EMAIL' => ['nullable', 'email:rfc', 'max:255'],
            'FROM' => ['nullable', 'string', 'max:255'],
            'RETURN_PATH' => ['nullable', 'email:rfc', 'max:255'],
            'LIST_OWNER' => ['nullable', 'email:rfc', 'max:255'],
            'ORGANIZATION' => ['nullable', 'string', 'max:255'],
            'SUBJECT_TEXT_CONFIRM' => ['nullable', 'string', 'max:255'],
            'TEXT_CONFIRMATION' => ['nullable', 'string', 'max:65535'],
            'UNSUBLINK' => ['nullable', 'string', 'max:65535'],
            'INTERVAL_NUMBER' => ['required', 'integer', 'min:0'],
            'INTERVAL_TYPE' => ['required', Rule::in(['no', 'minute', 'hour', 'day'])],
            'LIMIT_NUMBER' => ['required', 'integer', 'min:1'],
            'SLEEP' => ['required', 'integer', 'min:0', 'max:86400'],
            'DAYS_FOR_REMOVE_SUBSCRIBER' => ['required', 'integer', 'min:1'],
            'PRECEDENCE' => ['required', Rule::in(['no', 'bulk', 'junk', 'list'])],
            'CHARSET' => ['required', 'string', 'max:64'],
            'CONTENT_TYPE' => ['required', Rule::in(['html', 'plain'])],
            'HOW_TO_SEND' => ['required', Rule::in(['php', 'smtp', 'sendmail'])],
            'SENDMAIL_PATH' => ['nullable', 'string', 'max:1024'],
            'URL' => ['nullable', 'url', 'max:2048'],
            'header_name' => ['sometimes', 'array', 'max:50'],
            'header_name.*' => [
                'nullable',
                'string',
                'max:255',
                'regex:/^[A-Za-z][A-Za-z0-9-]*$/',
            ],
            'header_value' => ['sometimes', 'array', 'max:50'],
            'header_value.*' => [
                'nullable',
                'string',
                'max:255',
                'not_regex:/[\r\n]/',
            ],
        ];

        foreach (Settings::BOOLEAN_KEYS as $key) {
            $rules[$key] = ['required', 'boolean'];
        }

        return $rules;
    }
}
