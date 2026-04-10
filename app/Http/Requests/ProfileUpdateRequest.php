<?php

namespace App\Http\Requests;

use App\Models\User;
use App\Services\SecuritySanitizationService;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProfileUpdateRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $sanitizer = app(SecuritySanitizationService::class);

        $this->merge([
            'name' => $sanitizer->sanitizePlainText((string) $this->input('name'), 255),
            'email' => $sanitizer->sanitizeEmail((string) $this->input('email'), 255),
        ]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'lowercase',
                'email',
                'max:255',
                Rule::unique(User::class)->ignore($this->user()->id),
            ],
        ];
    }
}
