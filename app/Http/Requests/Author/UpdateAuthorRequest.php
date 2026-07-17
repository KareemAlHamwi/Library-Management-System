<?php

namespace App\Http\Requests\Author;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAuthorRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name.en' => ['sometimes', 'string', 'max:255'],
            'name.ar' => ['sometimes', 'string', 'max:255'],
            'bio.en' => ['nullable', 'string'],
            'bio.ar' => ['nullable', 'string'],
        ];
    }
}
