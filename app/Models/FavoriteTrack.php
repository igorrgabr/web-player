<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FavoriteTrack extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'track_id',
        'favorited_at',
    ];

    protected $casts = [
        'favorited_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function track(): BelongsTo
    {
        return $this->belongsTo(Track::class);
    }

    public static function toggleForUser(User $user, array $normalizedTrack): bool
    {
        $track = Track::upsertFromCatalog($normalizedTrack);

        $existing = static::where('user_id', $user->id)
            ->where('track_id', $track->id)
            ->first();

        if ($existing) {
            $existing->delete();

            return false;
        }

        static::create([
            'user_id' => $user->id,
            'track_id' => $track->id,
            'favorited_at' => now(),
        ]);

        return true;
    }

    public static function isFavoritedByUser(User $user, ?string $spotifyTrackId = null, ?string $spotifyTrackUri = null): bool
    {
        return static::where('user_id', $user->id)
            ->whereHas('track', function ($query) use ($spotifyTrackId, $spotifyTrackUri) {
                if ($spotifyTrackId) {
                    $query->where('spotify_track_id', $spotifyTrackId);
                } elseif ($spotifyTrackUri) {
                    $query->where('spotify_track_uri', $spotifyTrackUri);
                } else {
                    $query->whereRaw('1 = 0');
                }
            })
            ->exists();
    }
}
