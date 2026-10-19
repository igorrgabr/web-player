<?php

namespace Tests\Feature;

use App\Http\Livewire\SearchCatalog;
use App\Models\SpotifyAccount;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\TestCase;

class FavoritesTest extends TestCase
{
    use RefreshDatabase;

    private function createAuthenticatedListener(string $suffix = '1'): User
    {
        $user = User::create([
            'name' => "Ouvinte {$suffix}",
            'email' => "ouvinte{$suffix}@example.com",
        ]);

        SpotifyAccount::create([
            'user_id' => $user->id,
            'spotify_user_id' => "spotify-fav-{$suffix}",
            'display_name' => "Ouvinte {$suffix}",
            'email' => "ouvinte{$suffix}@example.com",
            'product_type' => 'premium',
            'access_token' => "token-{$suffix}",
            'refresh_token' => "refresh-{$suffix}",
            'token_expires_at' => now()->addHour(),
        ]);

        return $user;
    }

    public function test_listener_can_toggle_favorite_from_catalog_and_player_bar_with_strict_user_isolation(): void
    {
        $listenerA = $this->createAuthenticatedListener('A');
        $listenerB = $this->createAuthenticatedListener('B');

        Http::fake([
            'https://api.spotify.com/v1/search*' => Http::response([
                'tracks' => [
                    'total' => 1,
                    'items' => [
                        [
                            'id' => 'track-fav-1',
                            'uri' => 'spotify:track:track-fav-1',
                            'name' => 'Digital Love',
                            'duration_ms' => 301000,
                            'artists' => [['id' => 'artist-dp', 'name' => 'Daft Punk']],
                            'album' => [
                                'id' => 'album-disc',
                                'name' => 'Discovery',
                                'images' => [['url' => 'https://img.spotify/disc.jpg']],
                            ],
                        ],
                    ],
                ],
                'artists' => ['total' => 0, 'items' => []],
                'albums' => ['total' => 0, 'items' => []],
            ], 200),
        ]);

        $component = Livewire::actingAs($listenerA)
            ->test(SearchCatalog::class)
            ->set('query', 'Digital Love')
            ->assertSet('favoritedTrackIds', [])
            ->call('toggleFavoriteAtIndex', 0);

        $component
            ->assertSet('favoritedTrackIds', ['track-fav-1'])
            ->assertDispatchedBrowserEvent('spotify:favorite-updated', [
                'spotifyTrackId' => 'track-fav-1',
                'spotifyTrackUri' => 'spotify:track:track-fav-1',
                'favorited' => true,
            ]);

        $this->assertDatabaseHas('tracks', [
            'spotify_track_id' => 'track-fav-1',
            'title' => 'Digital Love',
        ]);

        $this->assertDatabaseHas('favorite_tracks', [
            'user_id' => $listenerA->id,
        ]);

        // Listener B must not see Listener A's favorite
        $this->actingAs($listenerB)
            ->getJson(route('player.favorites.status', ['uri' => 'spotify:track:track-fav-1']))
            ->assertOk()
            ->assertJson(['favorited' => false]);

        // Listener A sees it as favorited via player bar status endpoint
        $this->actingAs($listenerA)
            ->getJson(route('player.favorites.status', ['uri' => 'spotify:track:track-fav-1']))
            ->assertOk()
            ->assertJson(['favorited' => true]);

        // Unfavoriting from the player bar endpoint with only URI/ID preserves existing track metadata in DB
        $this->actingAs($listenerA)
            ->postJson(route('player.favorites.toggle'), [
                'spotify_track_uri' => 'spotify:track:track-fav-1',
            ])
            ->assertOk()
            ->assertJson(['favorited' => false]);

        $this->assertDatabaseHas('tracks', [
            'spotify_track_id' => 'track-fav-1',
            'title' => 'Digital Love',
            'artist_name' => 'Daft Punk',
        ]);

        $this->assertDatabaseMissing('favorite_tracks', [
            'user_id' => $listenerA->id,
        ]);
    }

    public function test_favorites_library_lists_tracks_in_reverse_chronological_order_without_spotify_api_and_plays_context(): void
    {
        $listenerA = $this->createAuthenticatedListener('A');
        $listenerB = $this->createAuthenticatedListener('B');

        $olderTrack = \App\Models\Track::upsertFromCatalog([
            'spotify_track_id' => 'track-older',
            'spotify_track_uri' => 'spotify:track:track-older',
            'title' => 'Older Song',
            'artist_name' => 'Artist One',
            'album_name' => 'Album One',
            'duration_ms' => 180000,
        ]);

        $newerTrack = \App\Models\Track::upsertFromCatalog([
            'spotify_track_id' => 'track-newer',
            'spotify_track_uri' => 'spotify:track:track-newer',
            'title' => 'Newer Song',
            'artist_name' => 'Artist Two',
            'album_name' => 'Album Two',
            'duration_ms' => 240000,
        ]);

        $otherUserTrack = \App\Models\Track::upsertFromCatalog([
            'spotify_track_id' => 'track-other',
            'spotify_track_uri' => 'spotify:track:track-other',
            'title' => 'Secret Song B',
            'artist_name' => 'Artist B',
            'album_name' => 'Album B',
            'duration_ms' => 200000,
        ]);

        \App\Models\FavoriteTrack::create([
            'user_id' => $listenerA->id,
            'track_id' => $olderTrack->id,
            'favorited_at' => now()->subMinutes(10),
        ]);

        \App\Models\FavoriteTrack::create([
            'user_id' => $listenerA->id,
            'track_id' => $newerTrack->id,
            'favorited_at' => now()->subMinutes(1),
        ]);

        \App\Models\FavoriteTrack::create([
            'user_id' => $listenerB->id,
            'track_id' => $otherUserTrack->id,
            'favorited_at' => now(),
        ]);

        Http::fake([
            'https://api.spotify.com/v1/me/player/play*' => Http::response(null, 204),
        ]);

        $component = Livewire::actingAs($listenerA)
            ->test(\App\Http\Livewire\FavoritesLibrary::class)
            ->assertSeeInOrder(['Newer Song', 'Older Song'])
            ->assertDontSee('Secret Song B');

        // Rendering the library must NOT call any Spotify API endpoint
        Http::assertNothingSent();

        // Playing from the top button (offset=0) sends the entire reverse-chronological favorites list starting at 0
        $component->call('playTrackAtIndex', 0)
            ->assertDispatchedBrowserEvent('spotify:context-started');

        Http::assertSent(function (\Illuminate\Http\Client\Request $request) {
            if ($request->method() !== 'PUT' || ! str_contains($request->url(), '/v1/me/player/play')) {
                return false;
            }

            $data = $request->data();

            return ($data['uris'] ?? []) === ['spotify:track:track-newer', 'spotify:track:track-older']
                && ($data['offset']['position'] ?? null) === 0;
        });

        // Playing from index 1 ('Older Song') sends the entire reverse-chronological favorites list with offset=1
        $component->call('playTrackAtIndex', 1)
            ->assertDispatchedBrowserEvent('spotify:context-started');

        Http::assertSent(function (\Illuminate\Http\Client\Request $request) {
            if ($request->method() !== 'PUT' || ! str_contains($request->url(), '/v1/me/player/play')) {
                return false;
            }

            $data = $request->data();

            return ($data['uris'] ?? []) === ['spotify:track:track-newer', 'spotify:track:track-older']
                && ($data['offset']['position'] ?? null) === 1;
        });

        // Unfavoriting index 0 ('Newer Song') removes it from the list
        $component->call('toggleFavoriteAtIndex', 0)
            ->assertDontSee('Newer Song')
            ->assertSee('Older Song');
    }
}
