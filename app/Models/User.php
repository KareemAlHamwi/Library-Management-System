<?php

namespace App\Models;

use App\Enums\UserRole;
use Illuminate\Auth\Passwords\CanResetPassword as PasswordsCanResetPassword;
use Illuminate\Contracts\Auth\CanResetPassword;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable implements CanResetPassword, MustVerifyEmail
{
    use HasApiTokens, HasFactory, Notifiable, PasswordsCanResetPassword;

    protected $fillable = [
        'first_name',
        'last_name',
        'email',
        'password',
        'avatar',
        'phone_number',
        'address',
        'birthdate',
        'bio',
        'pending_email',
        'total_points',
        'purchase_points',
        'event_points',
        'loyalty_points',
        'role'
    ];

    protected $hidden = [
        'password',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'birthdate' => 'date',
            'password' => 'hashed',
            'role' => UserRole::class,
            'purchase_points' => 'integer',
            'event_points' => 'integer',
            'loyalty_points' => 'integer'
        ];
    }

    public function getEmailForVerification(): string
    {
        return $this->pending_email ?? $this->email;
    }

    protected function avatarUrl(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->avatar
                ? Storage::disk('public')->url($this->avatar)
                : null,
        );
    }

    public function borrows(): HasMany
    {
        return $this->hasMany(Borrow::class);
    }

    public function purchases(): HasMany
    {
        return $this->hasMany(Purchase::class);
    }

    public function rewardTransactions(): HasMany
    {
        return $this->hasMany(RewardTransaction::class);
    }

    public function wallet(): HasOne
    {
        return $this->hasOne(Wallet::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    public function cartItems(): HasMany
    {
        return $this->hasMany(Cart::class);
    }

    public function events(): HasMany
    {
        return $this->hasMany(Event::class);
    }

    public function participations(): HasMany
    {
        return $this->hasMany(EventParticipation::class);
    }

    public function submissions(): HasMany
    {
        return $this->hasMany(EventSubmission::class);
    }

    public function eventPointLogs(): HasMany
    {
        return $this->hasMany(EventPointLog::class);
    }

    public function devices(): HasMany
    {
        return $this->hasMany(UserDevice::class);
    }
}
