<div class="flex-1 flex overflow-hidden">
    <!-- Barra Lateral de Navegação -->
    <aside class="w-64 bg-black border-r border-zinc-900 p-5 flex flex-col justify-between shrink-0">
        <div class="space-y-6">
            <div class="flex items-center gap-3 px-2">
                <div class="w-8 h-8 rounded-full bg-emerald-500 flex items-center justify-center text-zinc-950 font-bold">
                    W
                </div>
                <span class="font-bold tracking-tight text-white">Web Player</span>
            </div>

            <nav class="space-y-1" aria-label="Navegação Principal">
                <button
                    type="button"
                    wire:click="navigateTo('home')"
                    class="w-full flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition {{ $activeView === 'home' ? 'bg-zinc-800 text-white' : 'text-zinc-400 hover:text-white hover:bg-zinc-900' }}"
                >
                    <span>Início</span>
                </button>

                <button
                    type="button"
                    wire:click="navigateTo('search')"
                    class="w-full flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition {{ $activeView === 'search' ? 'bg-zinc-800 text-white' : 'text-zinc-400 hover:text-white hover:bg-zinc-900' }}"
                >
                    <span>Buscar</span>
                </button>

                <button
                    type="button"
                    wire:click="navigateTo('favorites')"
                    class="w-full flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition {{ $activeView === 'favorites' ? 'bg-zinc-800 text-white' : 'text-zinc-400 hover:text-white hover:bg-zinc-900' }}"
                >
                    <span>Faixas Favoritas</span>
                </button>
            </nav>
        </div>

        <div class="pt-4 border-t border-zinc-900 flex items-center justify-between px-2">
            <div class="truncate">
                <p class="text-xs text-zinc-400">Ouvinte</p>
                <p class="text-sm font-medium text-zinc-200 truncate">{{ auth()->user()->name }}</p>
            </div>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="text-xs text-zinc-400 hover:text-red-400 transition">
                    Sair
                </button>
            </form>
        </div>
    </aside>

    <!-- Área Central Dinâmica -->
    <main class="flex-1 overflow-y-auto bg-gradient-to-b from-zinc-900 to-zinc-950 p-8">
        @if ($activeView === 'home')
            <section>
                <h1 class="text-2xl font-bold text-white mb-2">Início</h1>
                <p class="text-sm text-zinc-400">Bem-vindo ao seu Web Player pessoal conectado ao Spotify Premium.</p>
            </section>
        @elseif ($activeView === 'search')
            <section>
                <h1 class="text-2xl font-bold text-white mb-2">Pesquisar no catálogo</h1>
                <p class="text-sm text-zinc-400">Busque Faixas, Artistas e Álbuns do catálogo do Spotify.</p>
            </section>
        @elseif ($activeView === 'favorites')
            <section>
                <h1 class="text-2xl font-bold text-white mb-2">Faixas Favoritas</h1>
                <p class="text-sm text-zinc-400">Sua coleção pessoal de Faixas curtidas.</p>
            </section>
        @elseif ($activeView === 'playlist')
            <section>
                <h1 class="text-2xl font-bold text-white mb-2">Playlist Local #{{ $contextId }}</h1>
            </section>
        @elseif ($activeView === 'album')
            <section>
                <h1 class="text-2xl font-bold text-white mb-2">Álbum #{{ $contextId }}</h1>
            </section>
        @elseif ($activeView === 'artist')
            <section>
                <h1 class="text-2xl font-bold text-white mb-2">Artista #{{ $contextId }}</h1>
            </section>
        @endif
    </main>
</div>
