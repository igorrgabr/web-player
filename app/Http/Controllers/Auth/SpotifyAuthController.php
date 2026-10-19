<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\Spotify\SpotifyAuthService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class SpotifyAuthController extends Controller
{
    private SpotifyAuthService $authService;

    public function __construct(SpotifyAuthService $authService)
    {
        $this->authService = $authService;
    }

    public function redirect(Request $request): RedirectResponse
    {
        $state = Str::random(40);
        $request->session()->put('spotify_oauth_state', $state);

        return redirect()->away($this->authService->buildAuthorizationUrl($state));
    }

    public function callback(Request $request): RedirectResponse
    {
        $expectedState = $request->session()->pull('spotify_oauth_state');
        $providedState = $request->query('state');

        if (! is_string($expectedState) || $expectedState === '' || ! is_string($providedState) || ! hash_equals($expectedState, $providedState)) {
            abort(403, 'Invalid OAuth state.');
        }

        $code = (string) $request->query('code', '');
        if ($code === '') {
            return redirect()->route('login')->withErrors([
                'spotify' => 'Autorização do Spotify não concluída.',
            ]);
        }

        $account = $this->authService->authenticateWithCode($code);

        if (! $account->isPremium()) {
            return redirect()->route('login')->withErrors([
                'spotify' => 'A reprodução completa exige uma Conta Spotify com assinatura Spotify Premium ativa.',
            ]);
        }

        Auth::login($account->user);
        $request->session()->regenerate();

        return redirect()->route('player.index');
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
