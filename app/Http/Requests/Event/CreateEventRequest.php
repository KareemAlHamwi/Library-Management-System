<?php

namespace App\Http\Requests\Event;

use Illuminate\Foundation\Http\FormRequest;

class CreateEventRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'book_id' => ['required', 'integer', 'exists:books,id'],
            'title' => ['required', 'string', 'max:50'],
            'description' => ['required', 'string', 'max:255'],
            'prompt' => ['required', 'string', 'max:255'],
            'external_link' => ['nullable', 'url'],
            'starts_at' => ['required', 'date', 'after:now'],
            'ends_at' => ['required', 'date', 'after:starts_at'],
        ];
    }
}
