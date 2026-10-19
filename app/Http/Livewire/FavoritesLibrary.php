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

                return array_merge($track->toNormalizedArray(), [
                    'favorited_at' => optional($fav->favorited_at)->format('d/m/Y H:i'),
                ]);
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

