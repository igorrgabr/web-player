# 02: Casca Persistente (App Shell) e Reprodutor Web com Controle de Áudio

**What to build:** Entregar a interface principal autenticada em formato *App Shell* de rota única (com barra lateral de navegação, área de conteúdo central dinâmica sincronizada com a URL via `$queryString` do Livewire 2 e rodapé do player isolado com `wire:ignore`) para que a troca entre seções nunca recarregue o documento HTML nem interrompa o áudio. O rodapé inicializa uma instância única do **Reprodutor Web** (Spotify Web Playback SDK) em navegadores compatíveis com DRM/EME (ou permite selecionar e controlar um **Dispositivo Connect** externo em dispositivos móveis), exibe os dados da **Faixa** atual, oferece controles funcionais de Play/Pause, Próxima, Anterior, barra de progresso contínua com *seek*, Volume/Mudo, Shuffle e Repeat (`off`, `context`, `track`), persiste as **Preferências de Reprodução** do **Ouvinte** e integra os controles com as teclas multimídia e o sistema operacional via Media Session API.

**Blocked by:** 01: Fundação da Aplicação e Login OAuth com Conta Spotify Premium

**Status:** ready-for-agent

- [ ] Navegar entre as seções principais na barra lateral atualiza a visão central e a query string da URL (suportando Voltar/Avançar e reload da aba) sem recarregar o documento HTML e sem recriar o rodapé do player.
- [ ] Em navegadores Desktop compatíveis com EME/DRM, o **Reprodutor Web** conecta-se ao Spotify usando o token da sessão, registra seu `device_id` e desbloqueia o contexto de áudio no primeiro gesto de clique do **Ouvinte** (`activateElement`).
- [ ] Quando o navegador não suporta o SDK embutido ou o **Ouvinte** escolhe outro aparelho ativo, o player opera sobre o **Dispositivo Connect** selecionado via API.
- [ ] Os controles de Play/Pause, Próxima, Anterior, barra de progresso (*seek* sem engasgo visual durante o arraste), Volume, Shuffle e Repeat funcionam e refletem o estado real da reprodução no rodapé.
- [ ] O volume padrão, modo de repetição, estado de *shuffle* e dispositivo preferido são salvos e restaurados a partir das **Preferências de Reprodução** do **Ouvinte**.
- [ ] Os metadados da **Faixa** ativa e as ações de mídia (`play`, `pause`, `previoustrack`, `nexttrack`, `seekto`) são sincronizados com `navigator.mediaSession` para controle em segundo plano.
- [ ] Testes automatizados (com `Http::fake()`) validam os endpoints de transferência de **Dispositivo de Reprodução**, comando de reprodução e persistência das **Preferências de Reprodução** por **Ouvinte**.

