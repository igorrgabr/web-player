<?php

namespace App\Services\Spotify;

use App\Models\User;

class SpotifyCatalogService
{
    public const DEV_MODE_PAGE_LIMIT = 10;

    private SpotifyHttpClient $client;

    public function __construct(SpotifyHttpClient $client)
    {
        $this->client = $client;
    }

    public function search(User $user, string $query, int $offset = 0, string $type = 'track,artist,album'): array
    {
        $trimmed = trim($query);
        if ($trimmed === '') {
            return [
                'tracks' => [],
                'tracks_total' => 0,
                'artists' => [],
                'albums' => [],
            ];
        }

        $data = $this->client->request($user, 'GET', '/search', [
            'q' => $trimmed,
            'type' => $type,
            'limit' => self::DEV_MODE_PAGE_LIMIT,
            'offset' => max(0, $offset),
        ]);

        return [
            'tracks' => array_map([$this, 'normalizeTrack'], $data['tracks']['items'] ?? []),
            'tracks_total' => (int) ($data['tracks']['total'] ?? count($data['tracks']['items'] ?? [])),
            'artists' => array_map([$this, 'normalizeArtist'], $data['artists']['items'] ?? []),
            'albums' => array_map([$this, 'normalizeAlbum'], $data['albums']['items'] ?? []),
        ];
    }

    public function getAlbum(User $user, string $albumId): array
    {
        $rawAlbum = $this->client->request($user, 'GET', '/albums/' . urlencode($albumId));
        $album = $this->normalizeAlbum($rawAlbum);

        $fallbackAlbum = [
            'id' => $rawAlbum['id'] ?? $albumId,
            'name' => $album['name'],
            'images' => $rawAlbum['images'] ?? [],
        ];

        $tracks = array_map(
            fn (array $item) => $this->normalizeTrack($item, $fallbackAlbum),
            $rawAlbum['tracks']['items'] ?? []
        );

        return [
            'album' => $album,
            'tracks' => $tracks,
        ];
    }

    public function getArtistOverview(User $user, string $artistId): array
    {
        $rawArtist = $this->client->request($user, 'GET', '/artists/' . urlencode($artistId));
        $artist = $this->normalizeArtist($rawArtist);

        $searchResults = $this->search(
            $user,
            sprintf('artist:"%s"', $artist['name']),
            0,
            'track'
        );

        $rawAlbums = $this->client->request($user, 'GET', '/artists/' . urlencode($artistId) . '/albums', [
            'limit' => self::DEV_MODE_PAGE_LIMIT,
        ]);

        return [
            'artist' => $artist,
            'tracks' => $searchResults['tracks'],
            'albums' => array_map([$this, 'normalizeAlbum'], $rawAlbums['items'] ?? []),
        ];
    }

    public function normalizeTrack(array $rawTrack, ?array $fallbackAlbum = null): array
    {
        $artists = $rawTrack['artists'] ?? [];
        $primaryArtist = $artists[0] ?? [];
        $artistNames = implode(', ', array_filter(array_map(fn ($a) => $a['name'] ?? '', $artists)));
        $album = $rawTrack['album'] ?? $fallbackAlbum ?? [];
        $spotifyTrackId = (string) ($rawTrack['id'] ?? '');

        return [
            'spotify_track_id' => $spotifyTrackId,
            'spotify_track_uri' => (string) ($rawTrack['uri'] ?? ('spotify:track:' . $spotifyTrackId)),
            'title' => (string) ($rawTrack['name'] ?? 'Faixa sem título'),
            'artist_name' => $artistNames !== '' ? $artistNames : 'Artista desconhecido',
            'spotify_artist_id' => isset($primaryArtist['id']) ? (string) $primaryArtist['id'] : null,
            'album_name' => (string) ($album['name'] ?? 'Álbum desconhecido'),
            'spotify_album_id' => isset($album['id']) ? (string) $album['id'] : null,
            'album_cover_url' => $album['images'][0]['url'] ?? null,
            'duration_ms' => max(1, (int) ($rawTrack['duration_ms'] ?? 1000)),
        ];
    }

    public function normalizeArtist(array $rawArtist): array
    {
        return [
            'spotify_artist_id' => (string) ($rawArtist['id'] ?? ''),
            'name' => (string) ($rawArtist['name'] ?? 'Artista'),
            'image_url' => $rawArtist['images'][0]['url'] ?? null,
            'genres' => array_slice($rawArtist['genres'] ?? [], 0, 4),
        ];
    }

    public function normalizeAlbum(array $rawAlbum): array
    {
        $artists = $rawAlbum['artists'] ?? [];
        $primaryArtist = $artists[0] ?? [];
        $artistNames = implode(', ', array_filter(array_map(fn ($a) => $a['name'] ?? '', $artists)));

        return [
            'spotify_album_id' => (string) ($rawAlbum['id'] ?? ''),
            'name' => (string) ($rawAlbum['name'] ?? 'Álbum'),
            'artist_name' => $artistNames !== '' ? $artistNames : 'Artista desconhecido',
            'spotify_artist_id' => isset($primaryArtist['id']) ? (string) $primaryArtist['id'] : null,
            'cover_url' => $rawAlbum['images'][0]['url'] ?? null,
            'release_year' => isset($rawAlbum['release_date']) ? substr((string) $rawAlbum['release_date'], 0, 4) : null,
        ];
    }

    public static function formatDuration(int $durationMs): string
    {
        $totalSeconds = max(0, intdiv($durationMs, 1000));
        $minutes = intdiv($totalSeconds, 60);
        $seconds = $totalSeconds % 60;

        return sprintf('%d:%02d', $minutes, $seconds);
    }
}
