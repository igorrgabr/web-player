<?php

namespace Tests\Feature;

use App\Models\SpotifyAccount;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class PlayerEndpointsTest extends TestCase
{
    use RefreshDatabase;

    private function createAuthenticatedListener(string $suffix = '1'): User
    {
        $user = User::create([
            'name' => 'Ouvinte ' . $suffix,
            'email' => "ouvinte{$suffix}@example.com",
        ]);

        SpotifyAccount::create([
            'user_id' => $user->id,
            'spotify_user_id' => 'spotify-user-' . $suffix,
            'display_name' => 'Ouvinte ' . $suffix,
            'email' => "ouvinte{$suffix}@example.com",
            'product_type' => 'premium',
            'access_token' => 'token-' . $suffix,
            'refresh_token' => 'refresh-' . $suffix,
            'token_expires_at' => now()->addHour(),
            'scopes' => 'streaming user-read-playback-state user-modify-playback-state',
        ]);

        return $user;
    }

    public function test_listener_can_list_playback_devices_and_transfer_active_device_persisting_preference(): void
    {
        Http::fake([
            'https://api.spotify.com/v1/me/player/devices' => Http::response([
                'devices' => [
                    [
                        'id' => 'web-player-device-1',
                        'is_active' => true,
                        'name' => 'Web Player Pessoal',
                        'type' => 'Computer',
                        'volume_percent' => 80,
                    ],
                    [
                        'id' => 'iphone-connect-device-2',
                        'is_active' => false,
                        'name' => 'iPhone de Igor',
                        'type' => 'Smartphone',
                        'volume_percent' => 65,
                    ],
                ],
            ], 200),
            'https://api.spotify.com/v1/me/player' => Http::response(null, 204),
        ]);

        $user = $this->createAuthenticatedListener();

        $this->actingAs($user)
            ->getJson('http://127.0.0.1:8000/player/devices')
            ->assertOk()
            ->assertJsonCount(2, 'devices')
            ->assertJsonPath('devices.0.id', 'web-player-device-1');

        $this->actingAs($user)
            ->postJson('http://127.0.0.1:8000/player/transfer', [
                'device_id' => 'web-player-device-1',
                'play' => false,
            ])
            ->assertOk()
            ->assertJson([
                'device_id' => 'web-player-device-1',
            ]);

        $this->assertSame('web-player-device-1', $user->fresh()->preference->preferred_device_id);

        Http::assertSent(function ($request) {
            return $request->url() === 'https://api.spotify.com/v1/me/player'
                && $request->method() === 'PUT'
                && ($request['device_ids'] ?? null) === ['web-player-device-1']
                && ($request['play'] ?? null) === false;
        });
    }

    public function test_listener_can_start_playback_context_and_control_pause_next_previous_and_seek(): void
    {
        Http::fake([
            'https://api.spotify.com/v1/me/player/play*' => Http::response(null, 204),
            'https://api.spotify.com/v1/me/player/pause*' => Http::response(null, 204),
            'https://api.spotify.com/v1/me/player/next*' => Http::response(null, 204),
            'https://api.spotify.com/v1/me/player/previous*' => Http::response(null, 204),
            'https://api.spotify.com/v1/me/player/seek*' => Http::response(null, 204),
        ]);

        $user = $this->createAuthenticatedListener();

        $uris = [
            'spotify:track:1111111111111111111111',
            'spotify:track:2222222222222222222222',
            'spotify:track:3333333333333333333333',
        ];

        $this->actingAs($user)
            ->postJson('http://127.0.0.1:8000/player/play', [
                'device_id' => 'web-player-device-1',
                'uris' => $uris,
                'offset' => 1,
            ])
            ->assertOk()
            ->assertJson([
                'playing' => true,
                'current_uri' => 'spotify:track:2222222222222222222222',
            ]);

        $this->assertSame(
            'spotify:track:2222222222222222222222',
            $user->fresh()->preference->last_played_track_uri
        );

        Http::assertSent(function ($request) use ($uris) {
            return $request->url() === 'https://api.spotify.com/v1/me/player/play?device_id=web-player-device-1'
                && $request->method() === 'PUT'
                && ($request['uris'] ?? null) === $uris
                && ($request['offset'] ?? null) === ['position' => 1];
        });

        $this->actingAs($user)
            ->postJson('http://127.0.0.1:8000/player/pause', ['device_id' => 'web-player-device-1'])
            ->assertOk();

        $this->actingAs($user)
            ->postJson('http://127.0.0.1:8000/player/next', ['device_id' => 'web-player-device-1'])
            ->assertOk();

        $this->actingAs($user)
            ->postJson('http://127.0.0.1:8000/player/previous', ['device_id' => 'web-player-device-1'])
            ->assertOk();

        $this->actingAs($user)
            ->postJson('http://127.0.0.1:8000/player/seek', [
                'device_id' => 'web-player-device-1',
                'position_ms' => 45000,
            ])
            ->assertOk();

        Http::assertSent(function ($request) {
            return str_starts_with($request->url(), 'https://api.spotify.com/v1/me/player/seek?')
                && str_contains($request->url(), 'position_ms=45000')
                && $request->method() === 'PUT';
        });
    }

    public function test_listener_can_update_playback_preferences_volume_repeat_and_shuffle_isolated_by_listener(): void
    {
        Http::fake([
            'https://api.spotify.com/v1/me/player/volume*' => Http::response(null, 204),
            'https://api.spotify.com/v1/me/player/repeat*' => Http::response(null, 204),
            'https://api.spotify.com/v1/me/player/shuffle*' => Http::response(null, 204),
        ]);

        $listenerOne = $this->createAuthenticatedListener('1');
        $listenerTwo = $this->createAuthenticatedListener('2');

        $this->actingAs($listenerOne)
            ->postJson('http://127.0.0.1:8000/player/preferences', [
                'device_id' => 'web-player-device-1',
                'default_volume' => 65,
                'repeat_mode' => 'context',
                'shuffle_enabled' => true,
            ])
            ->assertOk()
            ->assertJson([
                'default_volume' => 65,
                'repeat_mode' => 'context',
                'shuffle_enabled' => true,
            ]);

        $prefOne = $listenerOne->fresh()->getOrCreatePreferences();
        $prefTwo = $listenerTwo->fresh()->getOrCreatePreferences();

        $this->assertSame(65, $prefOne->default_volume);
        $this->assertSame('context', $prefOne->repeat_mode);
        $this->assertTrue($prefOne->shuffle_enabled);

        $this->assertSame(80, $prefTwo->default_volume);
        $this->assertSame('off', $prefTwo->repeat_mode);
        $this->assertFalse($prefTwo->shuffle_enabled);

        $this->actingAs($listenerOne)
            ->postJson('http://127.0.0.1:8000/player/preferences', [
                'default_volume' => 150,
                'repeat_mode' => 'invalid-mode',
            ])
            ->assertStatus(422);
    }
}
