<?php

namespace App\Http\Requests\Book;

use App\Services\Book\GoogleBooksService;
use App\Services\Book\OrderBy;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SearchGoogleBooksRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'q' => ['required', 'string', 'min:2', 'max:255'],
            'lang' => ['sometimes', 'nullable', 'string', Rule::in(array_keys(GoogleBooksService::getSupportedLanguages()))],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:40'],
            'order_by' => ['sometimes', 'string', Rule::in(array_column(OrderBy::cases(), 'value'))],
        ];
    }

    public function messages(): array
    {
        return [
            'q.required' => 'A search query is required.',
            'q.min' => 'The search query must be at least 2 characters.',
            'q.max' => 'The search query must not exceed 255 characters.',
        ];
    }
}
