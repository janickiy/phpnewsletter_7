<?php

namespace App\Http\Requests\Admin\Smtp;

use App\Helpers\SendEmailHelper;
use App\Models\Smtp;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class EditRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'host' => 'required|max:255',
            'username' => 'required',
            'email' => 'required|email',
            'port' => 'required|numeric',
            'timeout' => 'required|numeric',
            'id' => 'required|integer|exists:' . Smtp::getTableName() . ',id',
        ];
    }

    /**
     * @param Validator $validator
     * @return void
     */
    public function withValidator(Validator $validator)
    {
        if ($validator->fails() === false) {
            $validator->after(function ($validator) {
                if (SendEmailHelper::checkConnection($this->host, $this->email, $this->username, $this->password, $this->port, $this->authentication, $this->secure, $this->timeout) === false) {
                    $validator->errors()->add('connection', __('message.unable_connect_to_smtp'));
                }
            });
        }
    }
}
