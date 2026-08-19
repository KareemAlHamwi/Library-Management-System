<?php

namespace App\Http\Requests\Event;

use Illuminate\Foundation\Http\FormRequest;

class AwardPointsRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'points_awarded' => ['required', 'integer', 'min:1'],
            'reason' => ['required', 'string', 'max:255'],
        ];
    }
}
