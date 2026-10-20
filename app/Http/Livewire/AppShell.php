<?php

namespace App\Http\Livewire;

use App\Models\LocalPlaylist;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class AppShell extends Component
{
    public const ALLOWED_VIEWS = [
        'home',
        'search',
        'favorites',
        'playlist',
        'album',
        'artist',
    ];

    public string $activeView = 'home';
    public ?string $contextId = null;

    public array $playlists = [];
    public bool $showCreatePlaylistForm = false;
    public string $newPlaylistName = '';
    public string $newPlaylistDescription = '';

    protected $queryString = [
        'activeView' => ['except' => 'home', 'as' => 'view'],
        'contextId' => ['except' => null, 'as' => 'id'],
    ];

    protected $listeners = [
        'navigateTo',
        'playlistsUpdated' => 'loadPlaylists',
    ];

    public function mount(): void
    {
        if (! in_array($this->activeView, self::ALLOWED_VIEWS, true)) {
            $this->activeView = 'home';
        }

        $this->loadPlaylists();
    }

    public function loadPlaylists(): void
    {
        $user = Auth::user();
        if (! $user) {
            $this->playlists = [];

            return;
        }

        $this->playlists = $user->localPlaylists()
            ->get(['id', 'name', 'description'])
            ->map(fn (LocalPlaylist $pl) => [
                'id' => (string) $pl->id,
                'name' => $pl->name,
                'description' => $pl->description,
            ])
            ->values()
            ->all();
    }

    public function createPlaylist(): void
    {
        $validated = $this->validate([
            'newPlaylistName' => ['required', 'string', 'max:120'],
            'newPlaylistDescription' => ['nullable', 'string', 'max:500'],
        ]);

        $playlist = LocalPlaylist::create([
            'user_id' => Auth::id(),
            'name' => trim($validated['newPlaylistName']),
            'description' => isset($validated['newPlaylistDescription']) && trim($validated['newPlaylistDescription']) !== ''
                ? trim($validated['newPlaylistDescription'])
                : null,
        ]);

        $this->newPlaylistName = '';
        $this->newPlaylistDescription = '';
        $this->showCreatePlaylistForm = false;

        $this->loadPlaylists();
        $this->emit('playlistsUpdated');
        $this->navigateTo('playlist', (string) $playlist->id);
    }

    public function navigateTo(string $view, ?string $contextId = null): void
    {
        if (! in_array($view, self::ALLOWED_VIEWS, true)) {
            $this->activeView = 'home';
            $this->contextId = null;

            return;
        }

        $this->activeView = $view;
        $this->contextId = $contextId;
    }

    public function render(): View
    {
        return view('livewire.app-shell');
    }
}
