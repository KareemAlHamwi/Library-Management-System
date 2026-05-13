<?php

namespace App\Http\Requests\User;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUserRequest extends FormRequest
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
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'first_name' => ['sometimes', 'string', 'max:50', 'regex:/^[\p{Arabic}a-zA-Z\s]+$/u'],
            'last_name' => ['sometimes', 'string', 'max:50', 'regex:/^[\p{Arabic}a-zA-Z\s]+$/u'],
            'phone_number' => ['sometimes', 'nullable', 'string', 'min:7', 'max:15', 'regex:/^\+?[0-9\s\-\(\)]+$/'],
            'address' => ['sometimes', 'nullable', 'string', 'min:10', 'max:150'],
            'birthdate' => ['sometimes', 'nullable', 'date', 'before:today', 'after:1900-01-01'],
            'bio' => ['sometimes', 'nullable', 'string', 'min:10', 'max:255'],
            'email' => [
                'sometimes',
                'string',
                'lowercase',
                'email',
                'max:255',
                Rule::unique('users')->ignore($this->user()->id),
            ],
        ];
    }
}
