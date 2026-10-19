const csrfToken = () =>
    document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

export async function apiRequest(url, method = 'GET', payload = null) {
    const options = {
        method,
        headers: {
            Accept: 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': csrfToken(),
        },
        credentials: 'same-origin',
    };

    if (payload !== null && method !== 'GET') {
        options.headers['Content-Type'] = 'application/json';
        options.body = JSON.stringify(payload);
    }

    const response = await fetch(url, options);
    const data = await response.json().catch(() => ({}));

    if (!response.ok) {
        throw new Error(data.message || 'Falha na comunicação com o Reprodutor Web.');
    }

    return data;
}

export class SpotifySdkManager {
    constructor(callbacks = {}) {
        this.callbacks = callbacks;
        this.player = window.__spotifyPlayerInstance || null;
        this.deviceId = window.__spotifyPlayerDeviceId || null;
        this.elementActivated = false;
    }

    static supportsEme() {
        return (
            typeof window !== 'undefined' &&
            window.isSecureContext &&
            typeof navigator !== 'undefined' &&
            typeof navigator.requestMediaKeySystemAccess === 'function'
        );
    }

    init(initialVolumePercent = 80) {
        if (!SpotifySdkManager.supportsEme()) {
            this.callbacks.onFallbackToConnect?.('Modo Spotify Connect (Navegador sem DRM)');
            return;
        }

        if (window.__spotifyPlayerInstance) {
            this.player = window.__spotifyPlayerInstance;
            if (window.__spotifyPlayerDeviceId) {
                this.callbacks.onReady?.(window.__spotifyPlayerDeviceId);
            }
            return;
        }

        const bootSdk = () => {
            if (window.__spotifyPlayerInstance) {
                this.player = window.__spotifyPlayerInstance;
                return;
            }

            const player = new window.Spotify.Player({
                name: 'Web Player Pessoal',
                getOAuthToken: async (cb) => {
                    try {
                        const data = await apiRequest('/player/token', 'GET');
                        cb(data.access_token);
                    } catch (error) {
                        this.callbacks.onError?.(error.message);
                    }
                },
                volume: Math.max(0, Math.min(1, initialVolumePercent / 100)),
            });

            player.addListener('ready', ({ device_id }) => {
                window.__spotifyPlayerDeviceId = device_id;
                this.deviceId = device_id;
                this.callbacks.onReady?.(device_id);
            });

            player.addListener('not_ready', ({ device_id }) => {
                this.callbacks.onNotReady?.(device_id);
            });

            player.addListener('player_state_changed', (state) => {
                if (state) {
                    this.callbacks.onStateChanged?.(state);
                }
            });

            player.addListener('initialization_error', () => {
                this.callbacks.onFallbackToConnect?.('Modo Spotify Connect');
            });

            player.addListener('authentication_error', ({ message }) => {
                this.callbacks.onError?.(message || 'Erro de autenticação no Reprodutor Web.');
            });

            player.addListener('account_error', () => {
                this.callbacks.onError?.('Conta Spotify Premium necessária para reprodução.');
            });

            player.addListener('playback_error', ({ message }) => {
                this.callbacks.onError?.(message || 'Falha ao reproduzir a Faixa.');
            });

            window.__spotifyPlayerInstance = player;
            this.player = player;
            player.connect();

            window.addEventListener('beforeunload', () => {
                player.disconnect();
            });
        };

        if (window.Spotify && window.Spotify.Player) {
            bootSdk();
        } else {
            window.onSpotifyWebPlaybackSDKReady = bootSdk;
        }
    }

    activateAudioElement() {
        if (this.player && !this.elementActivated && typeof this.player.activateElement === 'function') {
            this.player.activateElement();
            this.elementActivated = true;
        }
    }
}
