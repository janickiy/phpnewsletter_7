<?php

namespace App\Http\Requests;

use App\Enums\AjaxAction;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AjaxActionRequest extends FormRequest
{
    /**
     * Authorize the requested action according to its centralized access policy.
     */
    public function authorize(): bool
    {
        $action = AjaxAction::tryFrom((string) $this->input('action'));

        // Let validation produce a useful 422 response for missing or unknown actions.
        return $action === null || $action->isAllowedFor($this->user());
    }

    /**
     * Validate the shared action discriminator and public locale input.
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'action' => ['required', 'string', Rule::enum(AjaxAction::class)],
            'locale' => [
                'required_if:action,'.AjaxAction::ChangeLanguage->value,
                'string',
                Rule::in(config('app.locales', [])),
            ],
        ];
    }

    /**
     * Return the validated action as a strongly typed value.
     */
    public function ajaxAction(): AjaxAction
    {
        return AjaxAction::from((string) $this->validated('action'));
    }
}
