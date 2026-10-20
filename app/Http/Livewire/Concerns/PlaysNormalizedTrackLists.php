<?php

namespace App\Http\Livewire\Concerns;

use App\Exceptions\SpotifyApiException;
use App\Models\FavoriteTrack;
use App\Models\LocalPlaylist;
use App\Services\Spotify\SpotifyPlayerService;
use Illuminate\Support\Facades\Auth;

trait PlaysNormalizedTrackLists
{
    public array $favoritedTrackIds = [];
    public array $userPlaylists = [];
    public ?string $playlistNotice = null;

    public function syncFavoritedTrackIds(): void
    {
        $user = Auth::user();
        if (! $user) {
            $this->favoritedTrackIds = [];
            $this->userPlaylists = [];

            return;
        }

        $this->favoritedTrackIds = FavoriteTrack::favoritedSpotifyTrackIdsForUser($user);
        $this->userPlaylists = $user->localPlaylists()
            ->get(['id', 'name'])
            ->map(fn (LocalPlaylist $pl) => [
                'id' => (int) $pl->id,
                'name' => $pl->name,
            ])
            ->values()
            ->all();
    }

    public function addTrackToPlaylist(int $index, int $playlistId): void
    {
        if (! isset($this->tracks[$index])) {
            return;
        }

        $this->playlistNotice = null;
        $this->errorMessage = null;

        $user = Auth::user();
        $playlist = LocalPlaylist::where('id', $playlistId)
            ->where('user_id', $user->id)
            ->first();

        if (! $playlist) {
            $this->errorMessage = 'Playlist Local não encontrada.';

            return;
        }

        $trackData = $this->tracks[$index];
        $added = $playlist->addNormalizedTrack($trackData);

        if (! $added) {
            $this->playlistNotice = sprintf(
                'A Faixa "%s" já faz parte desta Playlist Local.',
                $trackData['title'] ?? 'Selecionada'
            );

            return;
        }

        $this->playlistNotice = sprintf(
            'Faixa "%s" adicionada à Playlist Local "%s".',
            $trackData['title'] ?? 'Selecionada',
            $playlist->name
        );
    }

    public function toggleFavoriteAtIndex(int $index): void
    {
        if (! isset($this->tracks[$index])) {
            return;
        }

        $trackData = $this->tracks[$index];
        $favorited = FavoriteTrack::toggleForUser(Auth::user(), $trackData);
        $this->syncFavoritedTrackIds();

        $spotifyTrackId = (string) ($trackData['spotify_track_id'] ?? '');
        $spotifyTrackUri = (string) ($trackData['spotify_track_uri'] ?? ('spotify:track:' . $spotifyTrackId));

        $this->dispatchBrowserEvent('spotify:favorite-updated', [
            'spotifyTrackId' => $spotifyTrackId,
            'spotifyTrackUri' => $spotifyTrackUri,
            'favorited' => $favorited,
        ]);
    }

    public function playTrackAtIndex(int $index, SpotifyPlayerService $playerService): void
    {
        if (! isset($this->tracks[$index])) {
            return;
        }

        $this->errorMessage = null;

        try {
            $selectedTrack = $playerService->playNormalizedTrackList(Auth::user(), $this->tracks, $index);

            if ($selectedTrack) {
                $isFavorited = in_array($selectedTrack->spotify_track_id, $this->favoritedTrackIds, true);

                $this->dispatchBrowserEvent('spotify:context-started', [
                    'track' => [
                        'id' => $selectedTrack->spotify_track_id,
                        'uri' => $selectedTrack->spotify_track_uri,
                        'title' => $selectedTrack->title,
                        'artistName' => $selectedTrack->artist_name,
                        'albumName' => $selectedTrack->album_name,
                        'albumCoverUrl' => $selectedTrack->album_cover_url,
                        'favorited' => $isFavorited,
                    ],
                    'durationMs' => $selectedTrack->duration_ms,
                    'offset' => $index,
                ]);
            }
        } catch (SpotifyApiException $exception) {
            $this->errorMessage = $exception->getMessage();
        }
    }
}
