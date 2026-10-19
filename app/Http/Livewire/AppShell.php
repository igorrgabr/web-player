<?php

namespace App\Http\Livewire;

use Illuminate\Contracts\View\View;
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

    protected $queryString = [
        'activeView' => ['except' => 'home', 'as' => 'view'],
        'contextId' => ['except' => null, 'as' => 'id'],
    ];

    protected $listeners = [
        'navigateTo',
    ];

    public function mount(): void
    {
        if (! in_array($this->activeView, self::ALLOWED_VIEWS, true)) {
            $this->activeView = 'home';
        }
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
