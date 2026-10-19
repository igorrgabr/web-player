require('./bootstrap');

import Alpine from 'alpinejs';
import { createSpotifyPlayerBar } from './player/alpine-player-bar';

window.Alpine = Alpine;
Alpine.data('spotifyPlayerBar', createSpotifyPlayerBar);
Alpine.start();
