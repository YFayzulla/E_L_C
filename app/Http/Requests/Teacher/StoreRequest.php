<?php

namespace App\Http\Requests\Teacher;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'passport' => 'nullable|string|regex:/^[A-Z]{2}\d{7}$/|unique:users,passport|max:9',
            'date_born' => 'nullable|date',
            'location' => 'nullable|string|max:255',
            'phone' => ['required', 'digits:9', Rule::unique('users', 'phone')],
            // No ->ignore() here: this is a CREATE form, so there is no existing
            // row to exempt. The shared snippet's `?? $this->user()?->id` fallback
            // exempted the signed-in ADMIN's own row, so an admin who typed their
            // own address passed validation and then hit the DB unique index —
            // surfacing as the misleading "telefon raqami yoki passport" error.
            'email' => ['nullable', 'email', 'max:191', Rule::unique('users', 'email')],
            'photo' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:10048',
            'percent' => 'nullable|integer|min:0|max:100',
        ];
    }

    /**
     * Get the validation messages that apply to the request.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'The name field is required.',
            'passport.regex' => 'The passport must consist of 2 uppercase English letters followed by 7 digits.',
            'passport.unique' => 'This passport number already exists.',
            'phone.required' => 'The phone number is required.',
            'phone.digits' => 'The phone number must be exactly 9 digits.',
            'phone.unique' => 'The phone number has already been taken.',
            'photo.image' => 'The uploaded file must be an image.',
            'photo.mimes' => 'The image must be in one of the following formats: jpeg, png, jpg, gif, or svg.',
            'photo.max' => 'The image size must not exceed 20MB.',
            'percent.integer' => 'The percent must be an integer.',
            'percent.min' => 'The percent must be at least 0.',
            'percent.max' => 'The percent must not exceed 100.',
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
            'email' => 'pochta manzili',
        ];
    }
}
