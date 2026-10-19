<?php

return [
    'client_id' => env('SPOTIFY_CLIENT_ID', ''),
    'client_secret' => env('SPOTIFY_CLIENT_SECRET', ''),
    'redirect_uri' => env('SPOTIFY_REDIRECT_URI', 'http://127.0.0.1:8000/auth/spotify/callback'),
    'auth_url' => env('SPOTIFY_AUTH_URL', 'https://accounts.spotify.com/authorize'),
    'token_url' => env('SPOTIFY_TOKEN_URL', 'https://accounts.spotify.com/api/token'),
    'api_base_url' => env('SPOTIFY_API_BASE_URL', 'https://api.spotify.com/v1'),
    'scopes' => [
        'streaming',
        'user-read-email',
        'user-read-private',
        'user-read-playback-state',
        'user-modify-playback-state',
        'user-read-currently-playing',
    ],
];
