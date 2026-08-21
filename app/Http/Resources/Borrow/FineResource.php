<?php

namespace App\Http\Resources\Borrow;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FineResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'amount' => $this->amount,
            'is_paid' => $this->isPaid(),
            'paid_at' => $this->paid_at?->toDateTimeString(),
            'created_at' => $this->created_at->toDateTimeString(),
            'borrow' => $this->whenLoaded('borrow', fn () => [
                'id' => $this->borrow->id,
                'book' => $this->whenLoaded('borrow.book', fn () => [
                    'id' => $this->borrow->book->id,
                    'title' => $this->borrow->book->title,
                ]),
            ]),
        ];
    }
}
