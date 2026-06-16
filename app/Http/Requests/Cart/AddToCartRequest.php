<?php

namespace App\Http\Requests\Cart;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
class AddToCartRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Auth::check();
    }

    public function rules(): array
    {
        return [
            'book_id' => 'required|exists:books,id',
            'quantity' => 'nullable|integer|min:1|max:100'
        ];
    }

    public function messages(): array
    {
        return [
            'book_id.required' => 'Book must be defined',
            'book_id.exists' => 'Book is not existed',
            'quantity.min' => 'Quantity must be at least one',
            'quantity.max' => 'Cannot add more than 100'
        ];
    }
}
