<?php

namespace Tests\Feature;

use App\Http\Livewire\SearchCatalog;
use App\Models\SpotifyAccount;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\TestCase;

class CatalogSearchTest extends TestCase
{
    use RefreshDatabase;

    private function createAuthenticatedListener(): User
    {
        $user = User::create([
            'name' => 'Ouvinte Busca',
            'email' => 'busca@example.com',
        ]);

        SpotifyAccount::create([
            'user_id' => $user->id,
            'spotify_user_id' => 'spotify-search-user',
            'display_name' => 'Ouvinte Busca',
            'email' => 'busca@example.com',
            'product_type' => 'premium',
            'access_token' => 'search-token',
            'refresh_token' => 'search-refresh',
            'token_expires_at' => now()->addHour(),
            'scopes' => 'streaming',
        ]);

        return $user;
    }

    public function test_search_queries_spotify_with_limit_10_displays_tracks_artists_albums_and_loads_more(): void
    {
        Http::fake([
            'https://api.spotify.com/v1/search*offset=0*' => Http::response([
                'tracks' => [
                    'items' => [
                        [
                            'id' => 'track-1',
                            'uri' => 'spotify:track:track1',
                            'name' => 'One More Time',
                            'duration_ms' => 320000,
                            'artists' => [['id' => 'artist-1', 'name' => 'Daft Punk']],
                            'album' => [
                                'id' => 'album-1',
                                'name' => 'Discovery',
                                'images' => [['url' => 'https://i.scdn.co/image/discovery']],
                            ],
                        ],
                    ],
                    'total' => 25,
                ],
                'artists' => [
                    'items' => [
                        [
                            'id' => 'artist-1',
                            'name' => 'Daft Punk',
                            'images' => [['url' => 'https://i.scdn.co/image/daftpunk']],
                            'genres' => ['electronic', 'french house'],
                        ],
                    ],
                ],
                'albums' => [
                    'items' => [
                        [
                            'id' => 'album-1',
                            'name' => 'Discovery',
                            'release_date' => '2001-03-12',
                            'images' => [['url' => 'https://i.scdn.co/image/discovery']],
                            'artists' => [['id' => 'artist-1', 'name' => 'Daft Punk']],
                        ],
                    ],
                ],
            ], 200),
            'https://api.spotify.com/v1/search*offset=10*' => Http::response([
                'tracks' => [
                    'items' => [
                        [
                            'id' => 'track-2',
                            'uri' => 'spotify:track:track2',
                            'name' => 'Digital Love',
                            'duration_ms' => 301000,
                            'artists' => [['id' => 'artist-1', 'name' => 'Daft Punk']],
                            'album' => [
                                'id' => 'album-1',
                                'name' => 'Discovery',
                                'images' => [['url' => 'https://i.scdn.co/image/discovery']],
                            ],
                        ],
                    ],
                    'total' => 25,
                ],
            ], 200),
        ]);

        $user = $this->createAuthenticatedListener();
        $this->actingAs($user);

        Livewire::test(SearchCatalog::class)
            ->set('query', 'Daft Punk')
            ->assertSee('One More Time')
            ->assertSee('Daft Punk')
            ->assertSee('Discovery')
            ->assertSee('5:20')
            ->call('loadMoreTracks')
            ->assertSee('One More Time')
            ->assertSee('Digital Love');

        Http::assertSent(function ($request) {
            return str_starts_with($request->url(), 'https://api.spotify.com/v1/search?')
                && str_contains($request->url(), 'limit=10')
                && str_contains($request->url(), 'offset=0');
        });

        Http::assertSent(function ($request) {
            return str_starts_with($request->url(), 'https://api.spotify.com/v1/search?')
                && str_contains($request->url(), 'limit=10')
                && str_contains($request->url(), 'offset=10');
        });
    }

    public function test_search_handles_http_429_rate_limit_gracefully_with_clear_feedback(): void
    {
        Http::fake([
            'https://api.spotify.com/v1/search*' => Http::response([
                'error' => ['status' => 429, 'message' => 'API rate limit exceeded'],
            ], 429, ['Retry-After' => '12']),
        ]);

        $user = $this->createAuthenticatedListener();
        $this->actingAs($user);

        Livewire::test(SearchCatalog::class)
            ->set('query', 'Daft Punk')
            ->assertSee('12 segundos');
    }

    public function test_playing_a_track_from_search_upserts_local_tracks_catalog_and_sends_playback_context_with_offset(): void
    {
        Http::fake([
            'https://api.spotify.com/v1/search*' => Http::response([
                'tracks' => [
                    'items' => [
                        [
                            'id' => 'track-1',
                            'uri' => 'spotify:track:track1',
                            'name' => 'One More Time',
                            'duration_ms' => 320000,
                            'artists' => [['id' => 'artist-1', 'name' => 'Daft Punk']],
                            'album' => [
                                'id' => 'album-1',
                                'name' => 'Discovery',
                                'images' => [['url' => 'https://i.scdn.co/image/discovery']],
                            ],
                        ],
                        [
                            'id' => 'track-2',
                            'uri' => 'spotify:track:track2',
                            'name' => 'Aerodynamic',
                            'duration_ms' => 212000,
                            'artists' => [['id' => 'artist-1', 'name' => 'Daft Punk']],
                            'album' => [
                                'id' => 'album-1',
                                'name' => 'Discovery',
                                'images' => [['url' => 'https://i.scdn.co/image/discovery']],
                            ],
                        ],
                    ],
                    'total' => 2,
                ],
                'artists' => ['items' => []],
                'albums' => ['items' => []],
            ], 200),
            'https://api.spotify.com/v1/me/player/play*' => Http::response(null, 204),
        ]);

        $user = $this->createAuthenticatedListener();
        $user->getOrCreatePreferences()->update(['preferred_device_id' => 'device-sdk-1']);
        $this->actingAs($user);

        Livewire::test(SearchCatalog::class)
            ->set('query', 'Discovery')
            ->call('playTrackAtIndex', 1)
            ->assertDispatchedBrowserEvent('spotify:context-started');

        $this->assertDatabaseHas('tracks', [
            'spotify_track_id' => 'track-1',
            'spotify_track_uri' => 'spotify:track:track1',
            'title' => 'One More Time',
            'artist_name' => 'Daft Punk',
            'album_name' => 'Discovery',
            'duration_ms' => 320000,
        ]);

        $this->assertDatabaseHas('tracks', [
            'spotify_track_id' => 'track-2',
            'spotify_track_uri' => 'spotify:track:track2',
            'title' => 'Aerodynamic',
            'artist_name' => 'Daft Punk',
            'album_name' => 'Discovery',
            'duration_ms' => 212000,
        ]);

        $selectedTrack = \App\Models\Track::where('spotify_track_id', 'track-2')->first();
        $this->assertNotNull($selectedTrack);
        $this->assertSame($selectedTrack->id, $user->fresh()->preference->last_played_track_id);

        Http::assertSent(function ($request) {
            return $request->url() === 'https://api.spotify.com/v1/me/player/play?device_id=device-sdk-1'
                && $request->method() === 'PUT'
                && ($request['uris'] ?? null) === ['spotify:track:track1', 'spotify:track:track2']
                && ($request['offset'] ?? null) === ['position' => 1];
        });
    }
}
