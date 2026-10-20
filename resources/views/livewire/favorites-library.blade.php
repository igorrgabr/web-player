<section class="space-y-8">
    <div class="flex flex-col sm:flex-row items-start sm:items-end justify-between gap-6 pb-6 border-b border-zinc-800">
        <div class="flex items-end gap-5">
            <div class="w-32 h-32 rounded-2xl bg-gradient-to-br from-emerald-500 to-teal-900 flex items-center justify-center text-5xl text-white shadow-2xl shrink-0">
                ♥
            </div>
            <div class="space-y-1">
                <p class="text-xs font-semibold uppercase tracking-widest text-emerald-400">Biblioteca Pessoal</p>
                <h1 class="text-3xl font-extrabold text-white">Faixas Favoritas</h1>
                <p class="text-sm text-zinc-400">
                    Sua coleção pessoal de Faixas curtidas • {{ count($tracks) }} {{ count($tracks) === 1 ? 'Faixa' : 'Faixas' }}
                </p>
            </div>
        </div>

        @if (count($tracks) > 0)
            <button
                type="button"
                onclick="window.__spotifyPlayerInstance?.activateElement?.()"
                wire:click="playTrackAtIndex(0)"
                class="inline-flex items-center gap-2 rounded-full bg-emerald-500 hover:bg-emerald-400 text-zinc-950 font-bold text-sm px-6 py-2.5 transition shadow-lg"
            >
                <span>▶ Reproduzir Faixas Favoritas</span>
            </button>
        @endif
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

    @if (count($tracks) === 0)
        <div class="rounded-2xl bg-zinc-900/40 border border-zinc-800/70 p-10 text-center space-y-3">
            <p class="text-zinc-300 font-medium">Você ainda não adicionou nenhuma Faixa Favorita.</p>
            <p class="text-xs text-zinc-500">Toque no ícone de coração (♡) em qualquer Faixa da busca, álbum, artista ou no rodapé do player.</p>
        </div>
    @else
        <div class="divide-y divide-zinc-800/60 rounded-xl bg-zinc-900/50 border border-zinc-800/80 overflow-hidden">
            @foreach ($tracks as $index => $track)
                <div class="flex items-center justify-between gap-4 px-4 py-3 hover:bg-zinc-800/60 transition">
                    <div class="flex items-center gap-3 min-w-0">
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
</section>

