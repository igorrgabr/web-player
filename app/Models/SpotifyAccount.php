<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SpotifyAccount extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'spotify_user_id',
        'display_name',
        'email',
        'avatar_url',
        'country',
        'product_type',
        'access_token',
        'refresh_token',
        'token_expires_at',
        'scopes',
    ];

    protected $hidden = [
        'access_token',
        'refresh_token',
    ];

    protected $casts = [
        'access_token' => 'encrypted',
        'refresh_token' => 'encrypted',
        'token_expires_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isPremium(): bool
    {
        return strtolower((string) $this->product_type) === 'premium';
    }

    public function hasValidToken(int $bufferMinutes = 5): bool
    {
        return $this->token_expires_at !== null
            && $this->token_expires_at->greaterThan(now()->addMinutes($bufferMinutes));
    }
}

