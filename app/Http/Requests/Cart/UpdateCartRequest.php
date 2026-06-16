<?php

namespace App\Http\Requests\Cart;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
class UpdateCartRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Auth::check();
    }

    public function rules(): array
    {
        return [
            'quantity' => 'required|integer|min:1|max:100'
        ];
    }

    public function messages(): array
    {
        return [
            'quantity.required' => 'Quantity required',
            'quantity.min' => 'Quantity must be at least one',
            'quantity.max' => 'Cannot add more than 100'
        ];
    }
}
