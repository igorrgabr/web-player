<section class="space-y-8">
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

    @if (! empty($album))
        <div class="flex flex-col sm:flex-row items-start sm:items-end gap-6 pb-6 border-b border-zinc-800">
            @if ($album['cover_url'] ?? null)
                <img src="{{ $album['cover_url'] }}" alt="{{ $album['name'] }}" class="w-44 h-44 rounded-xl object-cover shadow-2xl shrink-0" />
            @else
                <div class="w-44 h-44 rounded-xl bg-zinc-800 flex items-center justify-center text-4xl text-zinc-500 shrink-0">♪</div>
            @endif

            <div class="space-y-2 min-w-0">
                <p class="text-xs font-semibold uppercase tracking-widest text-emerald-400">Álbum</p>
                <h1 class="text-3xl font-extrabold text-white truncate">{{ $album['name'] }}</h1>
                <div class="flex items-center gap-2 text-sm text-zinc-300">
                    @if ($album['spotify_artist_id'] ?? null)
                        <button
                            type="button"
                            wire:click="$emitUp('navigateTo', 'artist', '{{ $album['spotify_artist_id'] }}')"
                            class="font-semibold hover:text-emerald-400 hover:underline transition"
                        >
                            {{ $album['artist_name'] }}
                        </button>
                    @else
                        <span class="font-semibold">{{ $album['artist_name'] }}</span>
                    @endif
                    @if ($album['release_year'] ?? null)
                        <span class="text-zinc-500">•</span>
                        <span class="text-zinc-400">{{ $album['release_year'] }}</span>
                    @endif
                    <span class="text-zinc-500">•</span>
                    <span class="text-zinc-400">{{ count($tracks) }} Faixas</span>
                </div>

                @if (count($tracks) > 0)
                    <div class="pt-2">
                        <button
                            type="button"
                            onclick="window.__spotifyPlayerInstance?.activateElement?.()"
                            wire:click="playTrackAtIndex(0)"
                            class="inline-flex items-center gap-2 rounded-full bg-emerald-500 hover:bg-emerald-400 text-zinc-950 font-bold text-sm px-6 py-2.5 transition shadow-lg"
                        >
                            <span>▶ Reproduzir Álbum</span>
                        </button>
                    </div>
                @endif
            </div>
        </div>
    @endif

    @if (count($tracks) > 0)
        <div class="divide-y divide-zinc-800/60 rounded-xl bg-zinc-900/50 border border-zinc-800/80 overflow-hidden">
            @foreach ($tracks as $index => $track)
                <div class="flex items-center justify-between gap-4 px-4 py-3 hover:bg-zinc-800/60 transition">
                    <div class="flex items-center gap-4 min-w-0">
                        <span class="w-5 text-right text-xs font-mono text-zinc-500 shrink-0">{{ $index + 1 }}</span>
                        <button
                            type="button"
                            onclick="window.__spotifyPlayerInstance?.activateElement?.()"
                            wire:click="playTrackAtIndex({{ $index }})"
                            class="w-8 h-8 rounded-full bg-emerald-500 hover:bg-emerald-400 text-zinc-950 flex items-center justify-center font-bold text-xs shrink-0"
                            title="Reproduzir {{ $track['title'] }}"
                        >
                            ▶
                        </button>
                        <div class="truncate">
                            <p class="text-sm font-medium text-white truncate">{{ $track['title'] }}</p>
                            <p class="text-xs text-zinc-400 truncate">{{ $track['artist_name'] }}</p>
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
</section>

