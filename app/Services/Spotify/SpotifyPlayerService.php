<?php

namespace App\Services\Spotify;

use App\Models\User;
use App\Models\UserPreference;

class SpotifyPlayerService
{
    private SpotifyHttpClient $client;

    public function __construct(SpotifyHttpClient $client)
    {
        $this->client = $client;
    }

    public function getDevices(User $user): array
    {
        $data = $this->client->request($user, 'GET', '/me/player/devices');

        return $data['devices'] ?? [];
    }

    public function transferPlayback(User $user, string $deviceId, bool $play = false): void
    {
        $this->client->request($user, 'PUT', '/me/player', [], [
            'device_ids' => [$deviceId],
            'play' => $play,
        ]);

        $user->getOrCreatePreferences()->update([
            'preferred_device_id' => $deviceId,
        ]);
    }

    public function playContext(
        User $user,
        array $uris,
        int $offset = 0,
        ?string $deviceId = null,
        ?string $contextType = null,
        ?string $contextId = null,
        ?\App\Models\Track $resolvedTrack = null
    ): ?string {
        $safeOffset = max(0, min($offset, max(0, count($uris) - 1)));

        $payload = [];
        if (! empty($uris)) {
            $payload['uris'] = array_values($uris);
            $payload['offset'] = ['position' => $safeOffset];
        }

        $this->client->request(
            $user,
            'PUT',
            '/me/player/play',
            $this->withDeviceQuery($deviceId),
            empty($payload) ? null : $payload
        );

        $currentUri = $uris[$safeOffset] ?? null;

        if ($currentUri !== null) {
            $preference = $user->getOrCreatePreferences();
            $matchedTrack = $resolvedTrack ?? \App\Models\Track::where('spotify_track_uri', $currentUri)->first();

            $preference->update([
                'last_played_track_id' => $matchedTrack ? $matchedTrack->id : $preference->last_played_track_id,
                'last_played_track_uri' => $currentUri,
                'preferred_device_id' => $deviceId ?? $preference->preferred_device_id,
            ]);

            if ($matchedTrack) {
                \App\Models\PlayHistory::recordForUser(
                    $user,
                    $matchedTrack,
                    $contextType ?? 'context',
                    $contextId
                );
            }
        }

        return $currentUri;
    }

    public function playNormalizedTrackList(
        User $user,
        array $normalizedTracks,
        int $offset = 0,
        ?string $contextType = null,
        ?string $contextId = null
    ): ?\App\Models\Track {
        if (empty($normalizedTracks)) {
            return null;
        }

        $persistedByOriginalIndex = \App\Models\Track::upsertManyFromCatalog($normalizedTracks);
        if (empty($persistedByOriginalIndex)) {
            return null;
        }

        $orderedTracks = [];
        $resolvedOffset = 0;

        foreach ($persistedByOriginalIndex as $originalIndex => $track) {
            if ($originalIndex === $offset) {
                $resolvedOffset = count($orderedTracks);
            }
            $orderedTracks[] = $track;
        }

        $uris = array_map(fn ($t) => $t->spotify_track_uri, $orderedTracks);
        $preference = $user->getOrCreatePreferences();
        $selectedTrack = $orderedTracks[$resolvedOffset] ?? null;

        $this->playContext(
            $user,
            $uris,
            $resolvedOffset,
            $preference->preferred_device_id,
            $contextType,
            $contextId,
            $selectedTrack
        );

        return $selectedTrack;
    }

    public function pause(User $user, ?string $deviceId = null): void
    {
        $this->client->request($user, 'PUT', '/me/player/pause', $this->withDeviceQuery($deviceId));
    }

    public function next(User $user, ?string $deviceId = null): void
    {
        $this->client->request($user, 'POST', '/me/player/next', $this->withDeviceQuery($deviceId));
    }

    public function previous(User $user, ?string $deviceId = null): void
    {
        $this->client->request($user, 'POST', '/me/player/previous', $this->withDeviceQuery($deviceId));
    }

    public function seek(User $user, int $positionMs, ?string $deviceId = null): void
    {
        $this->client->request(
            $user,
            'PUT',
            '/me/player/seek',
            $this->withDeviceQuery($deviceId, ['position_ms' => max(0, $positionMs)])
        );
    }

    public function updatePreferences(User $user, array $attributes, ?string $deviceId = null): UserPreference
    {
        $preference = $user->getOrCreatePreferences();
        $updates = [];

        if (array_key_exists('default_volume', $attributes) && $attributes['default_volume'] !== null) {
            $volume = (int) $attributes['default_volume'];
            $updates['default_volume'] = $volume;
            $this->client->request(
                $user,
                'PUT',
                '/me/player/volume',
                $this->withDeviceQuery($deviceId, ['volume_percent' => $volume])
            );
        }

        if (array_key_exists('repeat_mode', $attributes) && $attributes['repeat_mode'] !== null) {
            $repeatMode = (string) $attributes['repeat_mode'];
            $updates['repeat_mode'] = $repeatMode;
            $this->client->request(
                $user,
                'PUT',
                '/me/player/repeat',
                $this->withDeviceQuery($deviceId, ['state' => $repeatMode])
            );
        }

        if (array_key_exists('shuffle_enabled', $attributes) && $attributes['shuffle_enabled'] !== null) {
            $shuffle = (bool) $attributes['shuffle_enabled'];
            $updates['shuffle_enabled'] = $shuffle;
            $this->client->request(
                $user,
                'PUT',
                '/me/player/shuffle',
                $this->withDeviceQuery($deviceId, ['state' => $shuffle ? 'true' : 'false'])
            );
        }

        if ($deviceId) {
            $updates['preferred_device_id'] = $deviceId;
        }

        if (! empty($updates)) {
            $preference->update($updates);
        }

        return $preference->fresh();
    }

    private function withDeviceQuery(?string $deviceId, array $query = []): array
    {
        if ($deviceId !== null && $deviceId !== '') {
            $query['device_id'] = $deviceId;
        }

        return $query;
    }
}
