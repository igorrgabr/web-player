@props([
    'index',
    'track',
    'favoritedIds' => [],
    'playlists' => [],
])

@php($isFav = in_array($track['spotify_track_id'], $favoritedIds ?? [], true))

<div class="flex items-center gap-3 shrink-0">
    @if (count($playlists) > 0)
        <div x-data="{ open: false }" class="relative">
            <button
                type="button"
                @click="open = !open"
                class="text-xs px-2 py-1 rounded-lg border border-zinc-700 hover:border-zinc-500 text-zinc-300 hover:text-white transition"
                title="Adicionar à Playlist Local"
            >
                + Playlist
            </button>
            <div
                x-show="open"
                @click.outside="open = false"
                class="absolute right-0 mt-1 w-48 rounded-xl bg-zinc-900 border border-zinc-800 shadow-2xl p-1.5 z-40"
                style="display: none;"
            >
                <p class="px-2 py-1 text-[10px] font-semibold uppercase tracking-wider text-zinc-500">
                    Adicionar à Playlist Local
                </p>
                @foreach ($playlists as $pl)
                    <button
                        type="button"
                        @click="open = false"
                        wire:click="addTrackToPlaylist({{ $index }}, {{ (int) $pl['id'] }})"
                        class="w-full text-left px-2.5 py-1.5 rounded-lg text-xs text-zinc-200 hover:bg-zinc-800 truncate transition"
                    >
                        {{ $pl['name'] }}
                    </button>
                @endforeach
            </div>
        </div>
    @endif

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

