<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="color-scheme" content="light">
    <title>@yield('title', $organization->name.' · GearHub')</title>
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

                <div class="flex flex-wrap items-center gap-4">
                    <details class="relative">
                        <summary class="cursor-pointer list-none rounded-lg px-3 py-2 text-sm font-bold text-forest hover:bg-sage focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-leaf">
                            {{ $organization->name }} <span aria-hidden="true">▾</span>
                        </summary>
                        <div class="absolute right-0 z-10 mt-2 grid min-w-56 gap-1 rounded-lg border border-border bg-white p-2">
                            @foreach ($staffOrganizations as $staffOrganization)
                                <a
                                    href="{{ route('organizations.admin', ['organization' => $staffOrganization->slug]) }}"
                                    @if ($staffOrganization->is($organization)) aria-current="page" @endif
                                    class="rounded-md px-3 py-2 text-sm text-ink hover:bg-sage"
                                >
                                    {{ $staffOrganization->name }}
                                    @if ($staffOrganization->is($organization))
                                        <span class="text-xs text-muted">(current)</span>
                                    @endif
                                </a>
                            @endforeach
                        </div>
                    </details>

                    @if (auth()->user()->isPlatformAdmin())
                        <a href="{{ route('platform.organizations.index') }}" class="text-sm font-bold text-forest hover:text-ink">
                            Platform
                        </a>
                    @endif

                    <div class="flex items-center gap-3 border-l border-border pl-4">
                        <span class="max-w-36 truncate text-sm font-medium text-muted">{{ auth()->user()->name }}</span>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <x-button type="submit" variant="secondary">Sign out</x-button>
                        </form>
                    </div>
                </div>
            </div>

            <nav class="mx-auto flex w-full max-w-6xl gap-6 px-5 pb-3 md:px-8" aria-label="Organization workspace">
                <a
                    href="{{ route('organizations.admin', ['organization' => $organization->slug]) }}"
                    @if (request()->routeIs('organizations.admin')) aria-current="page" @endif
                    class="border-b-2 pb-2 text-sm {{ request()->routeIs('organizations.admin') ? 'border-forest font-bold text-forest' : 'border-transparent text-muted hover:text-forest' }}"
                >
                    Overview
                </a>
                <a
                    href="{{ route('organizations.admin.gear.index', ['organization' => $organization->slug]) }}"
                    @if (request()->routeIs('organizations.admin.gear.*')) aria-current="page" @endif
                    class="border-b-2 pb-2 text-sm {{ request()->routeIs('organizations.admin.gear.*') ? 'border-forest font-bold text-forest' : 'border-transparent text-muted hover:text-forest' }}"
                >
                    Gear
                </a>
                @can('manageSettings', $organization)
                    <a
                        href="{{ route('organizations.admin.settings.edit', ['organization' => $organization->slug]) }}"
                        @if (request()->routeIs('organizations.admin.settings.*')) aria-current="page" @endif
                        class="border-b-2 pb-2 text-sm {{ request()->routeIs('organizations.admin.settings.*') ? 'border-forest font-bold text-forest' : 'border-transparent text-muted hover:text-forest' }}"
                    >
                        Settings
                    </a>
                @endcan
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
                {{ $organization->name }} · GearHub workspace
            </div>
        </footer>
    </div>
</body>
</html>
