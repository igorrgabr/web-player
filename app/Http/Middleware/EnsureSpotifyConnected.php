<?php

namespace App\Http\Middleware;

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
                    'message' => 'A reprodução exige uma Conta Spotify Premium vinculada.',
                ], 403);
            }

            return redirect()->route('login')->withErrors([
                'spotify' => 'A reprodução completa exige uma Conta Spotify com assinatura Spotify Premium ativa.',
            ]);
        }

        return $next($request);
    }
}
