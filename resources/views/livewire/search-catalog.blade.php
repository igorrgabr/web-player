<section class="space-y-8">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-white">Pesquisar no catálogo</h1>
            <p class="text-sm text-zinc-400">Busque Faixas, Artistas e Álbuns do catálogo do Spotify.</p>
        </div>

        <div class="w-full sm:w-96 relative">
            <input
                type="search"
                wire:model.debounce.400ms="query"
                placeholder="O que você quer ouvir hoje?"
                class="w-full rounded-full bg-zinc-900 border border-zinc-700 focus:border-emerald-500 focus:outline-none px-5 py-2.5 text-sm text-white placeholder-zinc-500"
            />
        </div>
    </div>

    @if ($errorMessage)
        <div role="alert" class="rounded-xl bg-red-950/70 border border-red-800 px-4 py-3 text-sm text-red-200">
            {{ $errorMessage }}
        </div>
    @endif

    @if (count($tracks) > 0)
        <div class="space-y-3">
            <h2 class="text-lg font-semibold text-white">Faixas</h2>
            <div class="divide-y divide-zinc-800/60 rounded-xl bg-zinc-900/50 border border-zinc-800/80 overflow-hidden">
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

                        <div class="flex items-center gap-4 shrink-0">
                            @php($isFav = in_array($track['spotify_track_id'], $favoritedTrackIds ?? [], true))
                            <button
                                type="button"
                                wire:click="toggleFavoriteAtIndex({{ $index }})"
                                class="text-base transition {{ $isFav ? 'text-emerald-400' : 'text-zinc-500 hover:text-white' }}"
                                title="{{ $isFav ? 'Remover das Faixas Favoritas' : 'Adicionar às Faixas Favoritas' }}"
                            >
                                {{ $isFav ? '♥' : '♡' }}
                            </button>
                            <span class="text-xs font-mono text-zinc-400">
                                {{ \App\Services\Spotify\SpotifyCatalogService::formatDuration($track['duration_ms']) }}
                            </span>
                        </div>
                    </div>
                @endforeach
            </div>

            @if (count($tracks) < $tracksTotal)
                <div class="pt-2">
                    <button
                        type="button"
                        wire:click="loadMoreTracks"
                        class="rounded-full border border-zinc-700 hover:border-zinc-500 text-xs font-medium text-zinc-200 px-5 py-2 transition"
                    >
                        Carregar mais Faixas
                    </button>
                </div>
            @endif
        </div>
    @endif

    @if (count($artists) > 0)
        <div class="space-y-3">
            <h2 class="text-lg font-semibold text-white">Artistas</h2>
            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-5 gap-4">
                @foreach ($artists as $artist)
                    <button
                        type="button"
                        wire:click="$emitUp('navigateTo', 'artist', '{{ $artist['spotify_artist_id'] }}')"
                        class="group text-left p-4 rounded-xl bg-zinc-900/60 hover:bg-zinc-800/80 border border-zinc-800/60 transition flex flex-col items-center text-center"
                    >
                        @if ($artist['image_url'])
                            <img src="{{ $artist['image_url'] }}" alt="{{ $artist['name'] }}" class="w-24 h-24 rounded-full object-cover mb-3 shadow-lg" />
                        @else
                            <div class="w-24 h-24 rounded-full bg-zinc-800 flex items-center justify-center text-zinc-500 mb-3">♪</div>
                        @endif
                        <p class="text-sm font-semibold text-white truncate w-full">{{ $artist['name'] }}</p>
                        <p class="text-xs text-zinc-400">Artista</p>
                    </button>
                @endforeach
            </div>

            @if (count($artists) < $artistsTotal)
                <div class="pt-2">
                    <button
                        type="button"
                        wire:click="loadMoreArtists"
                        class="rounded-full border border-zinc-700 hover:border-zinc-500 text-xs font-medium text-zinc-200 px-5 py-2 transition"
                    >
                        Carregar mais Artistas
                    </button>
                </div>
            @endif
        </div>
    @endif

    @if (count($albums) > 0)
        <div class="space-y-3">
            <h2 class="text-lg font-semibold text-white">Álbuns</h2>
            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-5 gap-4">
                @foreach ($albums as $album)
                    <button
                        type="button"
                        wire:click="$emitUp('navigateTo', 'album', '{{ $album['spotify_album_id'] }}')"
                        class="group text-left p-4 rounded-xl bg-zinc-900/60 hover:bg-zinc-800/80 border border-zinc-800/60 transition"
                    >
                        @if ($album['cover_url'])
                            <img src="{{ $album['cover_url'] }}" alt="{{ $album['name'] }}" class="w-full aspect-square rounded-lg object-cover mb-3 shadow-lg" />
                        @else
                            <div class="w-full aspect-square rounded-lg bg-zinc-800 flex items-center justify-center text-zinc-500 mb-3">♪</div>
                        @endif
                        <p class="text-sm font-semibold text-white truncate">{{ $album['name'] }}</p>
                        <p class="text-xs text-zinc-400 truncate">{{ $album['artist_name'] }} @if($album['release_year']) • {{ $album['release_year'] }} @endif</p>
                    </button>
                @endforeach
            </div>

            @if (count($albums) < $albumsTotal)
                <div class="pt-2">
                    <button
                        type="button"
                        wire:click="loadMoreAlbums"
                        class="rounded-full border border-zinc-700 hover:border-zinc-500 text-xs font-medium text-zinc-200 px-5 py-2 transition"
                    >
                        Carregar mais Álbuns
                    </button>
                </div>
            @endif
        </div>
    @endif
</section>
