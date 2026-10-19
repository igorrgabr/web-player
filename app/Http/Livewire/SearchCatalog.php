<?php

namespace App\Http\Livewire;

use App\Exceptions\SpotifyApiException;
use App\Http\Livewire\Concerns\PlaysNormalizedTrackLists;
use App\Services\Spotify\SpotifyCatalogService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class SearchCatalog extends Component
{
    use PlaysNormalizedTrackLists;

    public string $query = '';
    public array $tracks = [];
    public int $tracksTotal = 0;
    public int $tracksOffset = 0;
    public array $artists = [];
    public int $artistsTotal = 0;
    public int $artistsOffset = 0;
    public array $albums = [];
    public int $albumsTotal = 0;
    public int $albumsOffset = 0;
    public ?string $errorMessage = null;

    protected $queryString = [
        'query' => ['except' => '', 'as' => 'q'],
    ];

    protected $listeners = [
        'refreshFavoritesState' => 'syncFavoritedTrackIds',
    ];

    public function mount(SpotifyCatalogService $catalogService): void
    {
        $this->syncFavoritedTrackIds();

        if (trim($this->query) !== '') {
            $this->performSearch($catalogService);
        }
    }

    public function updatedQuery(): void
    {
        $this->tracksOffset = 0;
        $this->artistsOffset = 0;
        $this->albumsOffset = 0;
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
            $this->syncFavoritedTrackIds();
        } catch (SpotifyApiException $exception) {
            $this->errorMessage = $exception->getMessage();
        }
    }

    public function loadMoreArtists(SpotifyCatalogService $catalogService): void
    {
        if (trim($this->query) === '') {
            return;
        }

        $this->errorMessage = null;
        $nextOffset = $this->artistsOffset + SpotifyCatalogService::DEV_MODE_PAGE_LIMIT;

        try {
            $results = $catalogService->search(Auth::user(), $this->query, $nextOffset, 'artist');
            $this->artists = array_values(array_merge($this->artists, $results['artists']));
            $this->artistsTotal = $results['artists_total'];
            $this->artistsOffset = $nextOffset;
        } catch (SpotifyApiException $exception) {
            $this->errorMessage = $exception->getMessage();
        }
    }

    public function loadMoreAlbums(SpotifyCatalogService $catalogService): void
    {
        if (trim($this->query) === '') {
            return;
        }

        $this->errorMessage = null;
        $nextOffset = $this->albumsOffset + SpotifyCatalogService::DEV_MODE_PAGE_LIMIT;

        try {
            $results = $catalogService->search(Auth::user(), $this->query, $nextOffset, 'album');
            $this->albums = array_values(array_merge($this->albums, $results['albums']));
            $this->albumsTotal = $results['albums_total'];
            $this->albumsOffset = $nextOffset;
        } catch (SpotifyApiException $exception) {
            $this->errorMessage = $exception->getMessage();
        }
    }

    private function performSearch(SpotifyCatalogService $catalogService): void
    {
        $this->errorMessage = null;
        $this->syncFavoritedTrackIds();

        if (trim($this->query) === '') {
            $this->tracks = [];
            $this->tracksTotal = 0;
            $this->artists = [];
            $this->artistsTotal = 0;
            $this->albums = [];
            $this->albumsTotal = 0;

            return;
        }

        try {
            $results = $catalogService->search(Auth::user(), $this->query, 0, 'track,artist,album');
            $this->tracks = $results['tracks'];
            $this->tracksTotal = $results['tracks_total'];
            $this->artists = $results['artists'];
            $this->artistsTotal = $results['artists_total'];
            $this->albums = $results['albums'];
            $this->albumsTotal = $results['albums_total'];
        } catch (SpotifyApiException $exception) {
            $this->errorMessage = $exception->getMessage();
        }
    }

    public function render(): View
    {
        return view('livewire.search-catalog');
    }
}
