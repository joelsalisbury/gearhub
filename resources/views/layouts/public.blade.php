<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="color-scheme" content="light">
    <title>@yield('title', 'GearHub')</title>
    @fonts(['ubuntu-sans', 'ibm-plex-mono'])
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-cream font-sans text-ink antialiased">
    <div class="flex min-h-screen flex-col">
        <header class="border-b border-border">
            <nav class="mx-auto flex w-full max-w-6xl items-center justify-between gap-5 px-5 py-5 md:px-8" aria-label="Main navigation">
                <a href="{{ route('home') }}" class="font-mono text-lg font-semibold tracking-tight text-ink">
                    gearhub<span class="text-leaf">.</span>
                </a>
                @guest
                    <a href="{{ route('login') }}" class="text-sm font-bold text-forest hover:text-ink">Staff sign in</a>
                @else
                    <a href="{{ route('home') }}" class="text-sm font-bold text-forest hover:text-ink">Find an organization</a>
                @endguest
            </nav>
        </header>

        <main class="mx-auto w-full max-w-6xl flex-1 px-5 py-12 md:px-8 md:py-16">
            @yield('content')
        </main>

        <footer class="border-t border-border">
            <div class="mx-auto w-full max-w-6xl px-5 py-5 text-xs text-muted md:px-8">
                GearHub · Shared equipment, made simple.
            </div>
        </footer>
    </div>
</body>
</html>
