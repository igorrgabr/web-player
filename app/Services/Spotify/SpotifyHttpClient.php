<?php

namespace App\Services\Spotify;

use App\Exceptions\SpotifyApiException;
use App\Models\User;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

class SpotifyHttpClient
{
    private SpotifyAuthService $authService;

    public function __construct(SpotifyAuthService $authService)
    {
        $this->authService = $authService;
    }

    public function request(User $user, string $method, string $endpoint, array $query = [], ?array $body = null): array
    {
        $account = $this->authService->ensureValidToken($user->spotifyAccount);
        $url = rtrim((string) config('spotify.api_base_url'), '/') . '/' . ltrim($endpoint, '/');

        if (! empty($query)) {
            $url .= (str_contains($url, '?') ? '&' : '?') . http_build_query($query, '', '&', PHP_QUERY_RFC3986);
        }

        $response = $this->send($account->access_token, $method, $url, $body);

        if ($response->status() === 401) {
            $account->update(['token_expires_at' => now()->subMinute()]);
            $account = $this->authService->ensureValidToken($account->fresh());
            $response = $this->send($account->access_token, $method, $url, $body);
        }

        if ($response->status() === 429) {
            $retryAfter = (int) $response->header('Retry-After', 5);
            throw new SpotifyApiException(
                "Limite de requisições da API do Spotify atingido. Tente novamente em {$retryAfter} segundos.",
                429,
                $retryAfter
            );
        }

        if (! $response->successful()) {
            $errorMessage = $response->json('error.message') ?? 'Falha na comunicação com a Spotify Web API.';
            throw new SpotifyApiException((string) $errorMessage, $response->status());
        }

        return $response->json() ?? [];
    }

    private function send(string $accessToken, string $method, string $url, ?array $body = null): Response
    {
        $pending = Http::withToken($accessToken)->acceptJson();
        $upperMethod = strtoupper($method);

        if ($upperMethod === 'GET') {
            return $pending->get($url);
        }

        if ($upperMethod === 'POST') {
            return $body === null ? $pending->send('POST', $url) : $pending->post($url, $body);
        }

        if ($upperMethod === 'PUT') {
            return $body === null ? $pending->send('PUT', $url) : $pending->put($url, $body);
        }

        if ($upperMethod === 'DELETE') {
            return $body === null ? $pending->delete($url) : $pending->delete($url, $body);
        }

        return $pending->send($upperMethod, $url, $body ? ['json' => $body] : []);
    }
}

