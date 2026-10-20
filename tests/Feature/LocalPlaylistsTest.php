<?php

namespace Tests\Feature;

use App\Http\Livewire\AppShell;
use App\Http\Livewire\SearchCatalog;
use App\Models\SpotifyAccount;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\TestCase;

class LocalPlaylistsTest extends TestCase
{
    use RefreshDatabase;

    private function createAuthenticatedListener(string $suffix = '1'): User
    {
        $user = User::create([
            'name' => "Ouvinte {$suffix}",
            'email' => "playlist{$suffix}@example.com",
        ]);

        SpotifyAccount::create([
            'user_id' => $user->id,
            'spotify_user_id' => "spotify-pl-{$suffix}",
            'display_name' => "Ouvinte {$suffix}",
            'email' => "playlist{$suffix}@example.com",
            'product_type' => 'premium',
            'access_token' => "token-{$suffix}",
            'refresh_token' => "refresh-{$suffix}",
            'token_expires_at' => now()->addHour(),
        ]);

        return $user;
    }

    public function test_listener_can_create_local_playlist_in_sidebar_and_add_tracks_preventing_duplicates_and_isolating_users(): void
    {
        $listenerA = $this->createAuthenticatedListener('A');
        $listenerB = $this->createAuthenticatedListener('B');

        // Creating a Local Playlist from AppShell sidebar
        $shellA = Livewire::actingAs($listenerA)
            ->test(AppShell::class)
            ->set('newPlaylistName', 'Synthwave Mix')
            ->set('newPlaylistDescription', 'Clássicos eletrônicos para programar')
            ->call('createPlaylist');

        $shellA
            ->assertSee('Synthwave Mix')
            ->assertSet('activeView', 'playlist');

        $this->assertDatabaseHas('local_playlists', [
            'user_id' => $listenerA->id,
            'name' => 'Synthwave Mix',
            'description' => 'Clássicos eletrônicos para programar',
        ]);

        $playlistA = \App\Models\LocalPlaylist::where('user_id', $listenerA->id)->firstOrFail();

        // Listener B must NOT see Listener A's playlist in their sidebar
        Livewire::actingAs($listenerB)
            ->test(AppShell::class)
            ->assertDontSee('Synthwave Mix');

        Http::fake([
            'https://api.spotify.com/v1/search*' => Http::response([
                'tracks' => [
                    'total' => 2,
                    'items' => [
                        [
                            'id' => 'track-pl-1',
                            'uri' => 'spotify:track:track-pl-1',
                            'name' => 'Nightcall',
                            'duration_ms' => 258000,
                            'artists' => [['id' => 'artist-kav', 'name' => 'Kavinsky']],
                            'album' => [
                                'id' => 'album-out',
                                'name' => 'OutRun',
                                'images' => [['url' => 'https://img.spotify/outrun.jpg']],
                            ],
                        ],
                        [
                            'id' => 'track-pl-2',
                            'uri' => 'spotify:track:track-pl-2',
                            'name' => 'Pacific Coast Highway',
                            'duration_ms' => 378000,
                            'artists' => [['id' => 'artist-kav', 'name' => 'Kavinsky']],
                            'album' => [
                                'id' => 'album-out',
                                'name' => 'OutRun',
                                'images' => [['url' => 'https://img.spotify/outrun.jpg']],
                            ],
                        ],
                    ],
                ],
                'artists' => ['total' => 0, 'items' => []],
                'albums' => ['total' => 0, 'items' => []],
            ], 200),
        ]);

        // Listener A adds first track and second track to Synthwave Mix
        $searchComponent = Livewire::actingAs($listenerA)
            ->test(SearchCatalog::class)
            ->set('query', 'Kavinsky')
            ->call('addTrackToPlaylist', 0, $playlistA->id)
            ->assertSee('adicionada à Playlist Local')
            ->call('addTrackToPlaylist', 1, $playlistA->id);

        $track1 = \App\Models\Track::where('spotify_track_id', 'track-pl-1')->firstOrFail();
        $track2 = \App\Models\Track::where('spotify_track_id', 'track-pl-2')->firstOrFail();

        $this->assertDatabaseHas('playlist_tracks', [
            'local_playlist_id' => $playlistA->id,
            'track_id' => $track1->id,
            'position' => 1,
        ]);

        $this->assertDatabaseHas('playlist_tracks', [
            'local_playlist_id' => $playlistA->id,
            'track_id' => $track2->id,
            'position' => 2,
        ]);

        // Adding the same track again to the same playlist is blocked with a friendly notice
        $searchComponent
            ->call('addTrackToPlaylist', 0, $playlistA->id)
            ->assertSee('já faz parte desta Playlist Local');

        $this->assertSame(
            2,
            \App\Models\LocalPlaylistTrack::where('local_playlist_id', $playlistA->id)->count()
        );

        // Listener B cannot add tracks to Listener A's playlist
        Livewire::actingAs($listenerB)
            ->test(SearchCatalog::class)
            ->set('query', 'Kavinsky')
            ->call('addTrackToPlaylist', 0, $playlistA->id);

        $this->assertSame(
            2,
            \App\Models\LocalPlaylistTrack::where('local_playlist_id', $playlistA->id)->count()
        );
    }

    public function test_playlist_view_supports_editing_reordering_removing_playing_in_custom_order_and_deleting_with_user_isolation(): void
    {
        $listenerA = $this->createAuthenticatedListener('A');
        $listenerB = $this->createAuthenticatedListener('B');

        $playlist = \App\Models\LocalPlaylist::create([
            'user_id' => $listenerA->id,
            'name' => 'Roadtrip',
            'description' => 'Antiga descrição',
        ]);

        $playlist->addNormalizedTrack([
            'spotify_track_id' => 'track-rt-1',
            'spotify_track_uri' => 'spotify:track:track-rt-1',
            'title' => 'First Track',
            'artist_name' => 'Artist 1',
            'album_name' => 'Album 1',
            'duration_ms' => 200000,
        ]);

        $playlist->addNormalizedTrack([
            'spotify_track_id' => 'track-rt-2',
            'spotify_track_uri' => 'spotify:track:track-rt-2',
            'title' => 'Second Track',
            'artist_name' => 'Artist 2',
            'album_name' => 'Album 2',
            'duration_ms' => 210000,
        ]);

        $playlist->addNormalizedTrack([
            'spotify_track_id' => 'track-rt-3',
            'spotify_track_uri' => 'spotify:track:track-rt-3',
            'title' => 'Third Track',
            'artist_name' => 'Artist 3',
            'album_name' => 'Album 3',
            'duration_ms' => 220000,
        ]);

        // Listener B cannot view, edit, or delete Listener A's playlist
        Livewire::actingAs($listenerB)
            ->test(\App\Http\Livewire\PlaylistView::class, ['playlistId' => (string) $playlist->id])
            ->assertDontSee('First Track')
            ->set('editName', 'Hacked Name')
            ->call('updatePlaylist')
            ->call('deletePlaylist');

        $this->assertDatabaseHas('local_playlists', [
            'id' => $playlist->id,
            'name' => 'Roadtrip',
        ]);

        Http::fake([
            'https://api.spotify.com/v1/me/player/play*' => Http::response(null, 204),
        ]);

        // Listener A edits name & description, reorders tracks, plays in custom order, removes a track, and deletes the playlist
        $viewA = Livewire::actingAs($listenerA)
            ->test(\App\Http\Livewire\PlaylistView::class, ['playlistId' => (string) $playlist->id])
            ->assertSeeInOrder(['First Track', 'Second Track', 'Third Track'])
            ->set('editName', 'Roadtrip 2026')
            ->set('editDescription', 'Nova descrição atualizada')
            ->call('updatePlaylist')
            ->assertSee('Roadtrip 2026')
            ->assertSee('Nova descrição atualizada');

        $this->assertDatabaseHas('local_playlists', [
            'id' => $playlist->id,
            'name' => 'Roadtrip 2026',
            'description' => 'Nova descrição atualizada',
        ]);

        // Move 'Third Track' (index 2) up to index 1 -> order becomes: First Track, Third Track, Second Track
        $viewA->call('moveTrackUp', 2)
            ->assertSeeInOrder(['First Track', 'Third Track', 'Second Track']);

        // Move 'First Track' (index 0) down to index 1 -> order becomes: Third Track, First Track, Second Track
        $viewA->call('moveTrackDown', 0)
            ->assertSeeInOrder(['Third Track', 'First Track', 'Second Track']);

        // Playing from index 1 ('First Track') sends the reordered URIs with offset=1
        $viewA->call('playTrackAtIndex', 1)
            ->assertDispatchedBrowserEvent('spotify:context-started');

        Http::assertSent(function (\Illuminate\Http\Client\Request $request) {
            if ($request->method() !== 'PUT' || ! str_contains($request->url(), '/v1/me/player/play')) {
                return false;
            }

            $data = $request->data();

            return ($data['uris'] ?? []) === [
                'spotify:track:track-rt-3',
                'spotify:track:track-rt-1',
                'spotify:track:track-rt-2',
            ] && ($data['offset']['position'] ?? null) === 1;
        });

        // Remove 'First Track' (index 1) -> remaining tracks resequence to positions 1 and 2
        $viewA->call('removeTrackAtIndex', 1)
            ->assertDontSee('First Track')
            ->assertSeeInOrder(['Third Track', 'Second Track']);

        $positions = \App\Models\LocalPlaylistTrack::where('local_playlist_id', $playlist->id)
            ->orderBy('position')
            ->pluck('position')
            ->all();

        $this->assertSame([1, 2], $positions);

        // Delete playlist
        $viewA->call('deletePlaylist')
            ->assertEmitted('playlistsUpdated')
            ->assertEmitted('navigateTo', 'home');

        $this->assertDatabaseMissing('local_playlists', [
            'id' => $playlist->id,
        ]);
    }
}
