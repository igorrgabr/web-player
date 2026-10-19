# Biblioteca Exclusivamente Local e Catálogo Normalizado de Faixas

Playlists e Faixas Favoritas são armazenadas exclusivamente no PostgreSQL local da aplicação, referenciando uma tabela normalizada `tracks` que guarda o snapshot de exibição (`spotify_track_id`, `spotify_track_uri`, título, artista, álbum, capa e duração), sem sincronização bidirecional com os endpoints de playlists ou biblioteca da Spotify Web API. As restrições do *Development Mode* do Spotify introduzidas entre 2024 e 2026 limitaram cotas e endpoints de escrita/leitura em lote; manter a biblioteca localmente com metadados normalizados em `tracks` evita duplicação entre playlists e favoritos, elimina chamadas de rede ao abrir a biblioteca do usuário e isola a aplicação de bloqueios na API do Spotify.

