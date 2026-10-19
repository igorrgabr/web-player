<?php

namespace App\Http\Livewire\Concerns;

use App\Exceptions\SpotifyApiException;
use App\Models\FavoriteTrack;
use App\Services\Spotify\SpotifyPlayerService;
use Illuminate\Support\Facades\Auth;

trait PlaysNormalizedTrackLists
{
    public array $favoritedTrackIds = [];

    public function syncFavoritedTrackIds(): void
    {
        $user = Auth::user();
        if (! $user) {
            $this->favoritedTrackIds = [];

            return;
        }

        $this->favoritedTrackIds = FavoriteTrack::where('user_id', $user->id)
            ->join('tracks', 'tracks.id', '=', 'favorite_tracks.track_id')
            ->pluck('tracks.spotify_track_id')
            ->values()
            ->all();
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
