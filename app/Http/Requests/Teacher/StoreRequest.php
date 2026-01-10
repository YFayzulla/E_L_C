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
        ];
    }
}
