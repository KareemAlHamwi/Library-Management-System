<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Book extends Model
{
    protected $fillable = [
        'google_volume_id',
        'title',
        'description',
        'cover_image',
        'publisher',
        'published_date',
        'page_count',
        'isbn',
        'language',
        'price',
        'total_copies',
        'available_copies',
        'total_stock_copies',
        'available_stock_copies',
    ];

    public function authors(): BelongsToMany
    {
        return $this->belongsToMany(Author::class, 'book_author');
    }

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class, 'book_category');
    }

    public function borrows(): HasMany
    {
        return $this->hasMany(Borrow::class);
    }

    public function purchases(): HasMany
    {
        return $this->hasMany(Purchase::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    public function basketItems(): HasMany
    {
        return $this->hasMany(Cart::class);
    }

    public function events(): HasMany
    {
        return $this->hasMany(Event::class);
    }

    public function favorites(): HasMany
    {
        return $this->hasMany(Favorite::class);
    }
}
