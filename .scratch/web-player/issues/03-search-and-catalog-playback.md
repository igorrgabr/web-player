# 03: Pesquisa de Catálogo (Faixas, Álbuns e Artistas) e Reprodução de Contexto

**What to build:** Permitir que o **Ouvinte** pesquise por termo livre e explore **Faixas**, Artistas e Álbuns do catálogo do Spotify, navegue até a página de detalhes de um Álbum ou de um Artista e inicie a reprodução de qualquer **Faixa** ou lista. Ao tocar uma **Faixa** dentro de uma lista de resultados ou de um Álbum, o sistema persiste/atualiza os metadados básicos daquelas músicas no catálogo local normalizado de **Faixas** e envia toda a lista como um **Contexto de Reprodução** (`uris` ordenadas + `offset` na faixa clicada) para o **Dispositivo de Reprodução** ativo.

**Blocked by:** 02: Casca Persistente (App Shell) e Reprodutor Web com Controle de Áudio

**Status:** ready-for-agent

- [ ] A busca consulta o catálogo do Spotify paginando com limite de 10 itens por requisição (compatível com o teto do *Development Mode*) e permite carregar mais resultados sob demanda.
- [ ] Os resultados da busca exibem **Faixas** (capa, título, artista, álbum, duração e ação de tocar), Artistas e Álbuns.
- [ ] Clicar em um Álbum exibe seu cabeçalho e todas as suas **Faixas**, permitindo tocar o álbum completo a partir de qualquer posição.
- [ ] Clicar em um Artista exibe seu perfil, suas principais **Faixas** obtidas via busca filtrada por artista e sua discografia de álbuns/singles (sem depender dos endpoints legados restritos no *Development Mode*).
- [ ] Clicar para reproduzir qualquer **Faixa** registra/atualiza seus metadados no catálogo local de **Faixas** e inicia a reprodução enviando o **Contexto de Reprodução** (`uris` da lista + `offset` da faixa escolhida).
- [ ] Respostas de limite de taxa (`429 Too Many Requests` com `Retry-After`) ou falhas da API do Spotify são tratadas graciosamente, exibindo feedback claro na interface sem quebrar a sessão.
- [ ] Testes automatizados (com `Http::fake()`) validam a pesquisa paginada, a visualização de Álbum e Artista, o *upsert* no catálogo local de **Faixas**, o envio de **Contexto de Reprodução** com `offset` e o tratamento de erro HTTP 429.

