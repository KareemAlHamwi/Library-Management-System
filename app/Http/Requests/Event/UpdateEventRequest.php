<?php

namespace App\Http\Requests\Event;

use Illuminate\Foundation\Http\FormRequest;

class UpdateEventRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'book_id' => ['sometimes', 'integer', 'exists:books,id'],
            'title' => ['sometimes', 'string', 'max:50'],
            'description' => ['sometimes', 'string', 'max:255'],
            'prompt' => ['sometimes', 'string', 'max:255'],
            'external_link' => ['nullable', 'url'],
            'starts_at' => ['sometimes', 'date'],
            'ends_at' => ['sometimes', 'date', 'after:starts_at'],
        ];
    }
}
