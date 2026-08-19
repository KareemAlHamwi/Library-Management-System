<?php

namespace App\Http\Requests\Event;

use Illuminate\Foundation\Http\FormRequest;

class SubmitContentRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'content' => ['required', 'string'],
        ];
    }
}
