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
        $date = explode(' - ', $this->date_interval);
        $this->start_date = $date[0];
        $this->end_date = $date[1];

        return [
            'template_id' => 'required|integer',
            'categoryId' => 'required|array',
            'end_date' => 'date_format:d.m.Y H:i|before:start_date',
            'start_date' => 'date_format:d.m.Y H:i|after:tomorrow'
        ];
    }
}
