<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlayHistory extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'track_id',
        'context_type',
        'context_uri',
        'played_at',
    ];

    protected $casts = [
        'played_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function track(): BelongsTo
    {
        return $this->belongsTo(Track::class);
    }

    public static function recordForUser(
        User $user,
        Track $track,
        ?string $contextType = null,
        ?string $contextUri = null
    ): self {
        return static::create([
            'user_id' => $user->id,
            'track_id' => $track->id,
            'context_type' => $contextType,
            'context_uri' => $contextUri,
            'played_at' => now(),
        ]);
    }
}
