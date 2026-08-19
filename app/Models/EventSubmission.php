<?php

namespace App\Models;

use App\Enums\SubmissionStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EventSubmission extends Model
{
    protected $fillable = [
        'event_id',
        'user_id',
        'content',
        'status',
        'reviewed_at',
    ];

    protected $casts = [
        'status' => SubmissionStatus::class,
        'reviewed_at' => 'datetime',
    ];

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function eventPointLogs(): HasMany
    {
        return $this->hasMany(EventPointLog::class, 'submission_id');
    }
}
