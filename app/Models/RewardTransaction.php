<?php

namespace App\Models;

use App\Enums\RewardType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RewardTransaction extends Model
{
    protected $fillable = [
        'user_id',
        'points',
        'type',
        'reason',
        'reference_id',
        'created_at'
    ];
        // ✅ منع استخدام updated_at
    public $timestamps = false;

    // ✅ تحديد created_at فقط
    const CREATED_AT = 'created_at';
    const UPDATED_AT = null;

    protected $casts = [
        'type' => RewardType::class,
        'points' => 'integer'
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
    protected static function booted()
    {
        static::creating(function ($reward) {
            if (empty($reward->created_at)) {
                $reward->created_at = now();
            }
        });
    }
}
