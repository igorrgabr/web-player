<?php

namespace App\Services\Spotify;

use App\Models\SpotifyAccount;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class SpotifyAuthService
{
    public function buildAuthorizationUrl(string $state): string
    {
        $query = http_build_query([
            'response_type' => 'code',
            'client_id' => config('spotify.client_id'),
            'redirect_uri' => config('spotify.redirect_uri'),
            'scope' => implode(' ', config('spotify.scopes', [])),
            'state' => $state,
        ], '', '&', PHP_QUERY_RFC3986);

        return rtrim(config('spotify.auth_url'), '?') . '?' . $query;
    }

    public function authenticateWithCode(string $code): SpotifyAccount
    {
        $tokenResponse = Http::asForm()
            ->withBasicAuth((string) config('spotify.client_id'), (string) config('spotify.client_secret'))
            ->post((string) config('spotify.token_url'), [
                'grant_type' => 'authorization_code',
                'code' => $code,
                'redirect_uri' => config('spotify.redirect_uri'),
            ]);

        if (! $tokenResponse->successful()) {
            throw new RuntimeException('Falha ao trocar o código de autorização com o Spotify.');
        }

        $tokenData = $tokenResponse->json();
        $accessToken = (string) ($tokenData['access_token'] ?? '');

        $profileResponse = Http::withToken($accessToken)
            ->get(rtrim((string) config('spotify.api_base_url'), '/') . '/me');

        if (! $profileResponse->successful()) {
            throw new RuntimeException('Falha ao consultar o perfil da Conta Spotify.');
        }

        $profile = $profileResponse->json();
        $spotifyUserId = (string) ($profile['id'] ?? '');
        $email = $profile['email'] ?? ($spotifyUserId . '@spotify.local');
        $displayName = (string) ($profile['display_name'] ?: $spotifyUserId);
        $avatarUrl = $profile['images'][0]['url'] ?? null;
        $expiresIn = (int) ($tokenData['expires_in'] ?? 3600);

        return DB::transaction(function () use (
            $spotifyUserId,
            $email,
            $displayName,
            $avatarUrl,
            $profile,
            $tokenData,
            $accessToken,
            $expiresIn
        ) {
            $existingAccount = SpotifyAccount::where('spotify_user_id', $spotifyUserId)->first();

            if ($existingAccount) {
                $user = $existingAccount->user;
                $user->update([
                    'name' => $displayName,
                    'email' => $email,
                ]);
            } else {
                $user = User::updateOrCreate(
                    ['email' => $email],
                    ['name' => $displayName]
                );
            }

            return SpotifyAccount::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'spotify_user_id' => $spotifyUserId,
                    'display_name' => $displayName,
                    'email' => $email,
                    'avatar_url' => $avatarUrl,
                    'country' => $profile['country'] ?? null,
                    'product_type' => (string) ($profile['product'] ?? 'free'),
                    'access_token' => $accessToken,
                    'refresh_token' => (string) ($tokenData['refresh_token'] ?? ($existingAccount->refresh_token ?? '')),
                    'token_expires_at' => now()->addSeconds($expiresIn),
                    'scopes' => $tokenData['scope'] ?? implode(' ', config('spotify.scopes', [])),
                ]
            );
        });
    }

    public function ensureValidToken(SpotifyAccount $account): SpotifyAccount
    {
        if ($account->token_expires_at && $account->token_expires_at->greaterThan(now()->addMinutes(5))) {
            return $account;
        }

        return \Illuminate\Support\Facades\Cache::lock('spotify-token-refresh-' . $account->user_id, 10)
            ->block(5, function () use ($account) {
                $account->refresh();

                if ($account->token_expires_at && $account->token_expires_at->greaterThan(now()->addMinutes(5))) {
                    return $account;
                }

                $response = Http::asForm()
                    ->withBasicAuth((string) config('spotify.client_id'), (string) config('spotify.client_secret'))
                    ->post((string) config('spotify.token_url'), [
                        'grant_type' => 'refresh_token',
                        'refresh_token' => $account->refresh_token,
                    ]);

                if (! $response->successful()) {
                    throw new RuntimeException('Falha ao renovar o token da Conta Spotify.');
                }

                $data = $response->json();
                $expiresIn = (int) ($data['expires_in'] ?? 3600);

                $account->update([
                    'access_token' => (string) $data['access_token'],
                    'refresh_token' => ! empty($data['refresh_token'])
                        ? (string) $data['refresh_token']
                        : $account->refresh_token,
                    'token_expires_at' => now()->addSeconds($expiresIn),
                    'scopes' => $data['scope'] ?? $account->scopes,
                ]);

                return $account->fresh();
            });
    }
}
