@extends('layouts.public')

@section('title', 'GearHub · Shared equipment, made simple')

@section('content')
    <section class="mx-auto grid max-w-3xl gap-8">
        <div class="grid gap-4">
            <p class="font-mono text-xs font-medium uppercase tracking-wider text-leaf">Shared equipment, made simple</p>
            <h1 class="max-w-2xl text-4xl font-bold leading-tight tracking-tight text-ink sm:text-5xl">
                Borrow gear from your organization.
            </h1>
            <p class="max-w-xl text-lg leading-relaxed text-muted">
                GearHub helps organizations share equipment with their communities.
            </p>
        </div>

        <form method="GET" action="{{ route('home') }}" class="grid gap-3 rounded-xl border border-border bg-white p-5 sm:p-6">
            <label for="q" class="text-sm font-bold text-ink">Find your organization</label>
            <div class="grid gap-3 sm:grid-cols-[1fr_auto]">
                <input
                    id="q"
                    name="q"
                    type="search"
                    value="{{ old('q', $searchTerm) }}"
                    placeholder="Organization name or web address"
                    autocomplete="organization"
                    class="min-h-11 w-full rounded-lg border border-border bg-white px-3 py-2 text-sm text-ink outline-none focus:border-leaf focus:ring-2 focus:ring-leaf/20"
                >
                <x-button type="submit">Search</x-button>
            </div>
            @error('q')
                <p class="text-sm text-red-800">{{ $message }}</p>
            @enderror
        </form>

        @if ($searchTerm !== '')
            <section class="grid gap-4" aria-label="Organization search results" aria-live="polite">
                <div class="flex flex-wrap items-baseline justify-between gap-2">
                    <h2 class="font-mono text-xs font-medium uppercase tracking-wider text-leaf">Search results</h2>
                    <p class="text-sm text-muted">
                        {{ $organizations->total() }} {{ $organizations->total() === 1 ? 'organization' : 'organizations' }} found
                    </p>
                </div>

                <div class="grid gap-3">
                    @forelse ($organizations as $organization)
                        <a
                            href="{{ route('organizations.show', ['organization' => $organization->slug]) }}"
                            class="grid gap-1 rounded-xl border border-border bg-white p-4 transition hover:bg-sage focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-leaf sm:p-5"
                        >
                            <h3 class="font-bold text-ink">{{ $organization->name }}</h3>
                            @if ($organization->description)
                                <p class="text-sm leading-relaxed text-muted">{{ $organization->description }}</p>
                            @endif
                        </a>
                    @empty
                        <p class="rounded-xl border border-border bg-white p-4 text-sm text-muted sm:p-5">
                            No organizations found. Check the name or web address and try again.
                        </p>
                    @endforelse
                </div>

                @if ($organizations->hasPages())
                    <nav class="flex flex-wrap items-center justify-between gap-3" aria-label="Search result pages">
                        <p class="text-sm text-muted">Page {{ $organizations->currentPage() }} of {{ $organizations->lastPage() }}</p>
                        <div class="flex gap-2">
                            @if ($organizations->previousPageUrl())
                                <x-button href="{{ $organizations->previousPageUrl() }}" variant="secondary">Previous</x-button>
                            @endif
                            @if ($organizations->nextPageUrl())
                                <x-button href="{{ $organizations->nextPageUrl() }}" variant="secondary">Next</x-button>
                            @endif
                        </div>
                    </nav>
                @endif
            </section>
        @endif
    </section>
@endsection
