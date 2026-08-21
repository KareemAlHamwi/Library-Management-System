<?php

namespace App\Http\Requests\Borrow;

use Illuminate\Foundation\Http\FormRequest;

class RequestBorrowRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'due_date' => ['required', 'date', 'after:today'],
        ];
    }
}
