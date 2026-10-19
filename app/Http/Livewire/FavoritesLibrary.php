<?php

namespace App\Http\Livewire;

use App\Http\Livewire\Concerns\PlaysNormalizedTrackLists;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class FavoritesLibrary extends Component
{
    use PlaysNormalizedTrackLists {
        toggleFavoriteAtIndex as traitToggleFavoriteAtIndex;
    }

    public array $tracks = [];
    public ?string $errorMessage = null;

    protected $listeners = [
        'refreshFavoritesState' => 'loadFavorites',
    ];

    public function mount(): void
    {
        $this->loadFavorites();
    }

    public function toggleFavoriteAtIndex(int $index): void
    {
        $this->traitToggleFavoriteAtIndex($index);
        $this->loadFavorites();
    }

    public function loadFavorites(): void
    {
        $user = Auth::user();
        if (! $user) {
            $this->tracks = [];
            $this->favoritedTrackIds = [];

            return;
        }

        $favorites = $user->favoriteTracks()->with('track')->get();

        $this->tracks = $favorites
            ->map(function ($fav) {
                $track = $fav->track;
                if (! $track) {
                    return null;
                }

                return [
                    'spotify_track_id' => $track->spotify_track_id,
                    'spotify_track_uri' => $track->spotify_track_uri,
                    'title' => $track->title,
                    'artist_name' => $track->artist_name,
                    'spotify_artist_id' => $track->spotify_artist_id,
                    'album_name' => $track->album_name,
                    'spotify_album_id' => $track->spotify_album_id,
                    'album_cover_url' => $track->album_cover_url,
                    'duration_ms' => (int) $track->duration_ms,
                    'favorited_at' => optional($fav->favorited_at)->format('d/m/Y H:i'),
                ];
            })
            ->filter()
            ->values()
            ->all();

        $this->favoritedTrackIds = array_values(array_map(fn ($t) => $t['spotify_track_id'], $this->tracks));
    }

    public function render(): View
    {
        return view('livewire.favorites-library');
    }
}
