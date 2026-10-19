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

    public function playContext(User $user, array $uris, int $offset = 0, ?string $deviceId = null): ?string
    {
        $query = $deviceId ? ['device_id' => $deviceId] : [];
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
            $query,
            empty($payload) ? null : $payload
        );

        $currentUri = $uris[$safeOffset] ?? null;

        if ($currentUri !== null) {
            $user->getOrCreatePreferences()->update([
                'last_played_track_uri' => $currentUri,
                'preferred_device_id' => $deviceId ?? $user->getOrCreatePreferences()->preferred_device_id,
            ]);
        }

        return $currentUri;
    }

    public function pause(User $user, ?string $deviceId = null): void
    {
        $this->client->request(
            $user,
            'PUT',
            '/me/player/pause',
            $deviceId ? ['device_id' => $deviceId] : []
        );
    }

    public function next(User $user, ?string $deviceId = null): void
    {
        $this->client->request(
            $user,
            'POST',
            '/me/player/next',
            $deviceId ? ['device_id' => $deviceId] : []
        );
    }

    public function previous(User $user, ?string $deviceId = null): void
    {
        $this->client->request(
            $user,
            'POST',
            '/me/player/previous',
            $deviceId ? ['device_id' => $deviceId] : []
        );
    }

    public function seek(User $user, int $positionMs, ?string $deviceId = null): void
    {
        $query = ['position_ms' => max(0, $positionMs)];
        if ($deviceId) {
            $query['device_id'] = $deviceId;
        }

        $this->client->request($user, 'PUT', '/me/player/seek', $query);
    }

    public function updatePreferences(User $user, array $attributes, ?string $deviceId = null): UserPreference
    {
        $preference = $user->getOrCreatePreferences();
        $updates = [];

        if (array_key_exists('default_volume', $attributes) && $attributes['default_volume'] !== null) {
            $volume = (int) $attributes['default_volume'];
            $updates['default_volume'] = $volume;

            $query = ['volume_percent' => $volume];
            if ($deviceId) {
                $query['device_id'] = $deviceId;
            }
            $this->client->request($user, 'PUT', '/me/player/volume', $query);
        }

        if (array_key_exists('repeat_mode', $attributes) && $attributes['repeat_mode'] !== null) {
            $repeatMode = (string) $attributes['repeat_mode'];
            $updates['repeat_mode'] = $repeatMode;

            $query = ['state' => $repeatMode];
            if ($deviceId) {
                $query['device_id'] = $deviceId;
            }
            $this->client->request($user, 'PUT', '/me/player/repeat', $query);
        }

        if (array_key_exists('shuffle_enabled', $attributes) && $attributes['shuffle_enabled'] !== null) {
            $shuffle = (bool) $attributes['shuffle_enabled'];
            $updates['shuffle_enabled'] = $shuffle;

            $query = ['state' => $shuffle ? 'true' : 'false'];
            if ($deviceId) {
                $query['device_id'] = $deviceId;
            }
            $this->client->request($user, 'PUT', '/me/player/shuffle', $query);
        }

        if ($deviceId) {
            $updates['preferred_device_id'] = $deviceId;
        }

        if (! empty($updates)) {
            $preference->update($updates);
        }

        return $preference->fresh();
    }
}
