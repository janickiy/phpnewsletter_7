<?php

namespace App\Http\Requests\Admin\Smtp;

use App\Helpers\SendEmailHelper;
use App\Models\Smtp;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;
use Throwable;

abstract class SmtpRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'host' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email:rfc', 'max:255'],
            'password' => $this->passwordRules(),
            'port' => ['required', 'integer', 'between:1,65535'],
            'timeout' => ['required', 'integer', 'between:1,300'],
            'secure' => ['required', Rule::in(Smtp::SECURE_METHODS)],
            'authentication' => ['required', Rule::in(Smtp::AUTHENTICATION_METHODS)],
        ];
    }

    /**
     * Validate the actual SMTP connection only after field validation succeeds.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            try {
                $connected = SendEmailHelper::checkConnection(
                    (string) $this->input('host'),
                    (string) $this->input('email'),
                    (string) $this->input('username'),
                    $this->connectionPassword(),
                    (int) $this->input('port'),
                    $this->connectionAuthentication(),
                    $this->connectionSecurity(),
                    (int) $this->input('timeout'),
                );
            } catch (Throwable) {
                $connected = false;
            }

            if (! $connected) {
                $validator->errors()->add('connection', __('message.unable_connect_to_smtp'));
            }
        });
    }

    /**
     * @return array<int, string>
     */
    abstract protected function passwordRules(): array;

    protected function connectionPassword(): ?string
    {
        $password = $this->input('password');

        return is_string($password) && $password !== '' ? $password : null;
    }

    private function connectionAuthentication(): string
    {
        return match ($this->input('authentication')) {
            Smtp::AUTH_PLAIN => 'PLAIN',
            Smtp::AUTH_CRAM_MD5 => 'CRAM-MD5',
            default => 'LOGIN',
        };
    }

    private function connectionSecurity(): string
    {
        $secure = (string) $this->input('secure');

        return $secure === Smtp::SECURE_NONE ? '' : $secure;
    }
}
