<?php

namespace App\Http\Livewire\Concerns;

use App\Exceptions\SpotifyApiException;
use App\Services\Spotify\SpotifyPlayerService;
use Illuminate\Support\Facades\Auth;

trait PlaysNormalizedTrackLists
{
    public function playTrackAtIndex(int $index, SpotifyPlayerService $playerService): void
    {
        if (! isset($this->tracks[$index])) {
            return;
        }

        $this->errorMessage = null;

        try {
            $selectedTrack = $playerService->playNormalizedTrackList(Auth::user(), $this->tracks, $index);

            if ($selectedTrack) {
                $this->dispatchBrowserEvent('spotify:context-started', [
                    'track' => [
                        'uri' => $selectedTrack->spotify_track_uri,
                        'title' => $selectedTrack->title,
                        'artistName' => $selectedTrack->artist_name,
                        'albumName' => $selectedTrack->album_name,
                        'albumCoverUrl' => $selectedTrack->album_cover_url,
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
