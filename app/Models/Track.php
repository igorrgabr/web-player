<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Track extends Model
{
    use HasFactory;

    protected $fillable = [
        'spotify_track_id',
        'spotify_track_uri',
        'title',
        'artist_name',
        'spotify_artist_id',
        'album_name',
        'spotify_album_id',
        'album_cover_url',
        'duration_ms',
    ];

    protected $casts = [
        'duration_ms' => 'integer',
    ];

    public static function upsertFromCatalog(array $normalizedTrack): self
    {
        $spotifyTrackId = (string) ($normalizedTrack['spotify_track_id'] ?? '');

        return static::updateOrCreate(
            ['spotify_track_id' => $spotifyTrackId],
            [
                'spotify_track_uri' => (string) ($normalizedTrack['spotify_track_uri'] ?? ('spotify:track:' . $spotifyTrackId)),
                'title' => (string) ($normalizedTrack['title'] ?? 'Faixa sem título'),
                'artist_name' => (string) ($normalizedTrack['artist_name'] ?? 'Artista desconhecido'),
                'spotify_artist_id' => $normalizedTrack['spotify_artist_id'] ?? null,
                'album_name' => (string) ($normalizedTrack['album_name'] ?? 'Álbum desconhecido'),
                'spotify_album_id' => $normalizedTrack['spotify_album_id'] ?? null,
                'album_cover_url' => $normalizedTrack['album_cover_url'] ?? null,
                'duration_ms' => max(1, (int) ($normalizedTrack['duration_ms'] ?? 1000)),
            ]
        );
    }

    /**
     * @param  array<int, array<string, mixed>>  $normalizedTracks
     * @return array<int, self>
     */
    public static function upsertManyFromCatalog(array $normalizedTracks): array
    {
        $persisted = [];
        foreach (array_values($normalizedTracks) as $index => $trackData) {
            if (! empty($trackData['spotify_track_id'])) {
                $persisted[$index] = static::upsertFromCatalog($trackData);
            }
        }

        return $persisted;
    }
}

