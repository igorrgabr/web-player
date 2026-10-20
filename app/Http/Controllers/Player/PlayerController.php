<?php

namespace App\Http\Controllers\Player;

use App\Http\Controllers\Controller;
use App\Models\UserPreference;
use App\Services\Spotify\SpotifyAuthService;
use App\Services\Spotify\SpotifyPlayerService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class PlayerController extends Controller
{
    private SpotifyAuthService $authService;
    private SpotifyPlayerService $playerService;

    public function __construct(SpotifyAuthService $authService, SpotifyPlayerService $playerService)
    {
        $this->authService = $authService;
        $this->playerService = $playerService;
    }

    public function index(Request $request): View
    {
        $preferences = $request->user()->getOrCreatePreferences();
        $preferences->load('lastPlayedTrack');

        return view('layouts.app', [
            'preferences' => $preferences,
        ]);
    }

    public function token(Request $request): JsonResponse
    {
        $account = $this->authService->ensureValidToken($request->user()->spotifyAccount);

        return response()->json([
            'access_token' => $account->access_token,
            'spotify_user_id' => $account->spotify_user_id,
            'expires_at' => optional($account->token_expires_at)->toIso8601String(),
        ]);
    }

    public function devices(Request $request): JsonResponse
    {
        $devices = $this->playerService->getDevices($request->user());

        return response()->json([
            'devices' => $devices,
        ]);
    }

    public function transfer(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'device_id' => ['required', 'string', 'max:128'],
            'play' => ['sometimes', 'boolean'],
        ]);

        $this->playerService->transferPlayback(
            $request->user(),
            $validated['device_id'],
            (bool) ($validated['play'] ?? false)
        );

        return response()->json([
            'device_id' => $validated['device_id'],
        ]);
    }

    public function play(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'device_id' => ['nullable', 'string', 'max:128'],
            'uris' => ['sometimes', 'array', 'min:1'],
            'uris.*' => ['required', 'string', 'regex:/^spotify:track:[a-zA-Z0-9]+$/'],
            'offset' => ['sometimes', 'integer', 'min:0'],
        ]);

        $currentUri = $this->playerService->playContext(
            $request->user(),
            $validated['uris'] ?? [],
            (int) ($validated['offset'] ?? 0),
            $validated['device_id'] ?? null
        );

        return response()->json([
            'playing' => true,
            'current_uri' => $currentUri,
        ]);
    }

    public function pause(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'device_id' => ['nullable', 'string', 'max:128'],
        ]);

        $this->playerService->pause($request->user(), $validated['device_id'] ?? null);

        return response()->json(['paused' => true]);
    }

    public function next(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'device_id' => ['nullable', 'string', 'max:128'],
        ]);

        $this->playerService->next($request->user(), $validated['device_id'] ?? null);

        return response()->json(['skipped' => 'next']);
    }

    public function previous(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'device_id' => ['nullable', 'string', 'max:128'],
        ]);

        $this->playerService->previous($request->user(), $validated['device_id'] ?? null);

        return response()->json(['skipped' => 'previous']);
    }

    public function seek(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'device_id' => ['nullable', 'string', 'max:128'],
            'position_ms' => ['required', 'integer', 'min:0'],
        ]);

        $this->playerService->seek(
            $request->user(),
            (int) $validated['position_ms'],
            $validated['device_id'] ?? null
        );

        return response()->json([
            'position_ms' => (int) $validated['position_ms'],
        ]);
    }

    public function preferences(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'device_id' => ['nullable', 'string', 'max:128'],
            'default_volume' => ['sometimes', 'integer', 'min:0', 'max:100'],
            'repeat_mode' => ['sometimes', 'string', Rule::in(UserPreference::ALLOWED_REPEAT_MODES)],
            'shuffle_enabled' => ['sometimes', 'boolean'],
        ]);

        $preference = $this->playerService->updatePreferences(
            $request->user(),
            $validated,
            $validated['device_id'] ?? null
        );

        return response()->json([
            'default_volume' => $preference->default_volume,
            'repeat_mode' => $preference->repeat_mode,
            'shuffle_enabled' => $preference->shuffle_enabled,
            'preferred_device_id' => $preference->preferred_device_id,
        ]);
    }

    public function favoriteStatus(Request $request): JsonResponse
    {
        $trackId = $request->query('track_id');
        $uri = $request->query('uri');

        $favorited = \App\Models\FavoriteTrack::isFavoritedByUser(
            $request->user(),
            is_string($trackId) ? $trackId : null,
            is_string($uri) ? $uri : null
        );

        return response()->json([
            'favorited' => $favorited,
        ]);
    }

    public function toggleFavorite(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'spotify_track_id' => ['nullable', 'string', 'max:128'],
            'spotify_track_uri' => ['nullable', 'string', 'max:255'],
            'title' => ['nullable', 'string', 'max:255'],
            'artist_name' => ['nullable', 'string', 'max:255'],
            'spotify_artist_id' => ['nullable', 'string', 'max:128'],
            'album_name' => ['nullable', 'string', 'max:255'],
            'spotify_album_id' => ['nullable', 'string', 'max:128'],
            'album_cover_url' => ['nullable', 'string', 'max:1024'],
            'duration_ms' => ['nullable', 'integer', 'min:1'],
        ]);

        $spotifyTrackId = (string) ($validated['spotify_track_id'] ?? '');
        $spotifyTrackUri = (string) ($validated['spotify_track_uri'] ?? '');

        if ($spotifyTrackId === '' && str_starts_with($spotifyTrackUri, 'spotify:track:')) {
            $spotifyTrackId = substr($spotifyTrackUri, strlen('spotify:track:'));
        }

        if ($spotifyTrackId === '') {
            return response()->json(['message' => 'Identificador da Faixa inválido.'], 422);
        }

        $validated['spotify_track_id'] = $spotifyTrackId;
        $validated['spotify_track_uri'] = $spotifyTrackUri !== '' ? $spotifyTrackUri : ('spotify:track:' . $spotifyTrackId);

        $favorited = \App\Models\FavoriteTrack::toggleForUser($request->user(), $validated);

        return response()->json([
            'favorited' => $favorited,
            'spotify_track_id' => $spotifyTrackId,
            'spotify_track_uri' => $validated['spotify_track_uri'],
        ]);
    }
}
