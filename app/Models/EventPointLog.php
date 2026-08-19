<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EventPointLog extends Model
{
    const UPDATED_AT = null;

    protected $fillable = [
        'event_id',
        'user_id',
        'submission_id',
        'points_awarded',
        'reason',
    ];

    protected static function booted(): void
    {
        static::creating(function ($model) {
            $model->created_at ??= now();
        });
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function submission(): BelongsTo
    {
        return $this->belongsTo(EventSubmission::class, 'submission_id');
    }
}
