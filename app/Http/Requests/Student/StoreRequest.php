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
            'phone' => ['required',  'digits:9',Rule::unique('users', 'phone')],
            'parents_tel' => ['nullable'  , 'digits:9'],
//            'passport' => ['nullable', 'string', 'regex:/^[A-Z]{2}\d{7}$/', Rule::unique('users', 'passport')],
            'date_born' => 'nullable|date',
            'location' => 'nullable|string|max:255',
            'photo' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'percent' => 'nullable|integer|min:0|max:100',
            'group_id' => 'required|array',
            'group_id.*' => 'exists:groups,id',
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
            'passport.regex' => 'The passport must consist of 2 uppercase English letters followed by 7 digits.',
            'passport.unique' => 'This passport number already exists.',
            'photo.image' => 'The uploaded file must be an image.',
            'photo.mimes' => 'The image must be in one of the following formats: jpeg, png, jpg, gif, or svg.',
            'photo.max' => 'The image size must not exceed 2MB.',
            'percent.integer' => 'The percent must be an integer.',
            'percent.min' => 'The percent must be at least 0.',
            'percent.max' => 'The percent must not exceed 100.',
            'group_id.required' => 'The group field is required.',
            'group_id.array' => 'The group field must be an array.',
            'group_id.*.exists' => 'One of the selected groups does not exist.',
        ];
    }
}
