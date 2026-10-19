<?php

namespace App\Http\Middleware;

use App\Exceptions\SpotifyPremiumRequiredException;
use Closure;
use Illuminate\Http\Request;

class EnsureSpotifyConnected
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();
        $account = $user ? $user->spotifyAccount : null;

        if (! $account || ! $account->isPremium()) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => SpotifyPremiumRequiredException::DEFAULT_MESSAGE,
                ], 403);
            }

            return redirect()->route('login')->withErrors([
                'spotify' => SpotifyPremiumRequiredException::DEFAULT_MESSAGE,
            ]);
        }

        return $next($request);
    }
}
