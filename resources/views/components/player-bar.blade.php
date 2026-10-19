@props(['preferences'])

<footer
    id="global-player-bar"
    wire:ignore
    x-data="spotifyPlayerBar({
        defaultVolume: {{ (int) $preferences->default_volume }},
        repeatMode: @js($preferences->repeat_mode),
        shuffleEnabled: @js((bool) $preferences->shuffle_enabled),
        preferredDeviceId: @js($preferences->preferred_device_id),
        lastPlayedTrackUri: @js($preferences->last_played_track_uri),
    })"
    x-init="initPlayer()"
    class="h-24 bg-zinc-900 border-t border-zinc-800 px-6 flex items-center justify-between gap-4 shrink-0 select-none relative"
>
    <template x-if="errorMessage">
        <div
            role="alert"
            class="absolute -top-10 left-1/2 -translate-x-1/2 bg-red-950/95 border border-red-800 text-red-200 text-xs px-4 py-1.5 rounded-full shadow-lg flex items-center gap-2 z-50"
        >
            <span x-text="errorMessage"></span>
            <button type="button" @click="errorMessage = null" class="text-red-400 hover:text-white font-bold">×</button>
        </div>
    </template>

    <!-- Faixa Atual -->
    <div class="w-1/4 min-w-[180px] flex items-center gap-3">
        <template x-if="currentTrack.albumCoverUrl">
            <img :src="currentTrack.albumCoverUrl" :alt="currentTrack.albumName" class="w-14 h-14 rounded-lg object-cover shadow" />
        </template>
        <template x-if="!currentTrack.albumCoverUrl">
            <div class="w-14 h-14 rounded-lg bg-zinc-800 flex items-center justify-center text-zinc-500 text-xs">
                ♪
            </div>
        </template>
        <div class="truncate">
            <p class="text-sm font-semibold text-white truncate" x-text="currentTrack.title || 'Nenhuma Faixa em reprodução'"></p>
            <p class="text-xs text-zinc-400 truncate" x-text="currentTrack.artistName || 'Selecione uma Faixa para iniciar'"></p>
        </div>
    </div>

    <!-- Controles Centrais e Barra de Progresso -->
    <div class="flex-1 max-w-xl flex flex-col items-center gap-2">
        <div class="flex items-center gap-5">
            <button
                type="button"
                @click="toggleShuffle()"
                :class="shuffleEnabled ? 'text-emerald-400' : 'text-zinc-400 hover:text-white'"
                class="text-xs font-medium transition"
                title="Alternar Ordem Aleatória (Shuffle)"
            >
                Shuffle
            </button>

            <button
                type="button"
                @click="previousTrack()"
                class="text-zinc-300 hover:text-white transition text-sm"
                title="Faixa Anterior"
            >
                ⏮
            </button>

            <button
                type="button"
                @click="togglePlay()"
                class="w-10 h-10 rounded-full bg-white hover:scale-105 text-zinc-950 flex items-center justify-center font-bold transition"
                title="Reproduzir / Pausar"
            >
                <span x-text="isPaused ? '▶' : '⏸'"></span>
            </button>

            <button
                type="button"
                @click="nextTrack()"
                class="text-zinc-300 hover:text-white transition text-sm"
                title="Próxima Faixa"
            >
                ⏭
            </button>

            <button
                type="button"
                @click="cycleRepeatMode()"
                :class="repeatMode !== 'off' ? 'text-emerald-400' : 'text-zinc-400 hover:text-white'"
                class="text-xs font-medium transition"
                title="Modo de Repetição"
            >
                <span x-text="repeatMode === 'track' ? 'Repeat 1' : (repeatMode === 'context' ? 'Repeat All' : 'Repeat Off')"></span>
            </button>
        </div>

        <div class="w-full flex items-center gap-3 text-xs text-zinc-400">
            <span class="w-10 text-right font-mono" x-text="formatTime(positionMs)">0:00</span>
            <input
                type="range"
                min="0"
                :max="durationMs || 1"
                :value="positionMs"
                @mousedown="startSeekDrag()"
                @touchstart="startSeekDrag()"
                @input="updateSeekPreview($event.target.value)"
                @change="commitSeek($event.target.value)"
                class="flex-1 accent-emerald-500 h-1.5 bg-zinc-700 rounded-lg cursor-pointer"
            />
            <span class="w-10 font-mono" x-text="formatTime(durationMs)">0:00</span>
        </div>
    </div>

    <!-- Dispositivo de Reprodução e Volume -->
    <div class="w-1/4 min-w-[200px] flex items-center justify-end gap-4 relative">
        <div class="relative">
            <button
                type="button"
                @click="showDevicesMenu = !showDevicesMenu; if (showDevicesMenu) refreshDevices()"
                class="flex items-center gap-2 text-xs px-2.5 py-1.5 rounded-lg hover:bg-zinc-800 transition"
                title="Selecionar Dispositivo de Reprodução"
            >
                <span
                    class="w-2 h-2 rounded-full"
                    :class="sdkReady ? 'bg-emerald-400' : (playbackMode === 'connect' ? 'bg-amber-400' : 'bg-zinc-600')"
                ></span>
                <span class="text-zinc-300 truncate max-w-[130px]" x-text="statusLabel"></span>
            </button>

            <div
                x-show="showDevicesMenu"
                @click.outside="showDevicesMenu = false"
                class="absolute bottom-12 right-0 w-64 rounded-xl bg-zinc-900 border border-zinc-800 shadow-2xl p-3 z-50"
                style="display: none;"
            >
                <p class="text-xs font-semibold text-zinc-400 px-2 pb-2 border-b border-zinc-800">
                    Dispositivos de Reprodução
                </p>
                <div class="mt-2 space-y-1 max-h-48 overflow-y-auto">
                    <template x-for="device in devices" :key="device.id">
                        <button
                            type="button"
                            @click="selectDevice(device)"
                            class="w-full text-left px-2.5 py-2 rounded-lg text-xs flex items-center justify-between hover:bg-zinc-800 transition"
                            :class="activeDeviceId === device.id ? 'text-emerald-400 font-semibold bg-zinc-800/60' : 'text-zinc-200'"
                        >
                            <span class="truncate" x-text="device.name"></span>
                            <span class="text-[10px] text-zinc-500 ml-2" x-text="device.type"></span>
                        </button>
                    </template>
                    <template x-if="devices.length === 0">
                        <p class="text-xs text-zinc-500 px-2 py-2">Nenhum Dispositivo Connect encontrado.</p>
                    </template>
                </div>
            </div>
        </div>

        <div class="flex items-center gap-2 w-28">
            <button type="button" @click="toggleMute()" class="text-zinc-400 hover:text-white text-xs">
                <span x-text="volume === 0 ? '🔇' : '🔊'"></span>
            </button>
            <input
                type="range"
                min="0"
                max="100"
                :value="volume"
                @input="previewVolume($event.target.value)"
                @change="commitVolume($event.target.value)"
                class="w-full accent-emerald-500 h-1.5 bg-zinc-700 rounded-lg cursor-pointer"
            />
        </div>
    </div>
</footer>
