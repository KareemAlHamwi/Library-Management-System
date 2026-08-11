<?php

namespace App\Http\Requests\Wallet;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class TopUpRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;///  return auth()->check();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'amount' => 'required|numeric|min:0.01',
            'user_id' => 'required|exists:users,id',
            'payment_method' => 'nullable|string'
        ];
    }
    public function messages(): array
    {
        return [
            'amount.required' => 'The amount is required.',
            'amount.min' => 'The amount must be greater than 0',
            'user_id.required' => 'User is required',
            'user_id.exists' => 'User not found'
        ];
    }
}
