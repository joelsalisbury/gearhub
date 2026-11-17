@extends('layouts.platform')

@section('title', 'Organizations · GearHub')

@section('content')
    <section class="grid gap-6">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div class="grid gap-2">
                <p class="font-mono text-xs font-medium uppercase tracking-wider text-leaf">Platform</p>
                <h1 class="text-3xl font-bold tracking-tight text-ink">Organizations</h1>
            </div>
            <x-button href="{{ route('platform.organizations.create') }}">Create organization</x-button>
        </div>

        <form method="GET" action="{{ route('platform.organizations.index') }}" class="grid gap-3 sm:grid-cols-[1fr_auto]">
            <label for="q" class="sr-only">Search organizations by name</label>
            <input
                id="q"
                name="q"
                type="search"
                value="{{ $searchTerm }}"
                placeholder="Search organizations by name"
                class="min-h-11 rounded-lg border border-border bg-white px-3 py-2 text-sm text-ink outline-none focus:border-leaf focus:ring-2 focus:ring-leaf/20"
            >
            <x-button type="submit" variant="secondary">Search</x-button>
        </form>

        <div class="overflow-x-auto rounded-xl border border-border bg-white">
            <table class="w-full min-w-[42rem] text-left text-sm">
                <thead class="border-b border-border bg-sage/60 text-xs uppercase tracking-wide text-muted">
                    <tr>
                        <th class="px-4 py-3 font-bold">Organization</th>
                        <th class="px-4 py-3 font-bold">Parent</th>
                        <th class="px-4 py-3 font-bold">Staff</th>
                        <th class="px-4 py-3 font-bold">Status</th>
                        <th class="px-4 py-3"><span class="sr-only">Actions</span></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-border">
                    @forelse ($organizations as $organization)
                        <tr>
                            <td class="px-4 py-4">
                                <a href="{{ route('platform.organizations.show', $organization) }}" class="font-bold text-forest hover:text-ink">
                                    {{ $organization->name }}
                                </a>
                                <p class="mt-1 font-mono text-xs text-muted">/{{ $organization->slug }}</p>
                            </td>
                            <td class="px-4 py-4 text-muted">{{ $organization->parent?->name ?? '—' }}</td>
                            <td class="px-4 py-4 text-muted">{{ $organization->users_count }}</td>
                            <td class="px-4 py-4">
                                <span class="text-sm {{ $organization->isActive() ? 'text-forest' : 'text-muted' }}">
                                    {{ $organization->isActive() ? 'Active' : 'Deactivated' }}
                                </span>
                            </td>
                            <td class="px-4 py-4 text-right">
                                <a href="{{ route('platform.organizations.edit', $organization) }}" class="font-bold text-forest hover:text-ink">Edit</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-8 text-center text-muted">
                                {{ $searchTerm === '' ? 'No organizations have been created yet.' : 'No organizations match your search.' }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($organizations->hasPages())
            <nav class="flex flex-wrap items-center justify-between gap-3" aria-label="Organization pages">
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
@endsection
