# Web Player Pessoal com Spotify

Aplicativo de streaming musical pessoal onde ouvintes com assinatura Spotify Premium exploram o catálogo do Spotify, organizam sua própria biblioteca de playlists e favoritos, e reproduzem músicas completas.

## Language

**Ouvinte (Listener)**:
Pessoa autenticada na aplicação que possui uma Conta Spotify vinculada com assinatura Premium ativa e mantém sua própria biblioteca musical isolada.
_Evitar_: Cliente, assinante, perfil

**Conta Spotify (Spotify Account)**:
Vínculo de identidade e autorização do Ouvinte junto ao Spotify que habilita o acesso ao catálogo e à reprodução de músicas completas.
_Evitar_: Conta externa, integração, credencial

**Faixa (Track)**:
Obra musical individual identificada no catálogo do Spotify, composta por título, artista principal, álbum, capa e duração.
_Evitar_: Música, som, áudio, arquivo

**Playlist Local (Local Playlist)**:
Coleção nomeada e ordenada de Faixas criada e mantida exclusivamente pelo Ouvinte dentro da aplicação, independente das playlists existentes na conta oficial do Spotify.
_Evitar_: Lista de reprodução, playlist do Spotify, álbum customizado

**Faixa Favorita (Favorite Track)**:
Faixa marcada pelo Ouvinte como curtida dentro da sua biblioteca pessoal na aplicação, sem vínculo com as "Músicas Curtidas" da conta oficial do Spotify.
_Evitar_: Like, música salva no Spotify, bookmark

**Dispositivo de Reprodução (Playback Device)**:
Destino ativo onde o áudio é executado ou controlado pela sessão do Ouvinte — seja o próprio Reprodutor Web embutido no navegador ou um Dispositivo Connect externo.
_Evitar_: Caixa de som, cliente de áudio, saída

**Reprodutor Web (Web Player)**:
Dispositivo de Reprodução que decodifica e emite o áudio diretamente na aba aberta do navegador do Ouvinte.
_Evitar_: Player local, iframe de áudio, emulador

**Dispositivo Connect (Connect Device)**:
Aplicativo ou aparelho externo oficial do Spotify (como o app no celular ou desktop) controlado remotamente pela aplicação quando o Reprodutor Web não é utilizado.
_Evitar_: Player secundário, escravo, espelho

**Preferências de Reprodução (Playback Preferences)**:
Escolhas persistentes do Ouvinte sobre o comportamento padrão da sessão de áudio, incluindo volume inicial, modo de repetição e ordem aleatória.
_Evitar_: Configurações do sistema, ajustes de conta

**Contexto de Reprodução (Playback Context)**:
Sequência ordenada de Faixas (originada de uma Playlist Local, da lista de Faixas Favoritas, de um Álbum ou de um resultado de busca) enviada ao Dispositivo de Reprodução a partir de uma posição inicial (`offset`), permitindo avançar, retroceder, repetir ou embaralhar dentro daquele conjunto.
_Evitar_: Lista avulsa, buffer de áudio

**Histórico de Reprodução (Play History)**:
Registro cronológico das Faixas iniciadas pelo Ouvinte na aplicação, utilizado para alimentar a seção de faixas tocadas recentemente na tela inicial e retomar a última sessão.
_Evitar_: Log de auditoria, estatísticas globais


