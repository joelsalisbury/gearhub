<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="color-scheme" content="light">
    <title>@yield('title', 'Platform administration · GearHub')</title>
    @fonts(['ubuntu-sans', 'ibm-plex-mono'])
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-cream font-sans text-ink antialiased">
    <div class="flex min-h-screen flex-col">
        <header class="border-b border-border">
            <div class="mx-auto flex w-full max-w-6xl flex-wrap items-center justify-between gap-4 px-5 py-4 md:px-8">
                <a href="{{ route('home') }}" class="font-mono text-lg font-semibold tracking-tight text-ink">
                    gearhub<span class="text-leaf">.</span>
                </a>
                <div class="flex items-center gap-4">
                    <span class="text-xs font-medium uppercase tracking-wide text-muted">Platform administration</span>
                    <span class="max-w-36 truncate text-sm font-medium text-muted">{{ auth()->user()->name }}</span>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <x-button type="submit" variant="secondary">Sign out</x-button>
                    </form>
                </div>
            </div>
            <nav class="mx-auto flex w-full max-w-6xl gap-6 px-5 pb-3 md:px-8" aria-label="Platform administration">
                <a
                    href="{{ route('platform.organizations.index') }}"
                    @if (request()->routeIs('platform.organizations.*')) aria-current="page" @endif
                    class="border-b-2 pb-2 text-sm {{ request()->routeIs('platform.organizations.*') ? 'border-forest font-bold text-forest' : 'border-transparent text-muted hover:text-forest' }}"
                >
                    Organizations
                </a>
            </nav>
        </header>

        <main class="mx-auto w-full max-w-6xl flex-1 px-5 py-10 md:px-8 md:py-14">
            @if (session('status'))
                <p class="mb-6 rounded-lg border border-border bg-sage px-4 py-3 text-sm text-forest" role="status">
                    {{ session('status') }}
                </p>
            @endif
            @yield('content')
        </main>

        <footer class="border-t border-border">
            <div class="mx-auto w-full max-w-6xl px-5 py-5 text-xs text-muted md:px-8">
                GearHub · Platform administration
            </div>
        </footer>
    </div>
</body>
</html>
