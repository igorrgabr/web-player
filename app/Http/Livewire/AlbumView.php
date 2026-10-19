<?php

namespace App\Http\Livewire;

use App\Exceptions\SpotifyApiException;
use App\Services\Spotify\SpotifyCatalogService;
use App\Services\Spotify\SpotifyPlayerService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class AlbumView extends Component
{
    public string $albumId = '';
    public array $album = [];
    public array $tracks = [];
    public ?string $errorMessage = null;

    public function mount(string $albumId, SpotifyCatalogService $catalogService): void
    {
        $this->albumId = $albumId;
        $this->loadAlbum($catalogService);
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

    private function loadAlbum(SpotifyCatalogService $catalogService): void
    {
        $this->errorMessage = null;

        if (trim($this->albumId) === '') {
            return;
        }

        try {
            $data = $catalogService->getAlbum(Auth::user(), $this->albumId);
            $this->album = $data['album'];
            $this->tracks = $data['tracks'];
        } catch (SpotifyApiException $exception) {
            $this->errorMessage = $exception->getMessage();
        }
    }

    public function render(): View
    {
        return view('livewire.album-view');
    }
}
