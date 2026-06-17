<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Cart extends Model
{
    protected $fillable = [
        'user_id',
        'book_id',
        'quantity'
    ];
    // ✅ منع استخدام updated_at إذا لم يكن موجوداً
    public $timestamps = false;

    // ✅ تحديد created_at فقط
    const CREATED_AT = 'created_at';
    const UPDATED_AT = null;

    protected $casts = [
        'quantity' => 'integer',
        'user_id' => 'integer',
        'book_id' => 'integer'
    ];
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function book(): BelongsTo
    {
        return $this->belongsTo(Book::class);
    }
}
