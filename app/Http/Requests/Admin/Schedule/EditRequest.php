<?php

namespace App\Http\Requests\Admin\Schedule;

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
        $date = explode(' - ', $this->date_interval);
        $this->event_start = $date[0];
        $this->event_end = $date[1];

        return [
            'template_id' => 'required|integer',
            'categoryId' => 'required|array',
            'event_end' => 'date_format:d.m.Y H:i|before:event_start',
            'event_start' => 'date_format:d.m.Y H:i|after:tomorrow'
        ];
    }
}
