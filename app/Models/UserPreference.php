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
        'last_played_track_uri',
        'extra_settings',
    ];

    protected $casts = [
        'default_volume' => 'integer',
        'shuffle_enabled' => 'boolean',
        'extra_settings' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
