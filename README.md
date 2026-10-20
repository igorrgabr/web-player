# Web Player 🎵

> Aplicação web pessoal de streaming de música integrada com o Spotify Premium (Individual e Family), desenvolvida com Laravel, Livewire 2, Alpine.js v3, Tailwind CSS e PostgreSQL.

[![PHP Version](https://img.shields.io/badge/PHP-8.2%2B-777BB4?logo=php&logoColor=white)](https://www.php.net)
[![Laravel](https://img.shields.io/badge/Laravel-8.x-FF2D20?logo=laravel&logoColor=white)](https://laravel.com)
[![Livewire](https://img.shields.io/badge/Livewire-2.x-FB70A9?logo=livewire&logoColor=white)](https://laravel-livewire.com)
[![Alpine.js](https://img.shields.io/badge/Alpine.js-3.x-8BC0D0?logo=alpinedotjs&logoColor=white)](https://alpinejs.dev)
[![Tailwind CSS](https://img.shields.io/badge/Tailwind_CSS-3.x-38B2AC?logo=tailwind-css&logoColor=white)](https://tailwindcss.com)
[![PostgreSQL](https://img.shields.io/badge/PostgreSQL-16-336791?logo=postgresql&logoColor=white)](https://www.postgresql.org)
[![Spotify Web API](https://img.shields.io/badge/Spotify-Web_API_%26_SDK-1DB954?logo=spotify&logoColor=white)](https://developer.spotify.com)
[![Tests](https://img.shields.io/badge/Tests-26_passed-success)](https://phpunit.de)
[![License](https://img.shields.io/badge/License-MIT-blue.svg)](LICENSE)

---

## 📖 Visão Geral

O **Web Player** é uma aplicação pessoal focada em autonomia e controle de biblioteca: enquanto o streaming de áudio e a busca de catálogo utilizam a infraestrutura oficial do Spotify, **a sua biblioteca musical (Playlists Locais, Faixas Favoritas, Histórico de Reprodução e Preferências) pertence exclusivamente a você**, persistida em um banco PostgreSQL local e isolada por ouvinte.

A aplicação foi projetada para funcionar de forma contínua em Desktop e Mobile, combinando a **reprodução direta no navegador via Spotify Web Playback SDK** com a capacidade de controlar qualquer **Dispositivo Spotify Connect** da sua casa.

---

## ✨ Recursos

- 🎧 **Casca de Navegação Persistente (App Shell):** Arquitetura de rota única no Livewire sem recarregamento completo de página (`home`, `search`, `favorites`, `playlist`, `album`, `artist`), mantendo o tocador de áudio em reprodução contínua e isolado com `wire:ignore`.
- 🎛️ **Reprodutor de Áudio Híbrido:**
  - **Modo Web Playback SDK:** Reproduz áudio protegido por DRM diretamente no navegador (Chrome, Edge, Firefox).
  - **Modo Spotify Connect:** Detecta automaticamente dispositivos externos (Echo/Alexa, Smart TVs, consoles, mobile) e permite transferir e controlar a reprodução em tempo real.
  - Controles completos: Play/Pause, Próxima/Anterior, barra de busca (*seek drag*), controle de volume com mute/restore, repetição (`off`, `context`, `track`) e ordem aleatória (*shuffle*).
  - Sincronização com a **Media Session API** do sistema operacional (notificações de mídia, teclas multimídia e tela de bloqueio).
- 🔍 **Pesquisa de Catálogo (Compatível com Spotify Dev Mode 2024–2026):**
  - Pesquisa paginada por Faixas, Artistas e Álbuns respeitando o teto de 10 itens por requisição.
  - Visão detalhada de Álbum com lista de faixas e reprodução a partir de qualquer faixa.
  - Visão de Artista com perfil, discografia e faixas em destaque (sem depender de endpoints legados restritos).
  - Tratamento resiliente de respostas HTTP 429 (*Too Many Requests* com `Retry-After`).
- ❤️ **Biblioteca Local de Faixas Favoritas:**
  - Adicione ou remova qualquer faixa em um clique com atualização visual instantânea sincronizada entre as telas e a barra do player.
  - Listagem cronológica reversa instantânea, sem depender de requisições de rede ao Spotify para carregar a biblioteca.
  - Reprodução em sequência como um Contexto de Reprodução.
- 📋 **Gestão de Playlists Locais:**
  - Criação, edição de nome/descrição e exclusão de playlists.
  - Adição de faixas a partir de qualquer tela com prevenção de duplicatas na mesma playlist.
  - Reordenação da sequência de faixas (subir/descer) com persistência de posições e renumeração automática.
  - Reprodução ordenada da playlist completa com posicionamento via `offset`.
- 🕒 **Histórico de Reprodução e Tela Inicial:**
  - Registro automático de cada faixa ouvida com tipo de contexto (`album`, `artist`, `search`, `favorites`, `local_playlist`).
  - Tela inicial com atalhos rápidos e seção *"Tocadas Recentemente"* deduplicada.
  - Pré-carregamento visual da última faixa ouvida no rodapé ao abrir uma nova sessão da aplicação.
- 📱 **PWA e Responsividade:**
  - Layout totalmente adaptado para telas móveis e desktop.
  - Manifesto `manifest.webmanifest`, ícones e Service Worker para cache seguro de ativos estáticos e instalação standalone.

---

## 🏛️ Arquitetura e Decisões Técnicas

O projeto documenta formalmente suas principais decisões de arquitetura através de ADRs (*Architectural Decision Records*):

- [**ADR-0001: Casca Persistente com Livewire 2 (Single-Route App Shell)**](docs/adr/0001-livewire-2-single-route-app-shell.md)
  Mantém o componente `<x-player-bar>` montado e isolado com `wire:ignore` em Alpine.js, alternando o conteúdo central através do `$queryString` sem recarregar o ciclo de vida do SDK de áudio.
- [**ADR-0002: Biblioteca Local e Catálogo Normalizado de Faixas**](docs/adr/0002-local-library-and-normalized-tracks.md)
  Garante independência dos dados do ouvinte através da tabela normalizada `tracks` e tabelas relacionais locais (`favorite_tracks`, `local_playlists`, `playlist_tracks`, `play_histories`).
- [**ADR-0003: Reprodução Híbrida (SDK + Spotify Connect)**](docs/adr/0003-hybrid-playback-sdk-and-spotify-connect.md)
  Unifica a camada de reprodução para atender navegadores com suporte a DRM (Desktop) e dispositivos externos via REST API do Spotify Connect.

Consulte o [**Glossário do Domínio (GLOSSARY.md)**](GLOSSARY.md) para detalhes sobre a linguagem ubíqua adotada no projeto (*Ouvinte*, *Faixa*, *Contexto de Reprodução*, etc.).

---

## 🛠️ Tecnologias Utilizadas

- **Backend:** PHP 8.2+, Laravel 8.x, Laravel Sanctum, GuzzleHTTP
- **Frontend:** Livewire 2.x, Alpine.js 3.x, Tailwind CSS 3.x, Laravel Mix 6.x
- **Banco de Dados:** PostgreSQL 16 (via Docker)
- **Integração Externa:** Spotify Web API (OAuth 2.0 Authorization Code Flow com PKCE) e Spotify Web Playback SDK
- **Testes:** PHPUnit com suíte de testes de feature usando banco em memória SQLite e `Http::fake()`

---

## 📋 Pré-requisitos

Antes de iniciar, certifique-se de possuir em sua máquina:

1. **PHP 8.2 ou superior** com as extensões `pdo_pgsql`, `curl`, `mbstring`, `openssl`, `tokenizer` e `xml` habilitadas.
2. **Composer 2.x**
3. **Node.js (v18+) e NPM**
4. **Docker e Docker Compose** (para executar o PostgreSQL)
5. **Conta Spotify Premium** (individual ou familiar, obrigatória pela API do Spotify para controle de playback e SDK)
6. **Navegador compatível com DRM/EME Widevine** (Google Chrome, Microsoft Edge, Brave ou Firefox com DRM ativo).

---

## 🚀 Instalação e Execução

### 1. Clonar o Repositório

```bash
git clone https://github.com/seu-usuario/web-player.git
cd web-player
```

### 2. Instalar as Dependências

```bash
# Dependências do backend (PHP)
composer install

# Dependências do frontend (Node.js)
npm install
```

### 3. Criar a Aplicação no Spotify Developer Dashboard

1. Acesse o [Spotify Developer Dashboard](https://developer.spotify.com/dashboard) e faça login com sua conta Spotify.
2. Clique em **Create app** e configure:
   - **App name:** `Web Player Pessoal`
   - **App description:** `Player web pessoal para streaming`
   - **Redirect URIs:** adicione exatamente a URL de loopback:
     ```text
     http://127.0.0.1:8000/auth/spotify/callback
     ```
     > ⚠️ **Atenção:** Use estritamente `http://127.0.0.1:8000` e **nunca** `localhost`, em conformidade com as regras de segurança OAuth do Spotify.
   - Em **Which API/SDKs are you planning to use?**, selecione:
     - [x] **Web API**
     - [x] **Web Playback SDK**
3. Salve as alterações, acesse **Settings** do app e copie o **Client ID** e o **Client Secret**.
4. *(Opcional / Plano Familiar)*: Na aba **User Management**, adicione o nome e e-mail dos membros da família que utilizarão a aplicação no modo de desenvolvimento.

### 4. Configurar as Variáveis de Ambiente

Copie o arquivo de exemplo e gere a chave de criptografia da aplicação:

```bash
cp .env.example .env
php artisan key:generate
```

Abra o arquivo `.env` e preencha as credenciais obtidas no Dashboard:

```dotenv
APP_URL=http://127.0.0.1:8000

# Porta do PostgreSQL no Docker (altere para 54320 se já possuir outro Postgres na 5432)
DB_PORT=5432

SPOTIFY_CLIENT_ID=seu_client_id_aqui
SPOTIFY_CLIENT_SECRET=seu_client_secret_aqui
SPOTIFY_REDIRECT_URI=http://127.0.0.1:8000/auth/spotify/callback
```

### 5. Iniciar o Banco de Dados e Rodar as Migrations

Suba o container do PostgreSQL 16 e aplique o esquema do banco:

```bash
# Subir o banco de dados em segundo plano
docker compose up -d

# Executar as migrações
php artisan migrate
```

### 6. Compilar os Assets Front-end

Compile os arquivos CSS (Tailwind) e JavaScript (Alpine + Spotify SDK Manager):

```bash
# Para desenvolvimento (com hot-reload opcional via npm run watch)
npm run dev

# Ou para compilação otimizada de produção
npm run prod
```

### 7. Iniciar o Servidor Local

Execute o servidor embutido do Laravel vinculando-o ao endereço `127.0.0.1`:

```bash
php artisan serve --host=127.0.0.1 --port=8000
```

Abra seu navegador e acesse:
👉 **[http://127.0.0.1:8000](http://127.0.0.1:8000)**

Clique em **Entrar com Spotify**, autorize a aplicação e você será redirecionado para a casca do Web Player!

---

## 🧪 Testes Automatizados

A aplicação conta com suíte de testes de integração e ponta-a-ponta cobrindo todos os fluxos críticos (autenticação OAuth com PKCE, casca do player, busca paginada, visualização de álbuns e artistas, persistência de favoritas, gerenciamento de playlists e histórico).

Os testes são executados contra um banco SQLite em memória com todos os endpoints do Spotify mockados via `Http::fake()`, garantindo execução rápida e independente de credenciais de rede:

```bash
php artisan test
```

Para rodar uma suíte específica:

```bash
php artisan test tests/Feature/CatalogSearchTest.php
php artisan test tests/Feature/FavoritesTest.php
php artisan test tests/Feature/LocalPlaylistsTest.php
php artisan test tests/Feature/PlayHistoryAndHomeTest.php
```

---

## 🔍 Resolução de Problemas Comuns

### 1. Conflito de porta no Docker (`ports are not available: 127.0.0.1:5432`)
Se você já possui uma instância local do PostgreSQL rodando no seu computador (ou o serviço WinNAT/Hyper-V reservou a porta 5432):
1. Altere no `.env`: `DB_PORT=54320`
2. Execute:
   ```bash
   docker compose down
   docker compose up -d
   php artisan migrate
   ```

### 2. Erro SSL ou Bloqueio Corporativo (`ERR_CERT_AUTHORITY_INVALID` / FortiGate)
Se você estiver conectado a uma rede corporativa ou VPN com inspeção profunda de pacotes (ex.: Fortinet FortiGate, Zscaler) e receber bloqueios de categoria *Video/Audio* ao tentar abrir o login do Spotify:
- Desconecte a VPN corporativa temporariamente para autorizar a aplicação.
- Utilize sua rede doméstica ou compartilhe a conexão 4G/5G do celular (Hotspot pessoal).

### 3. Áudio não toca no navegador (Web Playback SDK)
- Certifique-se de que sua conta Spotify é **Premium** (a API do Spotify não permite streaming via SDK em contas gratuitas).
- Verifique se o navegador possui suporte e permissão para reprodução de conteúdo protegido (DRM/Widevine).
- O navegador exige interação do usuário para reproduzir áudio: clique em qualquer botão de reprodução da interface para autorizar o contexto de áudio.

---

## 📁 Estrutura do Código

```text
app/
├── Http/
│   ├── Controllers/
│   │   ├── Auth/SpotifyAuthController.php     # Autenticação OAuth 2.0 e refresh
│   │   └── Player/PlayerController.php        # Endpoints REST do tocador e dispositivos
│   ├── Livewire/
│   │   ├── Concerns/PlaysNormalizedTrackLists.php # Trait unificada de contexto de playback
│   │   ├── AppShell.php                       # Coordenador de navegação single-route
│   │   ├── HomeView.php                       # Tela inicial com atalhos e recentes
│   │   ├── SearchCatalog.php                  # Busca paginada no catálogo
│   │   ├── AlbumView.php                      # Visão de álbum e reprodução
│   │   ├── ArtistView.php                     # Perfil e discografia do artista
│   │   ├── FavoritesLibrary.php               # Coleção pessoal de faixas favoritas
│   │   └── PlaylistView.php                   # CRUD e reordenação de playlists locais
│   └── Middleware/
│       ├── EnforceLoopbackHost.php            # Redireciona localhost -> 127.0.0.1
│       └── EnsureSpotifyConnected.php         # Garante conta conectada e token válido
├── Models/
│   ├── LocalPlaylist.php                      # Playlist local do ouvinte
│   ├── LocalPlaylistTrack.php                 # Posição sequencial na playlist
│   ├── FavoriteTrack.php                      # Relação de faixas favoritas
│   ├── PlayHistory.php                        # Histórico de reproduções iniciadas
│   ├── SpotifyAccount.php                     # Tokens criptografados e perfil
│   ├── Track.php                              # Catálogo local normalizado
│   └── UserPreference.php                     # Volume, shuffle, repeat e última faixa
└── Services/
    └── Spotify/
        ├── SpotifyAuthService.php             # Ciclo de vida OAuth e rotação de tokens
        ├── SpotifyCatalogService.php          # Normalização de dados e busca
        ├── SpotifyHttpClient.php              # Cliente HTTP com retry e rate-limiting
        └── SpotifyPlayerService.php           # Controle de reprodução híbrida e histórico
```

---

## 📄 Licença

Este projeto é um software de código aberto licenciado sob a licença [MIT](LICENSE).
