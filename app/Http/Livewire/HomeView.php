<?php

namespace App\Http\Livewire;

use App\Http\Livewire\Concerns\PlaysNormalizedTrackLists;
use App\Models\LocalPlaylist;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class HomeView extends Component
{
    use PlaysNormalizedTrackLists;

    public const RECENT_TRACKS_LIMIT = 15;

    public int $favoritesCount = 0;
    public array $playlists = [];
    public array $tracks = [];
    public ?string $errorMessage = null;

    protected $listeners = [
        'refreshFavoritesState' => 'loadHomeData',
        'playlistsUpdated' => 'loadHomeData',
        'playHistoryUpdated' => 'loadHomeData',
    ];

    public function mount(): void
    {
        $this->loadHomeData();
    }

    public function loadHomeData(): void
    {
        $user = Auth::user();
        if (! $user) {
            $this->favoritesCount = 0;
            $this->playlists = [];
            $this->tracks = [];
            $this->favoritedTrackIds = [];
            $this->userPlaylists = [];

            return;
        }

        $this->syncUserLibraryState();
        $this->favoritesCount = count($this->favoritedTrackIds);

        $this->playlists = $user->localPlaylists()
            ->withCount('playlistTracks')
            ->get()
            ->map(fn (LocalPlaylist $pl) => [
                'id' => (string) $pl->id,
                'name' => $pl->name,
                'description' => $pl->description,
                'cover_url' => $pl->cover_url,
                'tracks_count' => (int) $pl->playlist_tracks_count,
            ])
            ->values()
            ->all();

        $this->tracks = $user->playHistories()
            ->with('track')
            ->limit(self::RECENT_TRACKS_LIMIT)
            ->get()
            ->map(function ($entry) {
                $track = $entry->track;
                if (! $track) {
                    return null;
                }

                return array_merge($track->toNormalizedArray(), [
                    'played_at' => optional($entry->played_at)->format('d/m/Y H:i'),
                ]);
            })
            ->filter()
            ->values()
            ->all();
    }

    protected function playbackContextType(): string
    {
        return 'history';
    }

    public function render(): View
    {
        return view('livewire.home-view');
    }
}
