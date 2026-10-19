<?php

namespace App\Http\Livewire;

use App\Exceptions\SpotifyApiException;
use App\Http\Livewire\Concerns\PlaysNormalizedTrackLists;
use App\Services\Spotify\SpotifyCatalogService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class AlbumView extends Component
{
    use PlaysNormalizedTrackLists;

    public string $albumId = '';
    public array $album = [];
    public array $tracks = [];
    public ?string $errorMessage = null;

    public function mount(string $albumId, SpotifyCatalogService $catalogService): void
    {
        $this->albumId = $albumId;
        $this->loadAlbum($catalogService);
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
