<?php

namespace App\Services\Spotify;

use App\Exceptions\SpotifyPremiumRequiredException;
use App\Models\SpotifyAccount;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
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
        $tokenData = $this->requestToken([
            'grant_type' => 'authorization_code',
            'code' => $code,
            'redirect_uri' => config('spotify.redirect_uri'),
        ], 'Falha ao trocar o código de autorização com o Spotify.');

        $accessToken = (string) ($tokenData['access_token'] ?? '');

        $accountResponse = Http::withToken($accessToken)
            ->get(rtrim((string) config('spotify.api_base_url'), '/') . '/me');

        if (! $accountResponse->successful()) {
            throw new RuntimeException('Falha ao consultar os dados da Conta Spotify.');
        }

        $accountData = $accountResponse->json();
        $productType = (string) ($accountData['product'] ?? 'free');

        if (strtolower($productType) !== 'premium') {
            throw new SpotifyPremiumRequiredException();
        }

        $spotifyUserId = (string) ($accountData['id'] ?? '');
        $email = $accountData['email'] ?? ($spotifyUserId . '@spotify.local');
        $displayName = (string) ($accountData['display_name'] ?: $spotifyUserId);
        $avatarUrl = $accountData['images'][0]['url'] ?? null;
        $expiresIn = (int) ($tokenData['expires_in'] ?? 3600);

        return DB::transaction(function () use (
            $spotifyUserId,
            $email,
            $displayName,
            $avatarUrl,
            $accountData,
            $productType,
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
                $userEmail = User::where('email', $email)->exists()
                    ? $spotifyUserId . '@spotify.local'
                    : $email;

                $user = User::create([
                    'name' => $displayName,
                    'email' => $userEmail,
                ]);
            }

            return SpotifyAccount::updateOrCreate(
                ['spotify_user_id' => $spotifyUserId],
                [
                    'user_id' => $user->id,
                    'display_name' => $displayName,
                    'email' => $email,
                    'avatar_url' => $avatarUrl,
                    'country' => $accountData['country'] ?? null,
                    'product_type' => $productType,
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
        if ($account->hasValidToken()) {
            return $account;
        }

        return Cache::lock('spotify-token-refresh-' . $account->user_id, 10)
            ->block(5, function () use ($account) {
                $account->refresh();

                if ($account->hasValidToken()) {
                    return $account;
                }

                $data = $this->requestToken([
                    'grant_type' => 'refresh_token',
                    'refresh_token' => $account->refresh_token,
                ], 'Falha ao renovar o token da Conta Spotify.');

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

    private function requestToken(array $payload, string $errorMessage): array
    {
        $response = Http::asForm()
            ->withBasicAuth((string) config('spotify.client_id'), (string) config('spotify.client_secret'))
            ->post((string) config('spotify.token_url'), $payload);

        if (! $response->successful()) {
            throw new RuntimeException($errorMessage);
        }

        return $response->json();
    }
}
