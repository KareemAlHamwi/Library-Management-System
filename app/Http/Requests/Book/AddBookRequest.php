<?php

namespace App\Http\Requests\Book;

use Illuminate\Foundation\Http\FormRequest;

class AddBookRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'cover_image' => ['nullable', 'image', 'max:2048'],
            'publisher' => ['nullable', 'string', 'max:255'],
            'published_date' => ['nullable', 'string', 'max:50'],
            'page_count' => ['nullable', 'integer', 'min:1'],
            'isbn' => ['nullable', 'string', 'unique:books,isbn'],
            'language' => ['nullable', 'string', 'max:10'],
            'price' => ['nullable', 'integer', 'min:0'],
            'total_copies' => ['required', 'integer', 'min:0'],
            'available_copies' => ['required', 'integer', 'min:0'],
            'total_stock_copies' => ['nullable', 'integer', 'min:0'],
            'available_stock_copies' => ['nullable', 'integer', 'min:0'],
            'google_volume_id' => ['nullable', 'string', 'unique:books,google_volume_id'],
            'author_ids' => ['nullable', 'array'],
            'author_ids.*' => ['integer', 'exists:authors,id'],
            'category_ids' => ['nullable', 'array'],
            'category_ids.*' => ['integer', 'exists:categories,id'],
        ];
    }
}
