<div class="flex-1 flex flex-col md:flex-row overflow-hidden">
    <!-- Barra de Navegação Mobile -->
    <header class="md:hidden bg-black border-b border-zinc-900 px-4 py-3 space-y-2 shrink-0">
        <div class="flex items-center justify-between gap-2">
            <div class="flex items-center gap-2">
                <div class="w-7 h-7 rounded-full bg-emerald-500 flex items-center justify-center text-zinc-950 font-bold text-xs">
                    W
                </div>
                <span class="font-bold text-sm text-white">Web Player</span>
            </div>
            <div class="flex items-center gap-2">
                <button
                    type="button"
                    wire:click="$toggle('showCreatePlaylistForm')"
                    class="px-2.5 py-1 rounded-lg text-xs font-semibold text-emerald-400 border border-zinc-800"
                >
                    + Playlist Local
                </button>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="px-2 py-1 text-xs text-zinc-400 hover:text-red-400 transition">
                        Sair
                    </button>
                </form>
            </div>
        </div>
        <nav class="flex items-center gap-1 overflow-x-auto" aria-label="Navegação Mobile">
            <button
                type="button"
                wire:click="navigateTo('home')"
                class="px-2.5 py-1.5 rounded-lg text-xs font-medium shrink-0 {{ $activeView === 'home' ? 'bg-zinc-800 text-white' : 'text-zinc-400' }}"
            >
                Início
            </button>
            <button
                type="button"
                wire:click="navigateTo('search')"
                class="px-2.5 py-1.5 rounded-lg text-xs font-medium shrink-0 {{ $activeView === 'search' ? 'bg-zinc-800 text-white' : 'text-zinc-400' }}"
            >
                Buscar
            </button>
            <button
                type="button"
                wire:click="navigateTo('favorites')"
                class="px-2.5 py-1.5 rounded-lg text-xs font-medium shrink-0 {{ $activeView === 'favorites' ? 'bg-zinc-800 text-white' : 'text-zinc-400' }}"
            >
                Favoritas
            </button>
        </nav>

        @if ($showCreatePlaylistForm)
            <form wire:submit.prevent="createPlaylist" class="space-y-2 rounded-xl bg-zinc-900/95 border border-zinc-800 p-3">
                <input
                    type="text"
                    wire:model.defer="newPlaylistName"
                    placeholder="Nome da Playlist Local"
                    required
                    maxlength="120"
                    class="w-full rounded-lg bg-zinc-950 border border-zinc-700 px-2.5 py-1.5 text-xs text-white placeholder-zinc-500 focus:border-emerald-500 focus:outline-none"
                />
                <input
                    type="text"
                    wire:model.defer="newPlaylistDescription"
                    placeholder="Descrição (opcional)"
                    maxlength="500"
                    class="w-full rounded-lg bg-zinc-950 border border-zinc-700 px-2.5 py-1.5 text-xs text-white placeholder-zinc-500 focus:border-emerald-500 focus:outline-none"
                />
                <div class="flex items-center justify-end gap-2">
                    <button type="button" wire:click="$set('showCreatePlaylistForm', false)" class="text-xs text-zinc-400 px-2 py-1">Cancelar</button>
                    <button type="submit" class="rounded-full bg-emerald-500 text-zinc-950 font-semibold text-xs px-3 py-1">Salvar</button>
                </div>
            </form>
        @endif
    </header>

    <!-- Barra Lateral de Navegação (Desktop) -->
    <aside class="hidden md:flex w-64 bg-black border-r border-zinc-900 p-5 flex-col justify-between shrink-0 overflow-y-auto">
        <div class="space-y-6">
            <div class="flex items-center gap-3 px-2">
                <div class="w-8 h-8 rounded-full bg-emerald-500 flex items-center justify-center text-zinc-950 font-bold">
                    W
                </div>
                <span class="font-bold tracking-tight text-white">Web Player</span>
            </div>

            <nav class="space-y-1" aria-label="Navegação Principal">
                <button
                    type="button"
                    wire:click="navigateTo('home')"
                    class="w-full flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition {{ $activeView === 'home' ? 'bg-zinc-800 text-white' : 'text-zinc-400 hover:text-white hover:bg-zinc-900' }}"
                >
                    <span>Início</span>
                </button>

                <button
                    type="button"
                    wire:click="navigateTo('search')"
                    class="w-full flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition {{ $activeView === 'search' ? 'bg-zinc-800 text-white' : 'text-zinc-400 hover:text-white hover:bg-zinc-900' }}"
                >
                    <span>Buscar</span>
                </button>

                <button
                    type="button"
                    wire:click="navigateTo('favorites')"
                    class="w-full flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition {{ $activeView === 'favorites' ? 'bg-zinc-800 text-white' : 'text-zinc-400 hover:text-white hover:bg-zinc-900' }}"
                >
                    <span>Faixas Favoritas</span>
                </button>
            </nav>

            <!-- Seção de Playlists Locais -->
            <div class="pt-4 border-t border-zinc-900 space-y-3">
                <div class="flex items-center justify-between px-2">
                    <span class="text-xs font-semibold uppercase tracking-wider text-zinc-400">Playlists Locais</span>
                    <button
                        type="button"
                        wire:click="$toggle('showCreatePlaylistForm')"
                        class="text-xs font-semibold text-emerald-400 hover:text-emerald-300 transition"
                        title="Nova Playlist Local"
                    >
                        + Criar
                    </button>
                </div>

                @if ($showCreatePlaylistForm)
                    <form wire:submit.prevent="createPlaylist" class="space-y-2 rounded-xl bg-zinc-900/90 border border-zinc-800 p-3">
                        <input
                            type="text"
                            wire:model.defer="newPlaylistName"
                            placeholder="Nome da Playlist Local"
                            required
                            maxlength="120"
                            class="w-full rounded-lg bg-zinc-950 border border-zinc-700 px-2.5 py-1.5 text-xs text-white placeholder-zinc-500 focus:border-emerald-500 focus:outline-none"
                        />
                        @error('newPlaylistName')
                            <p class="text-[11px] text-red-400">{{ $message }}</p>
                        @enderror
                        <input
                            type="text"
                            wire:model.defer="newPlaylistDescription"
                            placeholder="Descrição (opcional)"
                            maxlength="500"
                            class="w-full rounded-lg bg-zinc-950 border border-zinc-700 px-2.5 py-1.5 text-xs text-white placeholder-zinc-500 focus:border-emerald-500 focus:outline-none"
                        />
                        <div class="flex items-center justify-end gap-2 pt-1">
                            <button
                                type="button"
                                wire:click="$set('showCreatePlaylistForm', false)"
                                class="text-xs text-zinc-400 hover:text-white px-2 py-1"
                            >
                                Cancelar
                            </button>
                            <button
                                type="submit"
                                class="rounded-full bg-emerald-500 hover:bg-emerald-400 text-zinc-950 font-semibold text-xs px-3 py-1 transition"
                            >
                                Salvar
                            </button>
                        </div>
                    </form>
                @endif

                <div class="space-y-1 max-h-56 overflow-y-auto">
                    @forelse ($playlists as $pl)
                        <button
                            type="button"
                            wire:click="navigateTo('playlist', '{{ $pl['id'] }}')"
                            class="w-full text-left px-3 py-2 rounded-lg text-xs font-medium truncate transition {{ $activeView === 'playlist' && (string) $contextId === (string) $pl['id'] ? 'bg-zinc-800 text-white' : 'text-zinc-400 hover:text-white hover:bg-zinc-900' }}"
                        >
                            {{ $pl['name'] }}
                        </button>
                    @empty
                        <p class="px-2 text-xs text-zinc-600">Nenhuma Playlist Local criada.</p>
                    @endforelse
                </div>
            </div>
        </div>

        <div class="pt-4 border-t border-zinc-900 flex items-center justify-between px-2">
            <div class="truncate">
                <p class="text-xs text-zinc-400">Ouvinte</p>
                <p class="text-sm font-medium text-zinc-200 truncate">{{ auth()->user()->name }}</p>
            </div>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="text-xs text-zinc-400 hover:text-red-400 transition">
                    Sair
                </button>
            </form>
        </div>
    </aside>

    <!-- Área Central Dinâmica -->
    <main class="flex-1 overflow-y-auto bg-gradient-to-b from-zinc-900 to-zinc-950 p-4 sm:p-8">
        @if ($activeView === 'home')
            @livewire('home-view')
        @elseif ($activeView === 'search')
            @livewire('search-catalog')
        @elseif ($activeView === 'favorites')
            @livewire('favorites-library')
        @elseif ($activeView === 'playlist')
            @livewire('playlist-view', ['playlistId' => (string) $contextId], key('playlist-' . $contextId))
        @elseif ($activeView === 'album')
            @livewire('album-view', ['albumId' => (string) $contextId], key('album-' . $contextId))
        @elseif ($activeView === 'artist')
            @livewire('artist-view', ['artistId' => (string) $contextId], key('artist-' . $contextId))
        @endif
    </main>
</div>
