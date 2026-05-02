<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Fine extends Model
{
    public function borrow(): BelongsTo
    {
        return $this->belongsTo(Borrow::class);
    }
}
