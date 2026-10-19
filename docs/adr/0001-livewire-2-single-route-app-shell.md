# App Shell de Rota Única no Laravel 8 e Livewire 2

Adotamos **Laravel 8.x + Livewire 2.x + Tailwind CSS 3 + Alpine.js v3** com navegação interna baseada em um componente coordenador de rota única (`AppShell` sincronizado via `$queryString`) e isolamento do rodapé de áudio com `wire:ignore`. Como o Livewire 2 não possui a diretiva `@persist` introduzida no Livewire 3, qualquer navegação tradicional entre rotas Blade causaria recarregamento completo do documento HTML, destruindo a instância do Spotify Web Playback SDK e interrompendo a música; concentrar a troca de visões no `AppShell` preserva a conexão de áudio contínua sem abrir mão da stack Laravel 8 / Livewire 2.

