<!DOCTYPE html>
<html lang="pt-BR" class="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'Web Player') }}</title>
    <link href="{{ mix('css/app.css') }}" rel="stylesheet">
</head>
<body class="min-h-screen bg-zinc-950 text-zinc-100 flex items-center justify-center p-6 antialiased">
    <main class="w-full max-w-md bg-zinc-900/90 border border-zinc-800 rounded-2xl p-8 shadow-2xl text-center">
        <div class="w-14 h-14 rounded-full bg-emerald-500/10 border border-emerald-500/30 flex items-center justify-center mx-auto mb-6">
            <svg class="w-8 h-8 text-emerald-400" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                <path d="M12 2C6.477 2 2 6.477 2 12s4.477 10 10 10 10-4.477 10-10S17.523 2 12 2zm4.586 14.424c-.18.295-.563.387-.857.207-2.348-1.435-5.304-1.76-8.785-.964-.335.077-.67-.133-.746-.469-.077-.335.132-.67.469-.746 3.809-.871 7.077-.496 9.713 1.115.293.18.386.563.206.857zm1.224-2.722c-.226.367-.706.482-1.072.257-2.687-1.652-6.785-2.131-9.965-1.166-.413.125-.848-.106-.973-.517-.125-.413.106-.848.517-.973 3.632-1.102 8.147-.568 11.236 1.328.366.226.481.705.257 1.071zm.105-2.835C14.692 8.95 9.375 8.775 6.297 9.71c-.493.15-1.016-.128-1.166-.62-.149-.493.129-1.016.621-1.166 3.532-1.073 9.404-.865 13.115 1.338.445.264.59.838.327 1.282-.264.444-.838.59-1.279.323z"/>
            </svg>
        </div>

        <h1 class="text-2xl font-bold tracking-tight text-white mb-2">
            Web Player Pessoal
        </h1>
        <p class="text-sm text-zinc-400 mb-6">
            Conecte sua Conta Spotify Premium para explorar o catálogo, organizar suas Playlists Locais e reproduzir Faixas completas.
        </p>

        @if ($errors->has('spotify'))
            <div role="alert" class="mb-6 rounded-xl bg-red-950/60 border border-red-800/80 p-4 text-left text-sm text-red-200">
                {{ $errors->first('spotify') }}
            </div>
        @endif

        @auth
            <div class="space-y-3">
                <a href="{{ route('player.index') }}" class="block w-full rounded-full bg-emerald-500 hover:bg-emerald-400 text-zinc-950 font-semibold py-3 px-6 transition">
                    Abrir Reprodutor Web
                </a>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="w-full rounded-full border border-zinc-700 hover:border-zinc-500 text-zinc-300 text-sm font-medium py-2.5 px-6 transition">
                        Encerrar sessão
                    </button>
                </form>
            </div>
        @else
            <a href="{{ route('spotify.redirect') }}" class="inline-flex items-center justify-center w-full rounded-full bg-emerald-500 hover:bg-emerald-400 text-zinc-950 font-semibold py-3 px-6 transition">
                Entrar com Spotify Premium
            </a>
        @endauth
    </main>
</body>
</html>
