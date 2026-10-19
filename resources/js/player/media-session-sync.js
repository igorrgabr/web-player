export function syncMediaSessionMetadata(track, isPaused) {
    if (typeof navigator === 'undefined' || !('mediaSession' in navigator)) {
        return;
    }

    navigator.mediaSession.playbackState = isPaused ? 'paused' : 'playing';

    if (!track || !track.title || typeof window.MediaMetadata === 'undefined') {
        return;
    }

    navigator.mediaSession.metadata = new window.MediaMetadata({
        title: track.title,
        artist: track.artistName || '',
        album: track.albumName || '',
        artwork: track.albumCoverUrl
            ? [{ src: track.albumCoverUrl, sizes: '300x300', type: 'image/jpeg' }]
            : [],
    });
}

export function registerMediaSessionHandlers(handlers) {
    if (typeof navigator === 'undefined' || !('mediaSession' in navigator)) {
        return;
    }

    const actions = [
        ['play', handlers.onPlay],
        ['pause', handlers.onPause],
        ['previoustrack', handlers.onPrevious],
        ['nexttrack', handlers.onNext],
        [
            'seekto',
            (details) => {
                if (details && typeof details.seekTime === 'number' && handlers.onSeek) {
                    handlers.onSeek(Math.round(details.seekTime * 1000));
                }
            },
        ],
    ];

    actions.forEach(([action, callback]) => {
        try {
            navigator.mediaSession.setActionHandler(action, callback || null);
        } catch (_error) {
            // Ignora ações não suportadas pelo navegador específico
        }
    });
}

