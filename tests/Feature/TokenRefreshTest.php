<?php

namespace Tests\Feature;

use App\Models\SpotifyAccount;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class TokenRefreshTest extends TestCase
{
    use RefreshDatabase;

    public function test_token_endpoint_returns_existing_token_without_http_call_when_more_than_five_minutes_remain(): void
    {
        Http::fake();

        $user = User::create([
            'name' => 'Ouvinte Ativo',
            'email' => 'ativo@example.com',
        ]);

        SpotifyAccount::create([
            'user_id' => $user->id,
            'spotify_user_id' => 'spotify-active',
            'display_name' => 'Ouvinte Ativo',
            'email' => 'ativo@example.com',
            'product_type' => 'premium',
            'access_token' => 'still-valid-token',
            'refresh_token' => 'existing-refresh-token',
            'token_expires_at' => now()->addMinutes(20),
            'scopes' => 'streaming',
        ]);

        $this->actingAs($user)
            ->getJson('http://127.0.0.1:8000/player/token')
            ->assertOk()
            ->assertJson([
                'access_token' => 'still-valid-token',
                'spotify_user_id' => 'spotify-active',
            ]);

        Http::assertNothingSent();
    }

    public function test_token_endpoint_refreshes_expiring_token_and_preserves_refresh_token_when_omitted_by_spotify(): void
    {
        Http::fake([
            'https://accounts.spotify.com/api/token' => Http::response([
                'access_token' => 'newly-refreshed-access-token',
                'token_type' => 'Bearer',
                'expires_in' => 3600,
                'scope' => 'streaming user-read-email user-read-private',
            ], 200),
        ]);

        $user = User::create([
            'name' => 'Ouvinte Expirando',
            'email' => 'expirando@example.com',
        ]);

        $account = SpotifyAccount::create([
            'user_id' => $user->id,
            'spotify_user_id' => 'spotify-expiring',
            'display_name' => 'Ouvinte Expirando',
            'email' => 'expirando@example.com',
            'product_type' => 'premium',
            'access_token' => 'old-expiring-token',
            'refresh_token' => 'original-refresh-token-to-keep',
            'token_expires_at' => now()->addMinutes(3),
            'scopes' => 'streaming',
        ]);

        $this->actingAs($user)
            ->getJson('http://127.0.0.1:8000/player/token')
            ->assertOk()
            ->assertJson([
                'access_token' => 'newly-refreshed-access-token',
                'spotify_user_id' => 'spotify-expiring',
            ]);

        $account->refresh();
        $this->assertSame('newly-refreshed-access-token', $account->access_token);
        $this->assertSame('original-refresh-token-to-keep', $account->refresh_token);
        $this->assertTrue($account->token_expires_at->greaterThan(now()->addMinutes(50)));

        Http::assertSent(function ($request) {
            return $request->url() === 'https://accounts.spotify.com/api/token'
                && ($request['grant_type'] ?? null) === 'refresh_token'
                && ($request['refresh_token'] ?? null) === 'original-refresh-token-to-keep';
        });
    }
}
