<?php

namespace App\Models;

use App\Enums\TransactionStatus;
use App\Enums\TransactionType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Transaction extends Model
{
    protected $casts = [
        'type' => TransactionType::class,
        'status' => TransactionStatus::class,
        'amount' => 'decimal:2'
    ];
    protected $fillable = [
        'wallet_id',
        'amount',
        'type',
        'status',
        'reference_type',
        'reference_id'
    ];
    public function wallet(): BelongsTo
    {
        return $this->belongsTo(Wallet::class);
    }
}
