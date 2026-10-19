<section class="space-y-8">
    @if ($errorMessage)
        <div role="alert" class="rounded-xl bg-red-950/70 border border-red-800 px-4 py-3 text-sm text-red-200">
            {{ $errorMessage }}
        </div>
    @endif

    @if (! empty($artist))
        <div class="flex flex-col sm:flex-row items-start sm:items-end gap-6 pb-6 border-b border-zinc-800">
            @if ($artist['image_url'] ?? null)
                <img src="{{ $artist['image_url'] }}" alt="{{ $artist['name'] }}" class="w-40 h-40 rounded-full object-cover shadow-2xl shrink-0" />
            @else
                <div class="w-40 h-40 rounded-full bg-zinc-800 flex items-center justify-center text-4xl text-zinc-500 shrink-0">♪</div>
            @endif

            <div class="space-y-2 min-w-0">
                <p class="text-xs font-semibold uppercase tracking-widest text-emerald-400">Artista</p>
                <h1 class="text-3xl font-extrabold text-white truncate">{{ $artist['name'] }}</h1>
            </div>
        </div>
    @endif

    @if (count($tracks) > 0)
        <div class="space-y-3">
            <h2 class="text-lg font-semibold text-white">Principais Faixas</h2>
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
                                <p class="text-xs text-zinc-400 truncate">{{ $track['album_name'] }}</p>
                            </div>
                        </div>

                        <span class="text-xs font-mono text-zinc-400 shrink-0">
                            {{ \App\Services\Spotify\SpotifyCatalogService::formatDuration($track['duration_ms']) }}
                        </span>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    @if (count($albums) > 0)
        <div class="space-y-3">
            <h2 class="text-lg font-semibold text-white">Discografia</h2>
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
                        <p class="text-xs text-zinc-400 truncate">{{ $album['release_year'] ?? 'Álbum' }}</p>
                    </button>
                @endforeach
            </div>
        </div>
    @endif
</section>

