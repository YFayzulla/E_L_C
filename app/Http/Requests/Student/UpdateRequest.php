<?php

namespace App\Http\Requests\Student;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateRequest extends FormRequest
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
            'phone' => [
                'required', 'digits:9', Rule::unique('users', 'phone')->ignore($this->route('student')),
            ],
            'email' => ['nullable', 'email', 'max:191',
                Rule::unique('users', 'email')->ignore($this->route('student') ?? $this->route('teacher') ?? $this->user()?->id)],
            'photo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'birth_date' => [
                'nullable', 'date'
            ],
            'date_born' => [
                'nullable', 'date'
            ],
            'group_id' => 'required|array',
            'group_id.*' => 'exists:groups,id',
            'group_payment' => 'nullable|array',
            'group_payment.*' => 'nullable|numeric|min:0',
            'parents_name' => 'nullable|string|max:255',
            'parents_tel' => [
                'nullable',
            ],

            // Ota / ona / vasiy bloklari. Telefon bo'sh bo'lsa bloк butunlay
            // e'tiborsiz qoldiriladi, shuning uchun hammasi nullable.
            'guardians' => ['nullable', 'array'],
            'guardians.*.name' => ['nullable', 'string', 'max:255'],
            'guardians.*.phone' => ['nullable', 'digits:9'],
            'guardians.*.email' => ['nullable', 'email', 'max:191'],

            'location' => 'nullable|string|max:255',
            
            'description' => 'nullable|string',
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, mixed>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Please enter the name.',
            'phone.required' => 'Please enter the phone number.',
            'phone.digits' => 'The phone number must be exactly 9 digits.',
            'phone.unique' => 'The phone number has already been taken.',
            'passport.regex' => 'The passport format is invalid.',
            'passport.unique' => 'This passport number already exists.',
            'photo.image' => 'The file must be an image.',
            'photo.mimes' => 'The image must be in one of the following formats: jpeg, png, jpg, gif.',
            'photo.max' => 'The image size must not exceed 2048KB.',
            'group_id.required' => 'Please select at least one group.',
            'group_id.array' => 'The group field must be an array.',
            'group_id.*.exists' => 'One of the selected groups does not exist.',
            'should_pay.numeric' => 'The payment amount must be a number.',
            'should_pay.min' => 'The payment amount must be zero or greater.',
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
