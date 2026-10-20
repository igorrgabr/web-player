<section class="space-y-8">
    <div>
        <h1 class="text-2xl sm:text-3xl font-extrabold text-white">Início</h1>
        <p class="text-sm text-zinc-400">Bem-vindo ao seu Web Player pessoal conectado ao Spotify Premium.</p>
    </div>

    @if ($errorMessage)
        <div role="alert" class="rounded-xl bg-red-950/70 border border-red-800 px-4 py-3 text-sm text-red-200">
            {{ $errorMessage }}
        </div>
    @endif

    @if ($playlistNotice)
        <div role="status" class="rounded-xl bg-emerald-950/60 border border-emerald-800 px-4 py-3 text-sm text-emerald-200">
            {{ $playlistNotice }}
        </div>
    @endif

    <!-- Atalhos Rápidos: Faixas Favoritas e Playlists Locais -->
    <div class="space-y-3">
        <h2 class="text-lg font-semibold text-white">Sua Biblioteca</h2>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
            <button
                type="button"
                wire:click="$emitUp('navigateTo', 'favorites')"
                class="flex items-center gap-4 rounded-xl bg-zinc-900/70 hover:bg-zinc-800 border border-zinc-800/80 p-3 text-left transition group"
            >
                <div class="w-14 h-14 rounded-lg bg-gradient-to-br from-emerald-500 to-teal-900 flex items-center justify-center text-2xl text-white shadow shrink-0">
                    ♥
                </div>
                <div class="min-w-0">
                    <p class="text-sm font-bold text-white truncate group-hover:text-emerald-400 transition">Faixas Favoritas</p>
                    <p class="text-xs text-zinc-400">{{ $favoritesCount }} {{ $favoritesCount === 1 ? 'Faixa' : 'Faixas' }}</p>
                </div>
            </button>

            @foreach ($playlists as $pl)
                <button
                    type="button"
                    wire:click="$emitUp('navigateTo', 'playlist', '{{ $pl['id'] }}')"
                    class="flex items-center gap-4 rounded-xl bg-zinc-900/70 hover:bg-zinc-800 border border-zinc-800/80 p-3 text-left transition group"
                >
                    @if ($pl['cover_url'] ?? null)
                        <img src="{{ $pl['cover_url'] }}" alt="{{ $pl['name'] }}" class="w-14 h-14 rounded-lg object-cover shadow shrink-0" />
                    @else
                        <div class="w-14 h-14 rounded-lg bg-gradient-to-br from-indigo-600 to-zinc-800 flex items-center justify-center text-xl text-white shadow shrink-0">
                            ♫
                        </div>
                    @endif
                    <div class="min-w-0">
                        <p class="text-sm font-bold text-white truncate group-hover:text-emerald-400 transition">{{ $pl['name'] }}</p>
                        <p class="text-xs text-zinc-400 truncate">Playlist Local • {{ $pl['tracks_count'] }} {{ $pl['tracks_count'] === 1 ? 'Faixa' : 'Faixas' }}</p>
                    </div>
                </button>
            @endforeach
        </div>
    </div>

    <!-- Tocadas Recentemente (Histórico de Reprodução) -->
    <div class="space-y-3">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-lg font-semibold text-white">Tocadas Recentemente</h2>
                <p class="text-xs text-zinc-400">Seu Histórico de Reprodução mais recente.</p>
            </div>

            @if (count($tracks) > 0)
                <button
                    type="button"
                    onclick="window.__spotifyPlayerInstance?.activateElement?.()"
                    wire:click="playTrackAtIndex(0)"
                    class="rounded-full bg-emerald-500 hover:bg-emerald-400 text-zinc-950 font-bold text-xs px-4 py-2 transition"
                >
                    ▶ Reproduzir Recentes
                </button>
            @endif
        </div>

        @if (count($tracks) === 0)
            <div class="rounded-2xl bg-zinc-900/40 border border-zinc-800/70 p-8 text-center space-y-2">
                <p class="text-sm text-zinc-300 font-medium">Nenhuma Faixa no seu Histórico de Reprodução ainda.</p>
                <p class="text-xs text-zinc-500">Use a aba Buscar para encontrar e reproduzir suas primeiras Faixas.</p>
            </div>
        @else
            <div class="divide-y divide-zinc-800/60 rounded-xl bg-zinc-900/50 border border-zinc-800/80 overflow-visible">
                @foreach ($tracks as $index => $track)
                    <div class="flex items-center justify-between gap-4 px-4 py-3 hover:bg-zinc-800/60 transition">
                        <div class="flex items-center gap-3 min-w-0">
                            <button
                                type="button"
                                onclick="window.__spotifyPlayerInstance?.activateElement?.()"
                                wire:click="playTrackAtIndex({{ $index }})"
                                class="w-8 h-8 rounded-full bg-emerald-500 hover:bg-emerald-400 text-zinc-950 flex items-center justify-center font-bold text-xs shrink-0"
                                title="Reproduzir {{ $track['title'] }}"
                            >
                                ▶
                            </button>
                            @if ($track['album_cover_url'])
                                <img src="{{ $track['album_cover_url'] }}" alt="{{ $track['album_name'] }}" class="w-10 h-10 rounded object-cover shrink-0" />
                            @endif
                            <div class="truncate">
                                <p class="text-sm font-medium text-white truncate">{{ $track['title'] }}</p>
                                <p class="text-xs text-zinc-400 truncate">{{ $track['artist_name'] }} • {{ $track['album_name'] }}</p>
                            </div>
                        </div>

                        <x-track-row-actions
                            :index="$index"
                            :track="$track"
                            :favorited-ids="$favoritedTrackIds"
                            :playlists="$userPlaylists"
                        />
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</section>

