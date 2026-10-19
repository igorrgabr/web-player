<!DOCTYPE html>
<html lang="pt-BR" class="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'Web Player') }}</title>
    <link href="{{ mix('css/app.css') }}" rel="stylesheet">
    @livewireStyles
</head>
<body class="h-screen overflow-hidden bg-zinc-950 text-zinc-100 flex flex-col antialiased">
    @livewire('app-shell')

    <x-player-bar :preferences="$preferences" />

    @livewireScripts
    <script src="https://sdk.scdn.co/spotify-player.js" defer></script>
    <script src="{{ mix('js/app.js') }}" defer></script>
</body>
</html>
