<?php

namespace Tests\Feature;

use App\Http\Livewire\AlbumView;
use App\Http\Livewire\ArtistView;
use App\Models\SpotifyAccount;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\TestCase;

class AlbumAndArtistViewTest extends TestCase
{
    use RefreshDatabase;

    private function createAuthenticatedListener(): User
    {
        $user = User::create([
            'name' => 'Ouvinte Catálogo',
            'email' => 'catalogo@example.com',
        ]);

        SpotifyAccount::create([
            'user_id' => $user->id,
            'spotify_user_id' => 'spotify-catalogo-user',
            'display_name' => 'Ouvinte Catálogo',
            'email' => 'catalogo@example.com',
            'product_type' => 'premium',
            'access_token' => 'valid-access-token',
            'refresh_token' => 'valid-refresh-token',
            'token_expires_at' => now()->addHour(),
        ]);

        return $user;
    }

    public function test_album_view_displays_header_and_tracks_and_plays_full_album_from_any_offset(): void
    {
        $user = $this->createAuthenticatedListener();

        Http::fake([
            'https://api.spotify.com/v1/albums/album-ram*' => Http::response([
                'id' => 'album-ram',
                'name' => 'Random Access Memories',
                'release_date' => '2013-05-17',
                'images' => [['url' => 'https://img.spotify/ram.jpg']],
                'artists' => [['id' => 'artist-dp', 'name' => 'Daft Punk']],
                'tracks' => [
                    'items' => [
                        [
                            'id' => 'track-glbtm',
                            'uri' => 'spotify:track:track-glbtm',
                            'name' => 'Give Life Back to Music',
                            'duration_ms' => 275000,
                            'artists' => [['id' => 'artist-dp', 'name' => 'Daft Punk']],
                        ],
                        [
                            'id' => 'track-gl',
                            'uri' => 'spotify:track:track-gl',
                            'name' => 'Get Lucky',
                            'duration_ms' => 369000,
                            'artists' => [['id' => 'artist-dp', 'name' => 'Daft Punk']],
                        ],
                    ],
                ],
            ], 200),
            'https://api.spotify.com/v1/me/player/play*' => Http::response(null, 204),
        ]);

        $component = Livewire::actingAs($user)
            ->test(AlbumView::class, ['albumId' => 'album-ram'])
            ->assertSee('Random Access Memories')
            ->assertSee('Daft Punk')
            ->assertSee('Give Life Back to Music')
            ->assertSee('Get Lucky')
            ->call('playTrackAtIndex', 1);

        $component->assertDispatchedBrowserEvent('spotify:context-started');

        $this->assertDatabaseHas('tracks', [
            'spotify_track_id' => 'track-glbtm',
            'album_name' => 'Random Access Memories',
            'spotify_album_id' => 'album-ram',
            'album_cover_url' => 'https://img.spotify/ram.jpg',
        ]);
        $this->assertDatabaseHas('tracks', [
            'spotify_track_id' => 'track-gl',
            'title' => 'Get Lucky',
            'album_name' => 'Random Access Memories',
        ]);

        Http::assertSent(function (Request $request) {
            if ($request->method() !== 'PUT' || ! str_contains($request->url(), '/v1/me/player/play')) {
                return false;
            }

            $data = $request->data();

            return ($data['uris'] ?? []) === ['spotify:track:track-glbtm', 'spotify:track:track-gl']
                && ($data['offset']['position'] ?? null) === 1;
        });
    }

    public function test_artist_view_displays_profile_filtered_tracks_and_discography_without_deprecated_endpoints(): void
    {
        $user = $this->createAuthenticatedListener();

        Http::fake([
            'https://api.spotify.com/v1/artists/artist-dp' => Http::response([
                'id' => 'artist-dp',
                'name' => 'Daft Punk',
                'genres' => ['electronic', 'french house'],
                'images' => [['url' => 'https://img.spotify/dp.jpg']],
            ], 200),
            'https://api.spotify.com/v1/search*' => Http::response([
                'tracks' => [
                    'total' => 1,
                    'items' => [
                        [
                            'id' => 'track-hb',
                            'uri' => 'spotify:track:track-hb',
                            'name' => 'Harder, Better, Faster, Stronger',
                            'duration_ms' => 224000,
                            'artists' => [['id' => 'artist-dp', 'name' => 'Daft Punk']],
                            'album' => [
                                'id' => 'album-disc',
                                'name' => 'Discovery',
                                'images' => [['url' => 'https://img.spotify/disc.jpg']],
                            ],
                        ],
                    ],
                ],
            ], 200),
            'https://api.spotify.com/v1/artists/artist-dp/albums*' => Http::response([
                'items' => [
                    [
                        'id' => 'album-disc',
                        'name' => 'Discovery',
                        'release_date' => '2001-03-12',
                        'artists' => [['id' => 'artist-dp', 'name' => 'Daft Punk']],
                        'images' => [['url' => 'https://img.spotify/disc.jpg']],
                    ],
                ],
            ], 200),
            'https://api.spotify.com/v1/me/player/play*' => Http::response(null, 204),
        ]);

        Livewire::actingAs($user)
            ->test(ArtistView::class, ['artistId' => 'artist-dp'])
            ->assertSee('Daft Punk')
            ->assertSee('Harder, Better, Faster, Stronger')
            ->assertSee('Discovery')
            ->call('playTrackAtIndex', 0);

        $this->assertDatabaseHas('tracks', [
            'spotify_track_id' => 'track-hb',
            'title' => 'Harder, Better, Faster, Stronger',
        ]);

        Http::assertSent(function (Request $request) {
            return str_contains($request->url(), '/v1/search')
                && ($request->data()['limit'] ?? null) == 10
                && str_contains((string) ($request->data()['q'] ?? ''), 'artist:"Daft Punk"');
        });

        Http::assertSent(function (Request $request) {
            return str_contains($request->url(), '/v1/artists/artist-dp/albums')
                && ($request->data()['limit'] ?? null) == 10;
        });

        Http::assertNotSent(function (Request $request) {
            return str_contains($request->url(), 'top-tracks')
                || str_contains($request->url(), 'related-artists');
        });
    }
}
