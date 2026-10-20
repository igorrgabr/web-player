<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserPreference extends Model
{
    use HasFactory;

    public const ALLOWED_REPEAT_MODES = ['off', 'context', 'track'];

    protected $fillable = [
        'user_id',
        'default_volume',
        'repeat_mode',
        'shuffle_enabled',
        'preferred_device_id',
        'last_played_track_id',
        'last_played_track_uri',
    ];

    protected $casts = [
        'default_volume' => 'integer',
        'shuffle_enabled' => 'boolean',
        'last_played_track_id' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function lastPlayedTrack(): BelongsTo
    {
        return $this->belongsTo(Track::class, 'last_played_track_id');
    }
}
