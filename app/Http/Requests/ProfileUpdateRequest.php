<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProfileUpdateRequest extends FormRequest
{
    /**
     * Normalise the address before validation.
     *
     * An empty input must reach the model as NULL, not as '' — `users.email`
     * carries a unique index and two blank strings would collide.
     */
    protected function prepareForValidation(): void
    {
        if ($this->has('email')) {
            $this->merge([
                'email' => $this->filled('email') ? trim((string) $this->input('email')) : null,
            ]);
        }
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\Rule|array|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['string', 'max:255'],
            'phone' => ['string', 'max:255'],
            'email' => ['nullable', 'email', 'max:191',
                Rule::unique('users', 'email')->ignore($this->route('student') ?? $this->route('teacher') ?? $this->user()?->id)],
        ];
    }

    /**
     * Uzbek validation messages.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'email.email' => 'Pochta manzili noto‘g‘ri kiritilgan.',
            'email.max' => 'Pochta manzili 191 belgidan oshmasligi kerak.',
            'email.unique' => 'Bu pochta manzili boshqa hisobga biriktirilgan.',
        ];
    }

    /**
     * Uzbek attribute names used in the default validation messages.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'name' => 'ism',
            'phone' => 'telefon raqami',
            'email' => 'pochta manzili',
        ];
    }
}
