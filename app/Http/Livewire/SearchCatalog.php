<?php

namespace App\Http\Livewire;

use App\Exceptions\SpotifyApiException;
use App\Services\Spotify\SpotifyCatalogService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class SearchCatalog extends Component
{
    public string $query = '';
    public array $tracks = [];
    public int $tracksTotal = 0;
    public int $tracksOffset = 0;
    public array $artists = [];
    public array $albums = [];
    public ?string $errorMessage = null;

    protected $queryString = [
        'query' => ['except' => '', 'as' => 'q'],
    ];

    public function mount(SpotifyCatalogService $catalogService): void
    {
        if (trim($this->query) !== '') {
            $this->performSearch($catalogService);
        }
    }

    public function updatedQuery(): void
    {
        $this->tracksOffset = 0;
        $this->performSearch(app(SpotifyCatalogService::class));
    }

    public function loadMoreTracks(SpotifyCatalogService $catalogService): void
    {
        if (trim($this->query) === '') {
            return;
        }

        $this->errorMessage = null;
        $nextOffset = $this->tracksOffset + SpotifyCatalogService::DEV_MODE_PAGE_LIMIT;

        try {
            $results = $catalogService->search(Auth::user(), $this->query, $nextOffset, 'track');
            $this->tracks = array_values(array_merge($this->tracks, $results['tracks']));
            $this->tracksTotal = $results['tracks_total'];
            $this->tracksOffset = $nextOffset;
        } catch (SpotifyApiException $exception) {
            $this->errorMessage = $exception->getMessage();
        }
    }

    public function playTrackAtIndex(int $index, \App\Services\Spotify\SpotifyPlayerService $playerService): void
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

    private function performSearch(SpotifyCatalogService $catalogService): void
    {
        $this->errorMessage = null;

        if (trim($this->query) === '') {
            $this->tracks = [];
            $this->tracksTotal = 0;
            $this->artists = [];
            $this->albums = [];

            return;
        }

        try {
            $results = $catalogService->search(Auth::user(), $this->query, 0, 'track,artist,album');
            $this->tracks = $results['tracks'];
            $this->tracksTotal = $results['tracks_total'];
            $this->artists = $results['artists'];
            $this->albums = $results['albums'];
        } catch (SpotifyApiException $exception) {
            $this->errorMessage = $exception->getMessage();
        }
    }

    public function render(): View
    {
        return view('livewire.search-catalog');
    }
}
