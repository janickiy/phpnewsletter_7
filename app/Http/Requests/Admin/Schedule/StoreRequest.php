<?php

namespace App\Http\Requests\Admin\Schedule;

use Illuminate\Foundation\Http\FormRequest;

class StoreRequest extends FormRequest
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
            'templateId' => 'required|numeric',
            'categoryId' => 'required|array',
            'value_from_start_date' => 'required|date|date_format:d.m.Y H:i',
            'value_from_end_date' => 'required|date|date_format:d.m.Y H:i',
        ];
    }
}
