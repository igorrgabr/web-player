<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

class LocalPlaylist extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'description',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function playlistTracks(): HasMany
    {
        return $this->hasMany(LocalPlaylistTrack::class)->orderBy('position')->orderBy('id');
    }

    public function addNormalizedTrack(array $normalizedTrack): bool
    {
        $track = Track::upsertFromCatalog($normalizedTrack);

        $alreadyExists = $this->playlistTracks()
            ->where('track_id', $track->id)
            ->exists();

        if ($alreadyExists) {
            return false;
        }

        $nextPosition = ((int) $this->playlistTracks()->max('position')) + 1;

        LocalPlaylistTrack::create([
            'local_playlist_id' => $this->id,
            'track_id' => $track->id,
            'position' => $nextPosition,
            'added_at' => now(),
        ]);

        return true;
    }

    public function removeTrackBySpotifyId(string $spotifyTrackId): void
    {
        DB::transaction(function () use ($spotifyTrackId) {
            $item = $this->playlistTracks()
                ->whereHas('track', fn ($q) => $q->where('spotify_track_id', $spotifyTrackId))
                ->first();

            if (! $item) {
                return;
            }

            $item->delete();
            $this->resequencePositions();
        });
    }

    public function moveTrackPosition(int $fromIndex, string $direction): void
    {
        DB::transaction(function () use ($fromIndex, $direction) {
            $items = $this->playlistTracks()->get()->values();
            $targetIndex = $direction === 'up' ? $fromIndex - 1 : $fromIndex + 1;

            if (! isset($items[$fromIndex]) || ! isset($items[$targetIndex])) {
                return;
            }

            $temp = $items[$fromIndex];
            $items[$fromIndex] = $items[$targetIndex];
            $items[$targetIndex] = $temp;

            foreach ($items as $idx => $entry) {
                $newPos = $idx + 1;
                if ((int) $entry->position !== $newPos) {
                    $entry->update(['position' => $newPos]);
                }
            }
        });
    }

    public function resequencePositions(): void
    {
        $items = $this->playlistTracks()->get()->values();

        foreach ($items as $idx => $entry) {
            $newPos = $idx + 1;
            if ((int) $entry->position !== $newPos) {
                $entry->update(['position' => $newPos]);
            }
        }
    }
}
