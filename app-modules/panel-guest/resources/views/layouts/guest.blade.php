<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'H-Vault' }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="min-h-screen bg-zinc-50 dark:bg-zinc-900 text-zinc-900 dark:text-zinc-100 antialiased">
    <div class="max-w-2xl mx-auto px-4 py-8 sm:py-12">
        <header class="text-center mb-8">
            <a href="{{ route('guest.landing') }}" class="inline-block">
                <h1 class="text-3xl font-bold tracking-tight">H-Vault</h1>
                <p class="text-sm text-zinc-500 dark:text-zinc-400 mt-1">Temporary file hosting</p>
            </a>
        </header>

        <main>
            {{ $slot }}
        </main>

        <footer class="mt-12 text-center text-xs text-zinc-400 dark:text-zinc-500">
            <p>&copy; {{ date('Y') }} He4rt Developers. All rights reserved.</p>
        </footer>
    </div>
    @livewireScripts
</body>
</html>
