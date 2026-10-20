import { apiRequest, SpotifySdkManager } from './spotify-sdk-manager';
import { registerMediaSessionHandlers, syncMediaSessionMetadata } from './media-session-sync';

export function createSpotifyPlayerBar(initialConfig = {}) {
    return {
        sdkManager: null,
        sdkReady: false,
        playbackMode: 'sdk',
        activeDeviceId: initialConfig.preferredDeviceId || null,
        devices: [],
        showDevicesMenu: false,
        statusLabel: 'Inicializando Reprodutor Web...',
        errorMessage: null,
        hasActiveSession: false,

        isPaused: true,
        positionMs: 0,
        durationMs: Number(initialConfig.lastPlayedTrack?.durationMs || 0),
        isDraggingSeek: false,
        progressTimer: null,

        volume: Number(initialConfig.defaultVolume ?? 80),
        previousVolumeBeforeMute: Number(initialConfig.defaultVolume ?? 80) || 80,
        repeatMode: initialConfig.repeatMode || 'off',
        shuffleEnabled: Boolean(initialConfig.shuffleEnabled),

        currentTrack: {
            id: initialConfig.lastPlayedTrack?.id || null,
            uri: initialConfig.lastPlayedTrack?.uri || initialConfig.lastPlayedTrackUri || null,
            title: initialConfig.lastPlayedTrack?.title || null,
            artistName: initialConfig.lastPlayedTrack?.artistName || null,
            albumName: initialConfig.lastPlayedTrack?.albumName || null,
            albumCoverUrl: initialConfig.lastPlayedTrack?.albumCoverUrl || null,
            favorited: false,
        },

        canUseLocalSdk() {
            return Boolean(this.playbackMode === 'sdk' && this.sdkReady && this.sdkManager?.player);
        },

        initPlayer() {
            if (window.Alpine && typeof window.Alpine.store === 'function') {
                window.Alpine.store('player', this);
            }

            this.sdkManager = new SpotifySdkManager({
                onReady: async (deviceId) => {
                    this.sdkReady = true;

                    if (!this.activeDeviceId || this.activeDeviceId === deviceId) {
                        this.playbackMode = 'sdk';
                        this.activeDeviceId = deviceId;
                        this.statusLabel = 'Reprodutor Web Ativo';

                        try {
                            await apiRequest('/player/transfer', 'POST', {
                                device_id: deviceId,
                                play: false,
                            });
                        } catch (_e) {
                            // Transferência inicial silenciosa
                        }
                    } else {
                        this.playbackMode = 'connect';
                        this.statusLabel = 'Dispositivo Connect Preferido';
                    }
                },
                onNotReady: () => {
                    this.sdkReady = false;
                    this.statusLabel = 'Reprodutor Web desconectado';
                },
                onFallbackToConnect: (label) => {
                    this.sdkReady = false;
                    this.playbackMode = 'connect';
                    this.statusLabel = label || 'Modo Spotify Connect';
                    this.refreshDevices();
                },
                onStateChanged: (state) => {
                    this.applySdkState(state);
                },
                onError: (message) => {
                    this.errorMessage = message;
                },
            });

            this.sdkManager.init(this.volume);
            this.startProgressLoop();
            this.refreshCurrentTrackFavoriteStatus();

            registerMediaSessionHandlers({
                onPlay: () => {
                    if (this.isPaused) {
                        this.togglePlay();
                    }
                },
                onPause: () => {
                    if (!this.isPaused) {
                        this.togglePlay();
                    }
                },
                onPrevious: () => this.previousTrack(),
                onNext: () => this.nextTrack(),
                onSeek: (posMs) => this.commitSeek(posMs),
            });

            window.addEventListener('spotify:play-context', (event) => {
                const detail = event.detail || {};
                this.playContext(detail.uris || [], detail.offset || 0);
            });

            window.addEventListener('spotify:context-started', (event) => {
                const detail = event.detail || {};
                this.hasActiveSession = true;
                this.isPaused = false;
                this.positionMs = 0;

                if (detail.durationMs) {
                    this.durationMs = Number(detail.durationMs);
                }

                if (detail.track) {
                    this.currentTrack = {
                        id: detail.track.id || null,
                        uri: detail.track.uri || null,
                        title: detail.track.title || null,
                        artistName: detail.track.artistName || null,
                        albumName: detail.track.albumName || null,
                        albumCoverUrl: detail.track.albumCoverUrl || null,
                        favorited: Boolean(detail.track.favorited),
                    };
                    syncMediaSessionMetadata(this.currentTrack, false);
                }
            });

            window.addEventListener('spotify:favorite-updated', (event) => {
                const detail = event.detail || {};
                if (
                    (detail.spotifyTrackUri && detail.spotifyTrackUri === this.currentTrack.uri) ||
                    (detail.spotifyTrackId && detail.spotifyTrackId === this.currentTrack.id)
                ) {
                    this.currentTrack.favorited = Boolean(detail.favorited);
                }
            });
        },

        applySdkState(state) {
            this.hasActiveSession = true;
            this.isPaused = Boolean(state.paused);
            this.durationMs = Number(state.duration || 0);

            if (!this.isDraggingSeek) {
                this.positionMs = Number(state.position || 0);
            }

            const track = state.track_window?.current_track;
            if (track) {
                const previousUri = this.currentTrack.uri;
                this.currentTrack = {
                    id: track.id || null,
                    uri: track.uri,
                    title: track.name,
                    artistName: (track.artists || []).map((a) => a.name).join(', '),
                    albumName: track.album?.name || '',
                    albumCoverUrl: track.album?.images?.[0]?.url || null,
                    favorited: previousUri === track.uri ? this.currentTrack.favorited : false,
                };

                if (previousUri !== track.uri) {
                    this.refreshCurrentTrackFavoriteStatus();
                }
            }

            syncMediaSessionMetadata(this.currentTrack, this.isPaused);
        },

        async refreshCurrentTrackFavoriteStatus() {
            if (!this.currentTrack.uri) {
                return;
            }

            try {
                const query = new URLSearchParams({ uri: this.currentTrack.uri });
                const data = await apiRequest(`/player/favorites/status?${query.toString()}`, 'GET');
                this.currentTrack.favorited = Boolean(data.favorited);
            } catch (_e) {
                // Silencioso
            }
        },

        async toggleCurrentTrackFavorite() {
            if (!this.currentTrack.uri) {
                return;
            }

            try {
                const data = await apiRequest('/player/favorites/toggle', 'POST', {
                    spotify_track_id: this.currentTrack.id,
                    spotify_track_uri: this.currentTrack.uri,
                    title: this.currentTrack.title,
                    artist_name: this.currentTrack.artistName,
                    album_name: this.currentTrack.albumName,
                    album_cover_url: this.currentTrack.albumCoverUrl,
                    duration_ms: this.durationMs || 1000,
                });

                this.currentTrack.favorited = Boolean(data.favorited);

                if (window.Livewire && typeof window.Livewire.emit === 'function') {
                    window.Livewire.emit('refreshFavoritesState');
                }
            } catch (error) {
                this.errorMessage = error.message;
            }
        },

        startProgressLoop() {
            if (this.progressTimer) {
                clearInterval(this.progressTimer);
            }

            this.progressTimer = setInterval(() => {
                if (!this.isPaused && !this.isDraggingSeek && this.durationMs > 0) {
                    this.positionMs = Math.min(this.durationMs, this.positionMs + 250);
                }
            }, 250);
        },

        async refreshDevices() {
            try {
                const data = await apiRequest('/player/devices', 'GET');
                this.devices = data.devices || [];

                if (!this.activeDeviceId && this.devices.length > 0) {
                    const active = this.devices.find((d) => d.is_active) || this.devices[0];
                    this.activeDeviceId = active.id;
                }
            } catch (error) {
                this.errorMessage = error.message;
            }
        },

        async selectDevice(device) {
            this.activeDeviceId = device.id;
            this.playbackMode = device.id === window.__spotifyPlayerDeviceId ? 'sdk' : 'connect';
            this.statusLabel = device.name;
            this.showDevicesMenu = false;

            try {
                await apiRequest('/player/transfer', 'POST', {
                    device_id: device.id,
                    play: !this.isPaused,
                });
            } catch (error) {
                this.errorMessage = error.message;
            }
        },

        async playContext(uris = [], offset = 0) {
            this.sdkManager?.activateAudioElement();
            this.errorMessage = null;

            try {
                await apiRequest('/player/play', 'POST', {
                    device_id: this.activeDeviceId,
                    uris,
                    offset,
                });
                this.hasActiveSession = true;
                this.isPaused = false;
            } catch (error) {
                this.errorMessage = error.message;
            }
        },

        async togglePlay() {
            this.sdkManager?.activateAudioElement();
            this.errorMessage = null;

            if (this.canUseLocalSdk() && this.hasActiveSession) {
                await this.sdkManager.player.togglePlay();
                return;
            }

            try {
                if (this.isPaused) {
                    const payload = { device_id: this.activeDeviceId };
                    if (!this.hasActiveSession && this.currentTrack.uri) {
                        payload.uris = [this.currentTrack.uri];
                        payload.offset = 0;
                    }
                    await apiRequest('/player/play', 'POST', payload);
                    this.hasActiveSession = true;
                    this.isPaused = false;
                } else {
                    await apiRequest('/player/pause', 'POST', {
                        device_id: this.activeDeviceId,
                    });
                    this.isPaused = true;
                }
            } catch (error) {
                this.errorMessage = error.message;
            }
        },

        async nextTrack() {
            if (this.canUseLocalSdk()) {
                await this.sdkManager.player.nextTrack();
                return;
            }

            try {
                await apiRequest('/player/next', 'POST', { device_id: this.activeDeviceId });
            } catch (error) {
                this.errorMessage = error.message;
            }
        },

        async previousTrack() {
            if (this.canUseLocalSdk()) {
                await this.sdkManager.player.previousTrack();
                return;
            }

            try {
                await apiRequest('/player/previous', 'POST', { device_id: this.activeDeviceId });
            } catch (error) {
                this.errorMessage = error.message;
            }
        },

        startSeekDrag() {
            this.isDraggingSeek = true;
        },

        updateSeekPreview(value) {
            this.isDraggingSeek = true;
            this.positionMs = Number(value || 0);
        },

        async commitSeek(value) {
            const targetMs = Math.max(0, Number(value || 0));
            this.positionMs = targetMs;
            this.isDraggingSeek = false;

            if (this.canUseLocalSdk()) {
                await this.sdkManager.player.seek(targetMs);
                return;
            }

            try {
                await apiRequest('/player/seek', 'POST', {
                    device_id: this.activeDeviceId,
                    position_ms: targetMs,
                });
            } catch (error) {
                this.errorMessage = error.message;
            }
        },

        previewVolume(value) {
            const vol = Math.max(0, Math.min(100, Number(value || 0)));
            this.volume = vol;

            if (this.canUseLocalSdk()) {
                this.sdkManager.player.setVolume(vol / 100);
            }
        },

        async commitVolume(value) {
            const vol = Math.max(0, Math.min(100, Number(value || 0)));
            this.volume = vol;
            if (vol > 0) {
                this.previousVolumeBeforeMute = vol;
            }

            try {
                await apiRequest('/player/preferences', 'POST', {
                    device_id: this.activeDeviceId,
                    default_volume: vol,
                });
            } catch (error) {
                this.errorMessage = error.message;
            }
        },

        async toggleMute() {
            if (this.volume > 0) {
                this.previousVolumeBeforeMute = this.volume;
                this.previewVolume(0);
                await this.commitVolume(0);
            } else {
                const restored = this.previousVolumeBeforeMute || 80;
                this.previewVolume(restored);
                await this.commitVolume(restored);
            }
        },

        async toggleShuffle() {
            const nextState = !this.shuffleEnabled;
            this.shuffleEnabled = nextState;

            try {
                await apiRequest('/player/preferences', 'POST', {
                    device_id: this.activeDeviceId,
                    shuffle_enabled: nextState,
                });
            } catch (error) {
                this.shuffleEnabled = !nextState;
                this.errorMessage = error.message;
            }
        },

        async cycleRepeatMode() {
            const order = ['off', 'context', 'track'];
            const currentIndex = order.indexOf(this.repeatMode);
            const nextMode = order[(currentIndex + 1) % order.length];
            const prevMode = this.repeatMode;
            this.repeatMode = nextMode;

            try {
                await apiRequest('/player/preferences', 'POST', {
                    device_id: this.activeDeviceId,
                    repeat_mode: nextMode,
                });
            } catch (error) {
                this.repeatMode = prevMode;
                this.errorMessage = error.message;
            }
        },

        formatTime(ms) {
            const totalSeconds = Math.max(0, Math.floor(Number(ms || 0) / 1000));
            const minutes = Math.floor(totalSeconds / 60);
            const seconds = totalSeconds % 60;
            return `${minutes}:${seconds < 10 ? '0' : ''}${seconds}`;
        },
    };
}
