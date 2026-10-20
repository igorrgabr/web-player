# 06: Histórico de Reprodução na Tela Inicial e Instalação PWA Responsiva

**What to build:** Registrar automaticamente no **Histórico de Reprodução** cada **Faixa** iniciada pelo **Ouvinte**, alimentar a tela Inicial (`Home`) com atalhos para **Faixas Favoritas**, **Playlists Locais** e a seção *"Tocadas Recentemente"* (além de pré-carregar visualmente no rodapé a última **Faixa** ouvida ao reabrir a aplicação), e concluir a experiência mobile responsiva com suporte a instalação como PWA.

**Blocked by:** 04: Biblioteca de Faixas Favoritas e Reprodução Sequencial, 05: Criação, Ordenação e Reprodução de Playlists Locais

**Status:** resolved

- [x] Sempre que o **Ouvinte** inicia a reprodução de uma **Faixa** ou **Contexto de Reprodução**, o evento é registrado no **Histórico de Reprodução** e a referência da última **Faixa** tocada é atualizada nas **Preferências de Reprodução**.
- [x] A tela Inicial (`Home`) exibe os cards de acesso rápido para **Faixas Favoritas** e **Playlists Locais** do **Ouvinte**, seguidos pela lista das últimas **Faixas** do seu **Histórico de Reprodução** prontas para tocar.
- [x] Ao abrir a aplicação em uma nova sessão, o rodapé do player exibe os metadados da última **Faixa** tocada pelo **Ouvinte**, permitindo retomar a reprodução com um clique.
- [x] A interface adapta a navegação lateral e o rodapé do player para telas móveis sem perda de funcionalidade.
- [x] A aplicação disponibiliza `manifest.webmanifest` válido e Service Worker enxuto (cacheando apenas ativos estáticos sem interferir em rotas autenticadas ou OAuth), habilitando a instalação como PWA no navegador.
- [x] Testes automatizados validam o registro no **Histórico de Reprodução**, a exibição das faixas recentes na tela Inicial isoladas por **Ouvinte** e a persistência da última **Faixa** tocada.

