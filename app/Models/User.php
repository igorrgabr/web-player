<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    public function spotifyAccount(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(SpotifyAccount::class);
    }

    public function preference(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(UserPreference::class);
    }

    public function favoriteTracks(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(FavoriteTrack::class)->orderByDesc('favorited_at')->orderByDesc('id');
    }

    public function localPlaylists(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(LocalPlaylist::class)->orderBy('created_at')->orderBy('id');
    }

    public function getOrCreatePreferences(): UserPreference
    {
        return $this->preference()->firstOrCreate(
            ['user_id' => $this->id],
            [
                'default_volume' => 80,
                'repeat_mode' => 'off',
                'shuffle_enabled' => false,
            ]
        );
    }
}
