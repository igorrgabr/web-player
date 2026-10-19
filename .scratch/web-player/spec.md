# Aplicativo Web Pessoal de Streaming Musical com Spotify

Status: ready-for-agent

## Problem Statement

Um **Ouvinte** com assinatura Spotify Premium Família deseja uma experiência web própria, rápida e personalizável para explorar o catálogo do Spotify, ouvir **Faixas** completas e organizar sua própria biblioteca de **Playlists Locais** e **Faixas Favoritas** em ambiente local, sem depender da interface oficial pesada e sem sofrer com as restrições recentes (2024–2026) impostas pela plataforma Spotify a endpoints de escrita de biblioteca e recomendações no *Development Mode*.

Além disso, em aplicações tradicionais renderizadas no servidor com Laravel 8 e Livewire 2, a navegação convencional entre páginas recarrega o documento HTML, destruindo a instância do **Reprodutor Web** e interrompendo a música a cada clique.

## Solution

Construir um aplicativo web pessoal de streaming musical baseado em **Laravel 8**, **Livewire 2**, **Alpine.js v3**, **Tailwind CSS 3** e **PostgreSQL 16**, operando sob uma arquitetura de casca persistente (*App Shell*) de rota única onde o rodapé do player permanece isolado e vivo durante toda a navegação interna.

A aplicação autentica o **Ouvinte** via OAuth 2.0 oficial do Spotify (utilizando loopback explícito `127.0.0.1`), valida a assinatura Premium da **Conta Spotify**, reproduz **Faixas** completas de forma híbrida (diretamente na aba via **Reprodutor Web** em navegadores Desktop compatíveis com DRM/EME, ou controlando um **Dispositivo Connect** externo em dispositivos móveis) e mantém **Playlists Locais**, **Faixas Favoritas**, **Histórico de Reprodução** e **Preferências de Reprodução** de maneira 100% local e isolada por **Ouvinte** no PostgreSQL, apoiada em um catálogo local normalizado de **Faixas**.

## User Stories

1. Como um **Ouvinte**, quero iniciar o login com minha **Conta Spotify** a partir da tela inicial, para que eu possa autenticar na aplicação com segurança sem expor minhas credenciais.
2. Como um **Ouvinte**, quero que o fluxo de login valide o parâmetro de segurança anti-CSRF (`state`) no retorno do Spotify, para que minha sessão esteja protegida contra ataques de falsificação de requisição.
3. Como um **Ouvinte**, quero que o sistema verifique se minha **Conta Spotify** possui assinatura Premium ativa no momento do login, para que eu receba um aviso claro caso a conta não suporte reprodução completa.
4. Como um **Ouvinte**, quero que meus tokens de acesso e renovação da **Conta Spotify** sejam armazenados criptografados em repouso no banco de dados, para que minhas credenciais de integração permaneçam protegidas.
5. Como um **Ouvinte**, quero que meu token de acesso do Spotify seja renovado automaticamente e de forma transparente antes de expirar, para que minha música e minhas buscas não sejam interrompidas após uma hora de uso.
6. Como um **Ouvinte** membro de um plano Spotify Premium Família (previamente autorizado no painel do aplicativo), quero ter meus próprios dados isolados ao fazer login, para que minhas **Playlists Locais**, **Faixas Favoritas**, **Histórico de Reprodução** e **Preferências de Reprodução** não se misturem com os de outros membros.
7. Como um **Ouvinte**, quero encerrar minha sessão na aplicação quando desejar, para que o **Reprodutor Web** seja desconectado com segurança.
8. Como um **Ouvinte** em um navegador Desktop compatível com DRM/EME, quero que a aplicação inicialize automaticamente um **Reprodutor Web** conectado ao Spotify assim que eu entrar no aplicativo, para que eu possa ouvir músicas diretamente na aba do navegador.
9. Como um **Ouvinte**, quero que meu primeiro clique de reprodução desbloqueie o contexto de áudio do navegador no **Reprodutor Web**, para que as políticas de *autoplay* do navegador não bloqueiem o som.
10. Como um **Ouvinte** em um navegador móvel sem suporte ao SDK de áudio embutido (ou quando eu preferir usar outro aparelho), quero selecionar e controlar um **Dispositivo Connect** ativo da minha conta, para que eu possa usar a aplicação como controlador remoto do Spotify no celular ou em outro dispositivo.
11. Como um **Ouvinte**, quero visualizar no rodapé fixo do player a capa do álbum, o título da **Faixa** atual, o nome do artista e o indicador do **Dispositivo de Reprodução** ativo, para que eu sempre saiba o que está tocando e onde.
12. Como um **Ouvinte**, quero pausar e retomar a **Faixa** em execução com feedback visual imediato no botão Play/Pause, para que eu tenha controle instantâneo sobre o áudio.
13. Como um **Ouvinte**, quero avançar para a próxima **Faixa** ou voltar para a **Faixa** anterior dentro do **Contexto de Reprodução** atual, para que eu possa navegar facilmente pela fila.
14. Como um **Ouvinte**, quero acompanhar o progresso da **Faixa** atual em uma barra contínua atualizada suavemente e poder arrastar o controle deslizante (*seek*) para qualquer ponto da música, para que eu possa avançar ou retroceder trechos específicos sem engasgos visuais.
15. Como um **Ouvinte**, quero ajustar o volume de reprodução (ou silenciar/reativar o som) por meio de um controle deslizante no rodapé, para que eu regule a intensidade do áudio rapidamente.
16. Como um **Ouvinte**, quero alternar entre os modos de repetição (`off`, `context` para repetir a lista inteira e `track` para repetir a faixa atual), para que a reprodução siga minha preferência do momento.
17. Como um **Ouvinte**, quero ativar ou desativar o modo de ordem aleatória (*shuffle*) no player, para que eu possa ouvir minhas listas de forma embaralhada quando desejar.
18. Como um **Ouvinte**, quero que meu volume padrão, modo de repetição, estado de *shuffle*, último **Dispositivo de Reprodução** e última **Faixa** tocada sejam salvos nas minhas **Preferências de Reprodução**, para que meu ambiente seja restaurado quando eu reabrir o aplicativo.
19. Como um **Ouvinte**, quero navegar entre a tela inicial, a busca, minha biblioteca de **Faixas Favoritas**, páginas de **Playlists Locais**, álbuns e artistas sem que a página HTML recarregue por completo, para que a **Faixa** em execução no **Reprodutor Web** continue tocando sem nenhum corte.
20. Como um **Ouvinte**, quero que a URL do navegador reflita a seção atual em que estou navegando, para que eu possa usar os botões Voltar/Avançar do navegador ou atualizar a aba sem perder a tela em que estava.
21. Como um **Ouvinte**, quero que a música continue tocando em segundo plano enquanto a aba do navegador estiver aberta (mesmo minimizada ou em outra janela), para que eu possa trabalhar ou estudar ouvindo música.
22. Como um **Ouvinte**, quero controlar o player (Play, Pause, Próxima, Anterior e Seek) pelas teclas multimídia físicas do meu teclado e pelo widget de mídia do sistema operacional (via Media Session API), para que eu não precise voltar à aba do navegador apenas para pausar ou pular de música.
23. Como um **Ouvinte**, quero pesquisar por termo livre e visualizar resultados organizados em **Faixas**, Artistas e Álbuns vindos do catálogo do Spotify, para que eu encontre rapidamente o que quero ouvir.
24. Como um **Ouvinte**, quero carregar mais resultados de busca sob demanda (paginação compatível com o limite de 10 itens por requisição do *Development Mode*), para que eu possa explorar além dos primeiros resultados.
25. Como um **Ouvinte**, quero clicar em um Álbum nos resultados da busca para visualizar sua capa, informações e lista completa de **Faixas**, podendo iniciar a reprodução do álbum inteiro a partir de qualquer faixa.
26. Como um **Ouvinte**, quero clicar em um Artista nos resultados da busca para visualizar seu perfil, suas principais **Faixas** encontradas no catálogo e sua discografia de álbuns/singles, para que eu possa explorar a obra daquele artista mesmo sem os endpoints legados restritos pelo Spotify.
27. Como um **Ouvinte**, quero marcar ou desmarcar qualquer **Faixa** como **Faixa Favorita** (tanto nas listagens de busca/álbum/playlist quanto diretamente no rodapé do player), para que eu construa minha coleção de músicas curtidas.
28. Como um **Ouvinte**, quero acessar minha biblioteca de **Faixas Favoritas** ordenadas da mais recente para a mais antiga e iniciar a reprodução de toda a lista como um **Contexto de Reprodução** a partir da faixa clicada, para que eu ouça minhas músicas preferidas em sequência.
29. Como um **Ouvinte**, quero criar novas **Playlists Locais** informando nome e descrição opcional, para que eu organize minhas músicas por tema, humor ou ocasião.
30. Como um **Ouvinte**, quero editar o nome/descrição ou excluir uma **Playlist Local** existente, para que eu mantenha minha biblioteca organizada.
31. Como um **Ouvinte**, quero adicionar qualquer **Faixa** a uma das minhas **Playlists Locais** a partir dos resultados da busca, de um álbum ou da minha biblioteca, para que eu monte minhas seleções musicais.
32. Como um **Ouvinte**, quero que o sistema impeça a adição de uma **Faixa** duplicada dentro da mesma **Playlist Local** exibindo um aviso amigável, para que minhas playlists não acumulem músicas repetidas acidentalmente.
33. Como um **Ouvinte**, quero remover **Faixas** de uma **Playlist Local** e reordenar a posição das faixas (movendo para cima ou para baixo), para que a sequência de reprodução da playlist fique exatamente na ordem que eu escolher.
34. Como um **Ouvinte**, quero clicar em qualquer **Faixa** dentro de uma **Playlist Local** para iniciar a reprodução enviando toda a playlist como **Contexto de Reprodução** a partir daquela posição (`offset`), para que as faixas seguintes da playlist toquem automaticamente na sequência definida.
35. Como um **Ouvinte**, quero visualizar na tela inicial meus atalhos de **Playlists Locais**, **Faixas Favoritas** e a lista do meu **Histórico de Reprodução** (*Tocadas Recentemente*), para que eu retome rapidamente o que estava ouvindo.
36. Como um **Ouvinte**, quero receber mensagens claras e não bloqueantes quando a API do Spotify retornar limite de requisições (HTTP 429) ou instabilidade temporária, para que a interface continue responsiva e eu saiba quando tentar novamente.
37. Como um **Ouvinte**, quero que a interface seja responsiva em telas Desktop e Mobile e conte com manifesto de aplicativo web (PWA), para que eu possa instalar o aplicativo como uma janela dedicada no meu dispositivo.

## Implementation Decisions

### 1. Arquitetura App Shell de Rota Única (Laravel 8 + Livewire 2 + Alpine.js v3)
- Conforme registrado no ADR-0001, a aplicação utiliza um componente coordenador principal (`AppShell`) em uma única rota autenticada, controlando a visão central (`home`, `search`, `favorites`, `playlist`, `album`, `artist`) e seus parâmetros via `$queryString` do Livewire 2.
- O rodapé do player é renderizado dentro de um container com `wire:ignore`, impedindo que atualizações de DOM do Livewire 2 recriem ou desconectem o **Reprodutor Web**.
- O estado de alta frequência do player (progresso em milissegundos interpolado localmente, estado de arraste da barra de *seek*, volume atual e metadados da faixa ativa) é gerenciado no cliente por um *store* reativo do Alpine.js alimentado por um gerenciador JavaScript Singleton do Web Playback SDK.
- Não serão utilizados AdminLTE 3 (para evitar conflito de Bootstrap/jQuery com Tailwind CSS 3 e Alpine.js) nem Redis no MVP (utilizando drivers nativos de banco/arquivo do Laravel).

### 2. Autenticação OAuth 2.0, Loopback Explícito (`127.0.0.1`) e Segurança de Tokens
- Toda configuração local e de Redirect URI utiliza estritamente o endereço IP literal de loopback `http://127.0.0.1` (e nunca `localhost`), atendendo simultaneamente à política de segurança de Redirect URIs do Spotify (2025/2026) e ao requisito de *Secure Context* dos navegadores para habilitar *Encrypted Media Extensions* (EME / Widevine DRM) sem exigir HTTPS local.
- Um middleware de ambiente local garante que acessos acidentais via `localhost` sejam redirecionados para `127.0.0.1` antes de iniciar o fluxo OAuth, preservando a consistência do cookie de sessão.
- O fluxo OAuth utiliza *Authorization Code Flow* com validação estrita do parâmetro `state` em sessão e solicita os escopos: `streaming`, `user-read-email`, `user-read-private`, `user-read-playback-state`, `user-modify-playback-state` e `user-read-currently-playing`.
- No callback de autenticação, o serviço consulta `GET /v1/me` para validar o perfil e confirmar que `product === 'premium'`.
- Os campos `access_token` e `refresh_token` da **Conta Spotify** são persistidos com criptografia simétrica em repouso (cast `encrypted` do Eloquent).
- O serviço de autenticação renova preventivamente o `access_token` quando faltarem menos de 5 minutos para `token_expires_at`, protegido por lock atômico para evitar condições de corrida entre chamadas concorrentes do SDK e do Livewire, preservando o `refresh_token` anterior caso o Spotify não retorne um novo na resposta de renovação.

### 3. Modelagem de Dados e Catálogo Local Normalizado (PostgreSQL 16)
- Conforme registrado no ADR-0002, **Playlists Locais**, **Faixas Favoritas**, **Histórico de Reprodução** e **Preferências de Reprodução** vivem exclusivamente no PostgreSQL local, isolados por **Ouvinte** (`user_id`), sem sincronização com os endpoints remotos de biblioteca/playlists do Spotify.
- **Entidades e Esquema Relacional:**
  1. **Ouvintes (`users`)**: Identidade local da sessão (`id`, `name`, `email`).
  2. **Contas Spotify (`spotify_accounts`)**: Relacionamento `1:1` com `users` (`user_id` UNIQUE, `spotify_user_id` UNIQUE, `display_name`, `email`, `avatar_url`, `country`, `product_type`, `access_token` criptografado, `refresh_token` criptografado, `token_expires_at`, `scopes`).
  3. **Catálogo Local de Faixas (`tracks`)**: Tabela normalizada de metadados de exibição (`id`, `spotify_track_id` UNIQUE, `spotify_track_uri`, `title`, `artist_name`, `spotify_artist_id`, `album_name`, `spotify_album_id`, `album_cover_url`, `duration_ms`). Atualizada via `upsert` sempre que uma **Faixa** é favoritada, adicionada a uma **Playlist Local** ou iniciada no player.
  4. **Playlists Locais (`local_playlists`)**: Pertencem a um **Ouvinte** (`id`, `user_id`, `name`, `description`, `cover_url`).
  5. **Faixas da Playlist (`playlist_tracks`)**: Relacionam `local_playlists` e `tracks` (`id`, `local_playlist_id`, `track_id`, `position` inteiro sequencial, `added_at`), com restrição de integridade `UNIQUE (local_playlist_id, track_id)` para impedir duplicatas na mesma playlist e índice composto `(local_playlist_id, position ASC)`.
  6. **Faixas Favoritas (`favorite_tracks`)**: Relacionam `users` e `tracks` (`id`, `user_id`, `track_id`, `favorited_at`), com restrição `UNIQUE (user_id, track_id)` e índice `(user_id, favorited_at DESC)`.
  7. **Histórico de Reprodução (`play_histories`)**: Registra as faixas iniciadas pelo **Ouvinte** (`id`, `user_id`, `track_id`, `context_type`, `context_id`, `played_at`), indexado por `(user_id, played_at DESC)`.
  8. **Preferências de Reprodução (`user_preferences`)**: Relacionamento `1:1` com `users` (`user_id` UNIQUE, `default_volume` entre 0 e 100, `repeat_mode` em `off|context|track`, `shuffle_enabled`, `preferred_device_id`, `last_played_track_id`).

### 4. Motor de Reprodução Híbrido e Contexto de Reprodução
- Conforme registrado no ADR-0003, o frontend inicializa um Singleton do **Reprodutor Web** (`Spotify.Player`) quando o navegador suporta EME/DRM. Ao receber o evento `ready` com o `device_id`, registra-o como dispositivo ativo da sessão.
- Antes de disparar a primeira reprodução a partir de um clique do **Ouvinte**, o gerenciador chama `player.activateElement()` sincronamente no gesto do usuário para cumprir as políticas de *autoplay* dos navegadores.
- Quando o navegador não suporta EME/DRM (como no iOS Safari) ou quando o **Ouvinte** seleciona outro aparelho na lista de dispositivos (`GET /v1/me/player/devices`), os comandos de controle são enviados via backend para a Spotify Web API operando sobre o **Dispositivo Connect** selecionado.
- Sempre que o **Ouvinte** clica para tocar uma **Faixa** dentro de uma lista (**Playlist Local**, **Faixas Favoritas**, Álbum ou resultado de Busca), a aplicação envia ao endpoint de reprodução (`PUT /v1/me/player/play`) o **Contexto de Reprodução** completo — ou seja, o array ordenado de `uris` daquela lista acompanhado de `offset: {"position": index}` — além de registrar a **Faixa** no **Histórico de Reprodução** local.
- O gerenciador de áudio sincroniza metadados e ações (`play`, `pause`, `previoustrack`, `nexttrack`, `seekto`) com a `navigator.mediaSession` do navegador para habilitar controle por teclas multimídia e pelo sistema operacional em segundo plano.

### 5. Catálogo, Pesquisa e Resiliência a Restrições da API (2024–2026)
- O serviço de catálogo realiza buscas via `GET /v1/search` utilizando `limit=10` por página (compatível com o teto imposto pelo Spotify ao *Development Mode* em 2026) e paginação via `offset`.
- A visualização de Álbum consome `GET /v1/albums/{id}`.
- Como os endpoints `GET /v1/artists/{id}/top-tracks` e `related-artists` foram removidos para o *Development Mode*, a visualização de Artista combina `GET /v1/artists/{id}` (dados do artista), `GET /v1/search?q=artist:"{nome}"&type=track&limit=10` (faixas em destaque do artista) e `GET /v1/artists/{id}/albums?limit=10` (discografia).
- O cliente HTTP centralizado para a API do Spotify trata respostas `401 Unauthorized` (forçando uma renovação de token e repetindo a chamada uma vez) e `429 Too Many Requests` (respeitando o cabeçalho `Retry-After` quando curto ou lançando exceção de domínio amigável para exibição de aviso na interface).

## Testing Decisions

### O que caracteriza um bom teste neste projeto
- Testes devem validar o **comportamento observável externo** (respostas HTTP, redirecionamentos, mudanças de estado visíveis nos componentes Livewire e registros persistidos no banco de dados), nunca detalhes internos de implementação.
- Nenhum serviço de domínio interno (`SpotifyAuthService`, `SpotifyCatalogService`, `SpotifyPlayerService`) ou model Eloquent deve ser substituído por *mocks*.

### Costura Única de Isolamento (*Single Seam*)
- A única costura de simulação externa permitida nos testes automatizados é a fronteira HTTP de saída do Laravel (`Http::fake()`) para interceptar chamadas destinadas a `accounts.spotify.com` e `api.spotify.com`.
- Os testes acionam a aplicação pelos seus pontos de entrada mais altos:
  1. **Requisições HTTP de Feature (`get`, `postJson`)** para os fluxos de autenticação OAuth e endpoints JSON de comando do player.
  2. **Testes de Componentes Livewire (`Livewire::test`)** para navegação no `AppShell`, pesquisa de catálogo, visualização de álbuns/artistas, gestão de **Playlists Locais** (incluindo unicidade de faixa e reordenação de `position`), **Faixas Favoritas** e **Histórico de Reprodução**.

### Comportamentos e Módulos Cobertos por Testes Automatizados vs. Manuais
- **Automatizados (PHPUnit):**
  - Redirecionamento OAuth com geração de `state` e URL em `127.0.0.1`;
  - Callback OAuth validando `state`, checagem de conta `premium`, persistência criptografada de tokens e isolamento por **Ouvinte**;
  - Renovação transparente de token expirado preservando o `refresh_token`;
  - Busca de catálogo paginada (`limit=10`), composição da visão de Artista e Álbum, e tratamento de erro HTTP 429 (`Retry-After`);
  - Envio de **Contexto de Reprodução** (`uris` + `offset`), transferência de **Dispositivo de Reprodução**, atualização de **Preferências de Reprodução** e gravação no **Histórico de Reprodução**;
  - CRUD completo de **Playlists Locais**, bloqueio de duplicatas na mesma playlist, reordenação sequencial de **Faixas** e marcação/desmarcação de **Faixas Favoritas** normalizadas na tabela `tracks`.
- **Manuais (Navegador Real + Conta Spotify Premium):**
  - Inicialização do binário DRM/Widevine do Web Playback SDK no navegador Desktop, desbloqueio de *autoplay* no primeiro clique, continuidade do som durante navegação no `AppShell`, funcionamento das teclas multimídia via Media Session API com janela minimizada e fallback Spotify Connect em navegador mobile.

## Out of Scope

- Download, extração, gravação, cache offline ou redistribuição de fluxos/arquivos de áudio do Spotify.
- Reprodução de áudio local com o navegador completamente fechado (sem um **Dispositivo Connect** externo aberto).
- Sincronização bidirecional de **Playlists Locais** ou **Faixas Favoritas** com as playlists ou biblioteca remota da conta oficial do Spotify.
- Uso de endpoints descontinuados pelo Spotify para aplicativos em *Development Mode* (`recommendations`, `audio-features`, `audio-analysis`, `related-artists`, `artists/{id}/top-tracks`, `browse/featured-playlists`, `browse/new-releases`).
- Solicitação de *Extended Quota Mode* comercial junto ao Spotify (o projeto opera dentro do limite de até 5 usuários do *Development Mode*).
- Uso de AdminLTE 3 ou Redis no MVP.

## Further Notes

- **Ambiente Local Windows + Docker:** O banco PostgreSQL 16 será provido via `docker-compose.yml` local mapeado em `127.0.0.1:5432`, enquanto o PHP, Composer e Node rodam diretamente no host Windows servindo a aplicação em `http://127.0.0.1:8000`.
- **Referências de Domínio e Arquitetura:** Consulte o vocabulário canônico em `GLOSSARY.md` e as decisões arquiteturais em `docs/adr/0001-livewire-2-single-route-app-shell.md`, `docs/adr/0002-local-library-and-normalized-tracks.md` e `docs/adr/0003-hybrid-playback-sdk-and-spotify-connect.md`.

