<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Fine extends Model
{
    const UPDATED_AT = null;

    protected $fillable = [
        'borrow_id',
        'amount',
        'paid_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'paid_at' => 'datetime',
    ];

    public function borrow(): BelongsTo
    {
        return $this->belongsTo(Borrow::class);
    }

    public function isPaid(): bool
    {
        return $this->paid_at !== null;
    }
}
