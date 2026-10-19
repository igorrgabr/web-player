<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SpotifyAuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_listener_accessing_via_localhost_is_redirected_to_127_0_0_1_loopback(): void
    {
        $response = $this->get('http://localhost:8000/auth/spotify/redirect?foo=bar');

        $response->assertRedirect('http://127.0.0.1:8000/auth/spotify/redirect?foo=bar');
    }

    public function test_initiating_login_redirects_to_spotify_with_random_state_and_required_scopes(): void
    {
        $response = $this->get('http://127.0.0.1:8000/auth/spotify/redirect');

        $response->assertStatus(302);
        $state = session('spotify_oauth_state');
        $this->assertIsString($state);
        $this->assertGreaterThanOrEqual(32, strlen($state));

        $location = $response->headers->get('Location');
        $this->assertStringStartsWith('https://accounts.spotify.com/authorize?', $location);

        parse_str(parse_url($location, PHP_URL_QUERY), $query);

        $this->assertSame('code', $query['response_type'] ?? null);
        $this->assertSame('test-spotify-client-id', $query['client_id'] ?? null);
        $this->assertSame('http://127.0.0.1:8000/auth/spotify/callback', $query['redirect_uri'] ?? null);
        $this->assertSame($state, $query['state'] ?? null);

        $scopes = explode(' ', $query['scope'] ?? '');
        $this->assertEqualsCanonicalizing([
            'streaming',
            'user-read-email',
            'user-read-private',
            'user-read-playback-state',
            'user-modify-playback-state',
            'user-read-currently-playing',
        ], $scopes);
    }

    public function test_oauth_callback_rejects_missing_or_mismatched_state_with_403(): void
    {
        $this->withSession(['spotify_oauth_state' => 'expected-secret-state'])
            ->get('http://127.0.0.1:8000/auth/spotify/callback?code=auth-code&state=wrong-state')
            ->assertStatus(403);

        $this->withSession(['spotify_oauth_state' => 'expected-secret-state'])
            ->get('http://127.0.0.1:8000/auth/spotify/callback?code=auth-code')
            ->assertStatus(403);
    }

    public function test_valid_premium_callback_authenticates_listener_isolates_accounts_encrypts_tokens_and_supports_logout(): void
    {
        \Illuminate\Support\Facades\Http::fake([
            'https://accounts.spotify.com/api/token' => \Illuminate\Support\Facades\Http::sequence()
                ->push([
                    'access_token' => 'plain-access-token-listener-1',
                    'token_type' => 'Bearer',
                    'scope' => 'streaming user-read-email user-read-private',
                    'expires_in' => 3600,
                    'refresh_token' => 'plain-refresh-token-listener-1',
                ], 200)
                ->push([
                    'access_token' => 'plain-access-token-listener-2',
                    'token_type' => 'Bearer',
                    'scope' => 'streaming user-read-email user-read-private',
                    'expires_in' => 3600,
                    'refresh_token' => 'plain-refresh-token-listener-2',
                ], 200),
            'https://api.spotify.com/v1/me' => \Illuminate\Support\Facades\Http::sequence()
                ->push([
                    'id' => 'spotify-user-1',
                    'display_name' => 'Ouvinte Um',
                    'email' => 'ouvinte1@example.com',
                    'country' => 'BR',
                    'product' => 'premium',
                    'images' => [['url' => 'https://i.scdn.co/image/avatar1']],
                ], 200)
                ->push([
                    'id' => 'spotify-user-2',
                    'display_name' => 'Ouvinte Dois',
                    'email' => 'ouvinte2@example.com',
                    'country' => 'BR',
                    'product' => 'premium',
                    'images' => [],
                ], 200),
        ]);

        $response = $this->withSession(['spotify_oauth_state' => 'valid-state-1'])
            ->get('http://127.0.0.1:8000/auth/spotify/callback?code=code-1&state=valid-state-1');

        $response->assertRedirect(route('player.index'));
        $this->assertAuthenticated();

        $rawRow = \Illuminate\Support\Facades\DB::table('spotify_accounts')
            ->where('spotify_user_id', 'spotify-user-1')
            ->first();

        $this->assertNotNull($rawRow);
        $this->assertNotSame('plain-access-token-listener-1', $rawRow->access_token);
        $this->assertNotSame('plain-refresh-token-listener-1', $rawRow->refresh_token);

        $this->getJson('http://127.0.0.1:8000/player/token')
            ->assertOk()
            ->assertJson([
                'access_token' => 'plain-access-token-listener-1',
                'spotify_user_id' => 'spotify-user-1',
            ]);

        $this->post('http://127.0.0.1:8000/logout')
            ->assertRedirect(route('login'));
        $this->assertGuest();

        $this->withSession(['spotify_oauth_state' => 'valid-state-2'])
            ->get('http://127.0.0.1:8000/auth/spotify/callback?code=code-2&state=valid-state-2')
            ->assertRedirect(route('player.index'));

        $this->getJson('http://127.0.0.1:8000/player/token')
            ->assertOk()
            ->assertJson([
                'access_token' => 'plain-access-token-listener-2',
                'spotify_user_id' => 'spotify-user-2',
            ]);
    }

    public function test_non_premium_spotify_account_receives_clear_warning_and_is_blocked_from_player(): void
    {
        \Illuminate\Support\Facades\Http::fake([
            'https://accounts.spotify.com/api/token' => \Illuminate\Support\Facades\Http::response([
                'access_token' => 'free-access-token',
                'token_type' => 'Bearer',
                'scope' => 'streaming user-read-email user-read-private',
                'expires_in' => 3600,
                'refresh_token' => 'free-refresh-token',
            ], 200),
            'https://api.spotify.com/v1/me' => \Illuminate\Support\Facades\Http::response([
                'id' => 'spotify-free-user',
                'display_name' => 'Free Listener',
                'email' => 'free@example.com',
                'country' => 'BR',
                'product' => 'free',
                'images' => [],
            ], 200),
        ]);

        $response = $this->withSession(['spotify_oauth_state' => 'free-state'])
            ->get('http://127.0.0.1:8000/auth/spotify/callback?code=free-code&state=free-state');

        $response->assertRedirect(route('login'));
        $response->assertSessionHasErrors('spotify');

        $this->followingRedirects()
            ->withSession(['spotify_oauth_state' => 'free-state-2'])
            ->get('http://127.0.0.1:8000/auth/spotify/callback?code=free-code&state=free-state-2')
            ->assertSee('Spotify Premium');

        $this->assertDatabaseCount('users', 0);
        $this->assertDatabaseCount('spotify_accounts', 0);
    }
}
