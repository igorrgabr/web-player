# Reprodução Híbrida via Web Playback SDK e Spotify Connect

O aplicativo utiliza o **Spotify Web Playback SDK** como motor primário para reproduzir áudio diretamente no navegador em ambientes Desktop compatíveis com DRM/EME, e alterna para o modo de controle remoto via **Spotify Connect** (REST API) quando o navegador não suporta EME (como no iOS Safari) ou quando o usuário seleciona explicitamente outro dispositivo ativo. Essa abordagem garante reprodução nativa na aba sempre que tecnicamente permitido pelo navegador, sem deixar dispositivos móveis sem funcionalidade.

