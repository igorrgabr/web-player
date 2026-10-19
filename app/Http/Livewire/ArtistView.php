<?php

namespace App\Http\Livewire;

use App\Exceptions\SpotifyApiException;
use App\Services\Spotify\SpotifyCatalogService;
use App\Services\Spotify\SpotifyPlayerService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class ArtistView extends Component
{
    public string $artistId = '';
    public array $artist = [];
    public array $tracks = [];
    public array $albums = [];
    public ?string $errorMessage = null;

    public function mount(string $artistId, SpotifyCatalogService $catalogService): void
    {
        $this->artistId = $artistId;
        $this->loadArtist($catalogService);
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

    private function loadArtist(SpotifyCatalogService $catalogService): void
    {
        $this->errorMessage = null;

        if (trim($this->artistId) === '') {
            return;
        }

        try {
            $data = $catalogService->getArtistOverview(Auth::user(), $this->artistId);
            $this->artist = $data['artist'];
            $this->tracks = $data['tracks'];
            $this->albums = $data['albums'];
        } catch (SpotifyApiException $exception) {
            $this->errorMessage = $exception->getMessage();
        }
    }

    public function render(): View
    {
        return view('livewire.artist-view');
    }
}
