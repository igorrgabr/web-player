# 05: Criação, Ordenação e Reprodução de Playlists Locais

**What to build:** Permitir que o **Ouvinte** crie, renomeie, edite a descrição e exclua **Playlists Locais** exibidas na barra lateral, adicione **Faixas** a qualquer **Playlist Local** a partir da busca ou de álbuns/favoritos, remova e reordene a sequência das **Faixas** dentro da playlist e reproduza a **Playlist Local** completa como um **Contexto de Reprodução**.

**Blocked by:** 03: Pesquisa de Catálogo (Faixas, Álbuns e Artistas) e Reprodução de Contexto

**Status:** resolved

- [x] O **Ouvinte** pode criar uma nova **Playlist Local** (com nome obrigatório e descrição opcional), que passa a aparecer imediatamente na barra lateral de navegação.
- [x] O **Ouvinte** pode editar o nome e a descrição de uma **Playlist Local** existente ou excluí-la.
- [x] O **Ouvinte** pode adicionar qualquer **Faixa** a uma de suas **Playlists Locais**, atribuindo automaticamente a próxima posição sequencial (`position`) ao final da lista.
- [x] Tentar adicionar uma **Faixa** que já pertence àquela mesma **Playlist Local** é bloqueado pela regra de unicidade e exibe um aviso amigável ao **Ouvinte**.
- [x] Dentro da página da **Playlist Local**, o **Ouvinte** pode remover **Faixas** ou alterar a ordem delas (movendo faixas para cima ou para baixo), mantendo a sequência de posições consistente.
- [x] Clicar para tocar a **Playlist Local** (ou uma **Faixa** específica dentro dela) envia todas as faixas da playlist na ordem definida pelo **Ouvinte** como **Contexto de Reprodução** com o respectivo `offset`.
- [x] Um **Ouvinte** não pode visualizar, editar ou excluir **Playlists Locais** pertencentes a outro **Ouvinte**.
- [x] Testes automatizados validam CRUD de **Playlists Locais**, adição de faixas, bloqueio de duplicatas na mesma playlist, remoção, reordenação de posições, isolamento entre **Ouvintes** e reprodução ordenada.

