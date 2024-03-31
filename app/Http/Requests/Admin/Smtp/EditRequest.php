<?php

namespace App\Http\Requests\Admin\Smtp;

use Illuminate\Foundation\Http\FormRequest;

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
            'id' => 'required|integer'
        ];
    }
}
