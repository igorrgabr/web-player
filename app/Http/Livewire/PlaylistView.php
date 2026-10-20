<?php

namespace App\Http\Livewire;

use App\Http\Livewire\Concerns\PlaysNormalizedTrackLists;
use App\Models\LocalPlaylist;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class PlaylistView extends Component
{
    use PlaysNormalizedTrackLists;

    public string $playlistId = '';
    public array $playlist = [];
    public array $tracks = [];
    public bool $isEditing = false;
    public string $editName = '';
    public string $editDescription = '';
    public ?string $errorMessage = null;

    protected $listeners = [
        'refreshFavoritesState' => 'syncFavoritedTrackIds',
        'playlistsUpdated' => 'loadPlaylist',
    ];

    public function mount(string $playlistId): void
    {
        $this->playlistId = $playlistId;
        $this->loadPlaylist();
    }

    public function loadPlaylist(): void
    {
        $this->errorMessage = null;
        $this->syncFavoritedTrackIds();

        $model = $this->findOwnedPlaylist();
        if (! $model) {
            $this->playlist = [];
            $this->tracks = [];
            $this->errorMessage = 'Playlist Local não encontrada.';

            return;
        }

        $this->playlist = [
            'id' => (int) $model->id,
            'name' => $model->name,
            'description' => $model->description,
        ];

        if (! $this->isEditing) {
            $this->editName = $model->name;
            $this->editDescription = (string) ($model->description ?? '');
        }

        $this->tracks = $model->playlistTracks()
            ->with('track')
            ->get()
            ->map(function ($item) {
                $track = $item->track;
                if (! $track) {
                    return null;
                }

                return array_merge($track->toNormalizedArray(), [
                    'position' => (int) $item->position,
                ]);
            })
            ->filter()
            ->values()
            ->all();
    }

    public function updatePlaylist(): void
    {
        $model = $this->findOwnedPlaylist();
        if (! $model) {
            $this->errorMessage = 'Playlist Local não encontrada.';

            return;
        }

        $validated = $this->validate([
            'editName' => ['required', 'string', 'max:120'],
            'editDescription' => ['nullable', 'string', 'max:500'],
        ]);

        $model->update([
            'name' => trim($validated['editName']),
            'description' => isset($validated['editDescription']) && trim($validated['editDescription']) !== ''
                ? trim($validated['editDescription'])
                : null,
        ]);

        $this->isEditing = false;
        $this->loadPlaylist();
        $this->emit('playlistsUpdated');
    }

    public function deletePlaylist(): void
    {
        $model = $this->findOwnedPlaylist();
        if (! $model) {
            $this->errorMessage = 'Playlist Local não encontrada.';

            return;
        }

        $model->delete();
        $this->emit('playlistsUpdated');
        $this->emit('navigateTo', 'home');
    }

    public function removeTrackAtIndex(int $index): void
    {
        if (! isset($this->tracks[$index])) {
            return;
        }

        $model = $this->findOwnedPlaylist();
        if (! $model) {
            return;
        }

        $spotifyTrackId = (string) ($this->tracks[$index]['spotify_track_id'] ?? '');
        if ($spotifyTrackId !== '') {
            $model->removeTrackBySpotifyId($spotifyTrackId);
            $this->loadPlaylist();
        }
    }

    public function moveTrackUp(int $index): void
    {
        $model = $this->findOwnedPlaylist();
        if (! $model) {
            return;
        }

        $model->moveTrackPosition($index, 'up');
        $this->loadPlaylist();
    }

    public function moveTrackDown(int $index): void
    {
        $model = $this->findOwnedPlaylist();
        if (! $model) {
            return;
        }

        $model->moveTrackPosition($index, 'down');
        $this->loadPlaylist();
    }

    private function findOwnedPlaylist(): ?LocalPlaylist
    {
        $user = Auth::user();
        if (! $user || trim($this->playlistId) === '' || ! ctype_digit((string) $this->playlistId)) {
            return null;
        }

        return LocalPlaylist::where('id', (int) $this->playlistId)
            ->where('user_id', $user->id)
            ->first();
    }

    public function render(): View
    {
        return view('livewire.playlist-view');
    }
}
