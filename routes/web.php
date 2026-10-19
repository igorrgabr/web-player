<?php

use App\Http\Controllers\Auth\SpotifyAuthController;
use App\Http\Controllers\Player\PlayerController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
})->name('login');

Route::get('/auth/spotify/redirect', [SpotifyAuthController::class, 'redirect'])
    ->name('spotify.redirect');

Route::get('/auth/spotify/callback', [SpotifyAuthController::class, 'callback'])
    ->name('spotify.callback');

Route::middleware('auth')->group(function () {
    Route::post('/logout', [SpotifyAuthController::class, 'logout'])
        ->name('logout');

    Route::middleware('spotify.connected')->group(function () {
        Route::get('/player', [PlayerController::class, 'index'])
            ->name('player.index');

        Route::get('/player/token', [PlayerController::class, 'token'])
            ->name('player.token');
    });
});
