<?php

namespace App\Http\Requests\Category;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCategoryRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name.en' => ['sometimes', 'string', 'max:255'],
            'name.ar' => ['sometimes', 'string', 'max:255'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ];
    }
}
