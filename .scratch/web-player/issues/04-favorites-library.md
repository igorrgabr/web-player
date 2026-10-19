# 04: Biblioteca de Faixas Favoritas e Reprodução Sequencial

**What to build:** Permitir que o **Ouvinte** marque ou desmarque qualquer **Faixa** como **Faixa Favorita** (a partir dos resultados de busca, páginas de Álbum/Artista, **Playlists Locais** ou diretamente no rodapé do player) e acesse sua biblioteca pessoal de **Faixas Favoritas** para ouvir suas músicas curtidas em sequência como um **Contexto de Reprodução**.

**Blocked by:** 03: Pesquisa de Catálogo (Faixas, Álbuns e Artistas) e Reprodução de Contexto

**Status:** ready-for-agent

- [ ] O **Ouvinte** pode favoritar ou desfavoritar qualquer **Faixa** em um clique, garantindo que cada **Faixa** apareça no máximo uma vez na lista de favoritas daquele **Ouvinte**.
- [ ] O estado visual de favorito (ícone de coração ativo/inativo) permanece sincronizado entre as listas de conteúdo central e o rodapé do player.
- [ ] A seção de **Faixas Favoritas** lista todas as músicas curtidas pelo **Ouvinte** da mais recente para a mais antiga sem precisar consultar a API do Spotify para renderizar a tela.
- [ ] Clicar em Play no topo da biblioteca de **Faixas Favoritas** ou em qualquer música específica da lista envia todas as **Faixas Favoritas** como **Contexto de Reprodução** a partir da posição escolhida (`offset`).
- [ ] As **Faixas Favoritas** de um **Ouvinte** são estritamente isoladas das de outros **Ouvintes**.
- [ ] Testes automatizados validam adição, remoção, unicidade por **Ouvinte**, ordenação cronológica reversa e disparo do **Contexto de Reprodução** da lista de favoritas.

