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
//            'phone' => [
//                'required', 'string', 'digits:9', Rule::unique('users', 'phone')->ignore($this->route('student')),
//            ],
            'photo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
//            'passport' => [
//                'nullable', 'string', 'regex:/^[A-Z]{2}\d{7}$/', Rule::unique('users', 'passport')->ignore($this->route('student'))
//            ],
            'group_id' => 'required|array',
            'group_id.*' => 'exists:groups,id',
            'parents_name' => 'nullable|string|max:255',
//            'parents_tel' => [
//                'nullable', 'string', 'digits:9', Rule::unique('users', 'parents_tel')->ignore($this->route('student')),
//            ],
            'location' => 'nullable|string|max:255',
            'should_pay' => 'nullable|numeric|min:0',
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
            'phone.regex' => 'The phone number format is invalid.',
            'photo.image' => 'The file must be an image.',
            'photo.mimes' => 'The image must be in one of the following formats: jpeg, png, jpg, gif.',
            'photo.max' => 'The image size must not exceed 2048KB.',
            'group_id.required' => 'Please select at least one group.',
            'group_id.array' => 'The group field must be an array.',
            'group_id.*.exists' => 'One of the selected groups does not exist.',
            'should_pay.numeric' => 'The payment amount must be a number.',
            'should_pay.min' => 'The payment amount must be zero or greater.',
        ];
    }
}
