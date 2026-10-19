<?php

namespace App\Http\Controllers\Player;

use App\Http\Controllers\Controller;
use App\Services\Spotify\SpotifyAuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PlayerController extends Controller
{
    private SpotifyAuthService $authService;

    public function __construct(SpotifyAuthService $authService)
    {
        $this->authService = $authService;
    }

    public function index(): View
    {
        return view('welcome');
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
}
