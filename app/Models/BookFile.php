<?php

namespace App\Models;

use App\Enums\FileType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BookFile extends Model
{
    protected $casts = [
        'file_type' => FileType::class,
    ];

    public function book(): BelongsTo
    {
        return $this->belongsTo(Book::class);
    }

    public function offlineSaves(): HasMany
    {
        return $this->hasMany(OfflineSave::class, 'file_id');
    }
}
