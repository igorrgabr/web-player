<?php

namespace App\Http\Livewire;

use App\Exceptions\SpotifyApiException;
use App\Http\Livewire\Concerns\PlaysNormalizedTrackLists;
use App\Services\Spotify\SpotifyCatalogService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class ArtistView extends Component
{
    use PlaysNormalizedTrackLists;

    public string $artistId = '';
    public array $artist = [];
    public array $tracks = [];
    public array $albums = [];
    public ?string $errorMessage = null;

    protected $listeners = [
        'refreshFavoritesState' => 'syncFavoritedTrackIds',
        'playlistsUpdated' => 'syncFavoritedTrackIds',
    ];

    public function mount(string $artistId, SpotifyCatalogService $catalogService): void
    {
        $this->artistId = $artistId;
        $this->syncFavoritedTrackIds();
        $this->loadArtist($catalogService);
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
