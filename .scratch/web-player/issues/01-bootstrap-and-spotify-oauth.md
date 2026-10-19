# 01: Fundação da Aplicação e Login OAuth com Conta Spotify Premium

**What to build:** Inicializar a aplicação web (Laravel 8, Livewire 2, Tailwind CSS 3, Alpine.js v3) conectada ao PostgreSQL 16 local (via container Docker) e operando estritamente no endereço de loopback `http://127.0.0.1:8000`. Um **Ouvinte** visitante vê a tela de boas-vindas e pode autenticar sua **Conta Spotify** via OAuth 2.0 oficial (com proteção anti-CSRF por `state`). No retorno do login, o sistema valida no perfil do Spotify se a conta possui assinatura Premium ativa, cria ou atualiza o registro do **Ouvinte** e de sua **Conta Spotify** armazenando `access_token` e `refresh_token` criptografados em repouso, fornece um endpoint autenticado para entregar um token válido ao navegador (renovando-o automaticamente com lock atômico quando faltarem menos de 5 minutos para expirar e preservando o `refresh_token` anterior se o Spotify não retornar um novo) e permite encerrar a sessão (logout).

**Blocked by:** None (can start immediately)

**Status:** ready-for-agent

- [ ] Acessos acidentais via `localhost` são redirecionados para `127.0.0.1` antes de iniciar o fluxo OAuth, garantindo consistência de sessão e conformidade com a regra de Redirect URI do Spotify.
- [ ] Iniciar o login redireciona para a página de autorização do Spotify com `state` criptograficamente aleatório salvo na sessão e os escopos `streaming`, `user-read-email`, `user-read-private`, `user-read-playback-state`, `user-modify-playback-state` e `user-read-currently-playing`.
- [ ] O callback OAuth rejeita requisições com `state` ausente ou divergente (`403 Forbidden`).
- [ ] Contas Spotify que não possuam `product === 'premium'` recebem aviso claro de que a reprodução exige assinatura Spotify Premium.
- [ ] Os campos `access_token` e `refresh_token` da **Conta Spotify** são gravados criptografados em repouso no banco de dados e vinculados ao **Ouvinte** de forma isolada.
- [ ] Consultar o token ativo da sessão renova transparentemente o `access_token` quando estiver expirado ou a menos de 5 minutos de expirar, preservando o `refresh_token` existente caso a resposta de renovação do Spotify não inclua um novo.
- [ ] Testes automatizados (usando `Http::fake()` como única costura externa) cobrem redirecionamento OAuth, falha de `state`, validação de conta Premium, criptografia dos tokens, isolamento entre **Ouvintes** e renovação automática de token expirado.

