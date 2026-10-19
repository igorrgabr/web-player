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
        $existing = static::where('spotify_track_id', $spotifyTrackId)->first();

        $title = isset($normalizedTrack['title']) && trim((string) $normalizedTrack['title']) !== ''
            ? (string) $normalizedTrack['title']
            : ($existing->title ?? 'Faixa sem título');

        $artistName = isset($normalizedTrack['artist_name']) && trim((string) $normalizedTrack['artist_name']) !== ''
            ? (string) $normalizedTrack['artist_name']
            : ($existing->artist_name ?? 'Artista desconhecido');

        $albumName = isset($normalizedTrack['album_name']) && trim((string) $normalizedTrack['album_name']) !== ''
            ? (string) $normalizedTrack['album_name']
            : ($existing->album_name ?? 'Álbum desconhecido');

        return static::updateOrCreate(
            ['spotify_track_id' => $spotifyTrackId],
            [
                'spotify_track_uri' => (string) ($normalizedTrack['spotify_track_uri'] ?? ($existing->spotify_track_uri ?? ('spotify:track:' . $spotifyTrackId))),
                'title' => $title,
                'artist_name' => $artistName,
                'spotify_artist_id' => $normalizedTrack['spotify_artist_id'] ?? ($existing->spotify_artist_id ?? null),
                'album_name' => $albumName,
                'spotify_album_id' => $normalizedTrack['spotify_album_id'] ?? ($existing->spotify_album_id ?? null),
                'album_cover_url' => $normalizedTrack['album_cover_url'] ?? ($existing->album_cover_url ?? null),
                'duration_ms' => max(1, (int) ($normalizedTrack['duration_ms'] ?? ($existing->duration_ms ?? 1000))),
            ]
        );
    }

    public function toNormalizedArray(): array
    {
        return [
            'spotify_track_id' => $this->spotify_track_id,
            'spotify_track_uri' => $this->spotify_track_uri,
            'title' => $this->title,
            'artist_name' => $this->artist_name,
            'spotify_artist_id' => $this->spotify_artist_id,
            'album_name' => $this->album_name,
            'spotify_album_id' => $this->spotify_album_id,
            'album_cover_url' => $this->album_cover_url,
            'duration_ms' => (int) $this->duration_ms,
        ];
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

