<?php

namespace App\Http\Requests\Book;

use Illuminate\Foundation\Http\FormRequest;

class UpdateBookRequest extends FormRequest
{
    public function rules(): array
    {
        $bookId = $this->route('bookId');

        return [
            'title' => ['sometimes', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'cover_image' => ['nullable', 'image', 'max:2048'],
            'publisher' => ['nullable', 'string', 'max:255'],
            'published_date' => ['nullable', 'string', 'max:50'],
            'page_count' => ['nullable', 'integer', 'min:1'],
            'isbn' => ['sometimes', 'string', 'unique:books,isbn,'.$bookId],
            'language' => ['nullable', 'string', 'max:10'],
            'price' => ['nullable', 'integer', 'min:0'],
            'total_copies' => ['sometimes', 'integer', 'min:0'],
            'available_copies' => ['sometimes', 'integer', 'min:0'],
            'total_stock_copies' => ['nullable', 'integer', 'min:0'],
            'available_stock_copies' => ['nullable', 'integer', 'min:0'],
            'author_ids' => ['nullable', 'array'],
            'author_ids.*' => ['integer', 'exists:authors,id'],
            'category_ids' => ['nullable', 'array'],
            'category_ids.*' => ['integer', 'exists:categories,id'],
        ];
    }
}
