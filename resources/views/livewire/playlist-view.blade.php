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

    @if (! empty($playlist))
        <div class="flex flex-col sm:flex-row items-start sm:items-end justify-between gap-6 pb-6 border-b border-zinc-800">
            <div class="flex items-end gap-5 min-w-0">
                <div class="w-32 h-32 rounded-2xl bg-gradient-to-br from-indigo-600 to-zinc-900 flex items-center justify-center text-4xl text-white shadow-2xl shrink-0">
                    ♫
                </div>
                <div class="space-y-1.5 min-w-0">
                    <p class="text-xs font-semibold uppercase tracking-widest text-emerald-400">Playlist Local</p>
                    <h1 class="text-3xl font-extrabold text-white truncate">{{ $playlist['name'] }}</h1>
                    @if ($playlist['description'])
                        <p class="text-sm text-zinc-300">{{ $playlist['description'] }}</p>
                    @endif
                    <p class="text-xs text-zinc-400">
                        {{ count($tracks) }} {{ count($tracks) === 1 ? 'Faixa' : 'Faixas' }}
                    </p>
                </div>
            </div>

            <div class="flex flex-wrap items-center gap-3 shrink-0">
                @if (count($tracks) > 0)
                    <button
                        type="button"
                        onclick="window.__spotifyPlayerInstance?.activateElement?.()"
                        wire:click="playTrackAtIndex(0)"
                        class="inline-flex items-center gap-2 rounded-full bg-emerald-500 hover:bg-emerald-400 text-zinc-950 font-bold text-sm px-6 py-2.5 transition shadow-lg"
                    >
                        <span>▶ Reproduzir Playlist Local</span>
                    </button>
                @endif

                <button
                    type="button"
                    wire:click="$toggle('isEditing')"
                    class="rounded-full border border-zinc-700 hover:border-zinc-500 text-xs font-medium text-zinc-200 px-4 py-2 transition"
                >
                    Editar
                </button>

                <button
                    type="button"
                    wire:click="deletePlaylist"
                    class="rounded-full border border-red-900/80 hover:border-red-600 text-xs font-medium text-red-300 hover:text-red-200 px-4 py-2 transition"
                >
                    Excluir
                </button>
            </div>
        </div>

        @if ($isEditing)
            <form wire:submit.prevent="updatePlaylist" class="rounded-2xl bg-zinc-900/80 border border-zinc-800 p-5 space-y-4 max-w-xl">
                <h2 class="text-sm font-semibold text-white">Editar Playlist Local</h2>
                <div class="space-y-1">
                    <label class="text-xs text-zinc-400">Nome</label>
                    <input
                        type="text"
                        wire:model.defer="editName"
                        required
                        maxlength="120"
                        class="w-full rounded-lg bg-zinc-950 border border-zinc-700 px-3 py-2 text-sm text-white focus:border-emerald-500 focus:outline-none"
                    />
                    @error('editName')
                        <p class="text-xs text-red-400">{{ $message }}</p>
                    @enderror
                </div>
                <div class="space-y-1">
                    <label class="text-xs text-zinc-400">Descrição (opcional)</label>
                    <textarea
                        wire:model.defer="editDescription"
                        rows="2"
                        maxlength="500"
                        class="w-full rounded-lg bg-zinc-950 border border-zinc-700 px-3 py-2 text-sm text-white focus:border-emerald-500 focus:outline-none"
                    ></textarea>
                </div>
                <div class="flex items-center justify-end gap-2">
                    <button
                        type="button"
                        wire:click="$set('isEditing', false)"
                        class="rounded-full border border-zinc-700 text-xs text-zinc-300 px-4 py-1.5"
                    >
                        Cancelar
                    </button>
                    <button
                        type="submit"
                        class="rounded-full bg-emerald-500 hover:bg-emerald-400 text-zinc-950 font-semibold text-xs px-5 py-1.5 transition"
                    >
                        Salvar Alterações
                    </button>
                </div>
            </form>
        @endif

        @if (count($tracks) === 0)
            <div class="rounded-2xl bg-zinc-900/40 border border-zinc-800/70 p-10 text-center space-y-2">
                <p class="text-zinc-300 font-medium">Esta Playlist Local ainda não possui Faixas.</p>
                <p class="text-xs text-zinc-500">Use o botão "+ Playlist" em qualquer Faixa da busca, álbum, artista ou Faixas Favoritas.</p>
            </div>
        @else
            <div class="divide-y divide-zinc-800/60 rounded-xl bg-zinc-900/50 border border-zinc-800/80 overflow-visible">
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

                        <div class="flex items-center gap-3 shrink-0">
                            <div class="flex items-center gap-1">
                                <button
                                    type="button"
                                    wire:click="moveTrackUp({{ $index }})"
                                    @if ($index === 0) disabled @endif
                                    class="text-xs px-2 py-1 rounded border border-zinc-800 text-zinc-400 hover:text-white disabled:opacity-30 transition"
                                    title="Mover Faixa para cima"
                                >
                                    ↑
                                </button>
                                <button
                                    type="button"
                                    wire:click="moveTrackDown({{ $index }})"
                                    @if ($index === count($tracks) - 1) disabled @endif
                                    class="text-xs px-2 py-1 rounded border border-zinc-800 text-zinc-400 hover:text-white disabled:opacity-30 transition"
                                    title="Mover Faixa para baixo"
                                >
                                    ↓
                                </button>
                            </div>

                            <button
                                type="button"
                                wire:click="removeTrackAtIndex({{ $index }})"
                                class="text-xs text-zinc-500 hover:text-red-400 px-2 py-1 transition"
                                title="Remover Faixa da Playlist Local"
                            >
                                Remover
                            </button>

                            <x-track-row-actions
                                :index="$index"
                                :track="$track"
                                :favorited-ids="$favoritedTrackIds"
                                :playlists="$userPlaylists"
                            />
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    @endif
</section>
