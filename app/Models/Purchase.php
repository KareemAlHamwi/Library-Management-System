<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Purchase extends Model
{
    protected $fillable = [
        'user_id',
        'book_id',
        'amount_paid',
        'created_at'
    ];
    // ✅ منع استخدام updated_at
    public $timestamps = false;

    // ✅ تحديد created_at فقط
    const CREATED_AT = 'created_at';
    const UPDATED_AT = null;

    protected $casts = [
        'amount_paid' => 'decimal:2'
    ];
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function book(): BelongsTo
    {
        return $this->belongsTo(Book::class);
    }



    protected static function booted()
    {
        static::creating(function ($purchase) {
            if (empty($purchase->created_at)) {
                $purchase->created_at = now();
            }
        });
    }
}
