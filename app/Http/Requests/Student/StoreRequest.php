<?php

namespace App\Http\Requests\Student;

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
            'password' => 'nullable|string|min:8|max:16',
            'phone' => ['required', 'digits:9', Rule::unique('users', 'phone')],
            // No ->ignore() here: this is a CREATE form, so there is no existing
            // row to exempt. The shared snippet's `?? $this->user()?->id` fallback
            // exempted the signed-in ADMIN's own row, so an admin who typed their
            // own address passed validation and then hit the DB unique index.
            'email' => ['nullable', 'email', 'max:191', Rule::unique('users', 'email')],
            'parents_tel' => ['nullable'],
            'birth_date' => 'nullable|date',
            'date_born' => 'nullable|date',
            'parents_name' => 'nullable|string|max:255',

            // Ota / ona / vasiy bloklari. Telefon bo'sh bo'lsa bloк butunlay
            // e'tiborsiz qoldiriladi, shuning uchun hammasi nullable.
            'guardians' => ['nullable', 'array'],
            'guardians.*.name' => ['nullable', 'string', 'max:255'],
            'guardians.*.phone' => ['nullable', 'digits:9'],
            'guardians.*.email' => ['nullable', 'email', 'max:191'],
            'location' => 'nullable|string|max:255',
            'photo' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            
            'description' => 'nullable|string',
            'group_id' => 'required|array',
            'group_id.*' => 'exists:groups,id',
            'group_payment' => 'nullable|array',
            'group_payment.*' => 'nullable|numeric|min:0',
        ];
    }

    /**
     * Get the custom messages for validation errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'The name field is required.',
            'phone.required' => 'The phone number is required.',
            'phone.digits' => 'The phone number must be exactly 9 digits.',
            'phone.unique' => 'The phone number has already been taken.',
            'birth_date.date' => 'The birth date must be a valid date.',
            'photo.image' => 'The uploaded file must be an image.',
            'photo.mimes' => 'The image must be in one of the following formats: jpeg, png, jpg, gif, or svg.',
            'photo.max' => 'The image size must not exceed 2MB.',
            'group_id.required' => 'The group field is required.',
            'group_id.array' => 'The group field must be an array.',
            'group_id.*.exists' => 'One of the selected groups does not exist.',
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
