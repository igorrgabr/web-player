<?php

namespace Tests\Feature;

use App\Http\Livewire\AlbumView;
use App\Models\SpotifyAccount;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\TestCase;

class PlayHistoryAndHomeTest extends TestCase
{
    use RefreshDatabase;

    private function createAuthenticatedListener(string $suffix = '1'): User
    {
        $user = User::create([
            'name' => "Ouvinte {$suffix}",
            'email' => "history{$suffix}@example.com",
        ]);

        SpotifyAccount::create([
            'user_id' => $user->id,
            'spotify_user_id' => "spotify-hist-{$suffix}",
            'display_name' => "Ouvinte {$suffix}",
            'email' => "history{$suffix}@example.com",
            'product_type' => 'premium',
            'access_token' => "token-{$suffix}",
            'refresh_token' => "refresh-{$suffix}",
            'token_expires_at' => now()->addHour(),
        ]);

        return $user;
    }

    public function test_playing_track_records_play_history_updates_last_played_preference_and_preloads_metadata_in_player_bar(): void
    {
        $listener = $this->createAuthenticatedListener('A');

        Http::fake([
            'https://api.spotify.com/v1/albums/album-disc*' => Http::response([
                'id' => 'album-disc',
                'name' => 'Discovery',
                'release_date' => '2001-03-12',
                'images' => [['url' => 'https://img.spotify/discovery.jpg']],
                'artists' => [['id' => 'artist-dp', 'name' => 'Daft Punk']],
                'tracks' => [
                    'items' => [
                        [
                            'id' => 'track-omt',
                            'uri' => 'spotify:track:track-omt',
                            'name' => 'One More Time',
                            'duration_ms' => 320000,
                            'artists' => [['id' => 'artist-dp', 'name' => 'Daft Punk']],
                        ],
                        [
                            'id' => 'track-aero',
                            'uri' => 'spotify:track:track-aero',
                            'name' => 'Aerodynamic',
                            'duration_ms' => 212000,
                            'artists' => [['id' => 'artist-dp', 'name' => 'Daft Punk']],
                        ],
                    ],
                ],
            ], 200),
            'https://api.spotify.com/v1/me/player/play*' => Http::response(null, 204),
        ]);

        Livewire::actingAs($listener)
            ->test(AlbumView::class, ['albumId' => 'album-disc'])
            ->call('playTrackAtIndex', 1);

        $trackAero = \App\Models\Track::where('spotify_track_id', 'track-aero')->firstOrFail();

        $this->assertDatabaseHas('play_histories', [
            'user_id' => $listener->id,
            'track_id' => $trackAero->id,
            'context_type' => 'album',
            'context_uri' => 'spotify:album:album-disc',
        ]);

        $this->assertDatabaseHas('user_preferences', [
            'user_id' => $listener->id,
            'last_played_track_id' => $trackAero->id,
            'last_played_track_uri' => 'spotify:track:track-aero',
        ]);

        // Reopening GET /player preloads the last played track metadata into the footer player bar
        $response = $this->actingAs($listener)->get(route('player.index'));

        $response->assertOk()
            ->assertSee('Aerodynamic')
            ->assertSee('Daft Punk')
            ->assertSee('https://img.spotify/discovery.jpg');
    }

    public function test_home_view_displays_shortcuts_and_isolated_recent_play_history_and_pwa_assets_are_configured(): void
    {
        $listenerA = $this->createAuthenticatedListener('A');
        $listenerB = $this->createAuthenticatedListener('B');

        \App\Models\LocalPlaylist::create([
            'user_id' => $listenerA->id,
            'name' => 'Lo-Fi Study',
        ]);

        \App\Models\LocalPlaylist::create([
            'user_id' => $listenerB->id,
            'name' => 'Secret Playlist B',
        ]);

        $trackOld = \App\Models\Track::upsertFromCatalog([
            'spotify_track_id' => 'track-hist-1',
            'spotify_track_uri' => 'spotify:track:track-hist-1',
            'title' => 'Veridis Quo',
            'artist_name' => 'Daft Punk',
            'album_name' => 'Discovery',
            'duration_ms' => 345000,
        ]);

        $trackRecent = \App\Models\Track::upsertFromCatalog([
            'spotify_track_id' => 'track-hist-2',
            'spotify_track_uri' => 'spotify:track:track-hist-2',
            'title' => 'Something About Us',
            'artist_name' => 'Daft Punk',
            'album_name' => 'Discovery',
            'duration_ms' => 232000,
        ]);

        $trackOther = \App\Models\Track::upsertFromCatalog([
            'spotify_track_id' => 'track-hist-b',
            'spotify_track_uri' => 'spotify:track:track-hist-b',
            'title' => 'Private Track B',
            'artist_name' => 'Other Artist',
            'album_name' => 'Other Album',
            'duration_ms' => 190000,
        ]);

        \App\Models\PlayHistory::create([
            'user_id' => $listenerA->id,
            'track_id' => $trackOld->id,
            'context_type' => 'album',
            'played_at' => now()->subMinutes(15),
        ]);

        \App\Models\PlayHistory::create([
            'user_id' => $listenerA->id,
            'track_id' => $trackRecent->id,
            'context_type' => 'album',
            'played_at' => now()->subMinutes(2),
        ]);

        \App\Models\PlayHistory::create([
            'user_id' => $listenerB->id,
            'track_id' => $trackOther->id,
            'context_type' => 'search',
            'played_at' => now(),
        ]);

        Http::fake([
            'https://api.spotify.com/v1/me/player/play*' => Http::response(null, 204),
        ]);

        $homeComponent = Livewire::actingAs($listenerA)
            ->test(\App\Http\Livewire\HomeView::class)
            ->assertSee('Faixas Favoritas')
            ->assertSee('Lo-Fi Study')
            ->assertDontSee('Secret Playlist B')
            ->assertSeeInOrder(['Something About Us', 'Veridis Quo'])
            ->assertDontSee('Private Track B');

        // Rendering HomeView must not call any Spotify API endpoint
        Http::assertNothingSent();

        // Playing from recent history starts playback context
        $homeComponent->call('playTrackAtIndex', 0)
            ->assertDispatchedBrowserEvent('spotify:context-started');

        Http::assertSent(function (\Illuminate\Http\Client\Request $request) {
            if ($request->method() !== 'PUT' || ! str_contains($request->url(), '/v1/me/player/play')) {
                return false;
            }

            $data = $request->data();

            return ($data['uris'] ?? []) === ['spotify:track:track-hist-2', 'spotify:track:track-hist-1']
                && ($data['offset']['position'] ?? null) === 0;
        });

        // Verify PWA manifest link in layout and valid manifest + service worker files
        $page = $this->actingAs($listenerA)->get(route('player.index'));
        $page->assertOk()
            ->assertSee('/manifest.webmanifest', false);

        $this->assertFileExists(public_path('manifest.webmanifest'));
        $manifest = json_decode((string) file_get_contents(public_path('manifest.webmanifest')), true);
        $this->assertIsArray($manifest);
        $this->assertSame('standalone', $manifest['display'] ?? null);

        $this->assertFileExists(public_path('sw.js'));
        $swContents = (string) file_get_contents(public_path('sw.js'));
        $this->assertStringContainsString('/css/app.css', $swContents);
        $this->assertStringContainsString('/js/app.js', $swContents);
    }
}
