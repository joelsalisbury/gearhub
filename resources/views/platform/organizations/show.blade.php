@extends('layouts.platform')

@section('title', $organization->name.' · Platform · GearHub')

@section('content')
    <section class="grid gap-8">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div class="grid gap-2">
                <a href="{{ route('platform.organizations.index') }}" class="text-sm font-bold text-forest hover:text-ink">← Organizations</a>
                <p class="font-mono text-xs font-medium uppercase tracking-wider text-leaf">Organization</p>
                <h1 class="text-3xl font-bold tracking-tight text-ink">{{ $organization->name }}</h1>
                <p class="font-mono text-sm text-muted">/{{ $organization->slug }}</p>
            </div>
            <div class="flex flex-wrap gap-3">
                <x-button href="{{ route('organizations.admin', ['organization' => $organization->slug]) }}" variant="secondary">Open workspace</x-button>
                <x-button href="{{ route('platform.organizations.edit', $organization) }}">Edit organization</x-button>
            </div>
        </div>

        <div class="grid gap-4 rounded-xl border border-border bg-white p-5 sm:grid-cols-2 sm:p-6">
            <div>
                <h2 class="text-xs font-bold uppercase tracking-wide text-muted">Status</h2>
                <p class="mt-1 text-sm font-bold {{ $organization->isActive() ? 'text-forest' : 'text-muted' }}">
                    {{ $organization->isActive() ? 'Active' : 'Deactivated' }}
                </p>
            </div>
            <div>
                <h2 class="text-xs font-bold uppercase tracking-wide text-muted">Parent organization</h2>
                <p class="mt-1 text-sm text-ink">{{ $organization->parent?->name ?? 'None' }}</p>
            </div>
            <div>
                <h2 class="text-xs font-bold uppercase tracking-wide text-muted">Timezone</h2>
                <p class="mt-1 text-sm text-ink">{{ $organization->timezone }}</p>
            </div>
            @if ($organization->description)
                <div class="sm:col-span-2">
                    <h2 class="text-xs font-bold uppercase tracking-wide text-muted">Description</h2>
                    <p class="mt-1 text-sm leading-relaxed text-ink">{{ $organization->description }}</p>
                </div>
            @endif
        </div>

        <section class="grid gap-4">
            <div>
                <h2 class="text-xl font-bold text-ink">Staff</h2>
                <p class="mt-1 text-sm text-muted">Memberships apply only to this organization.</p>
            </div>
            <div class="overflow-x-auto rounded-xl border border-border bg-white">
                <table class="w-full min-w-[30rem] text-left text-sm">
                    <thead class="border-b border-border bg-sage/60 text-xs uppercase tracking-wide text-muted">
                        <tr><th class="px-4 py-3">Name</th><th class="px-4 py-3">Email</th><th class="px-4 py-3">Role</th></tr>
                    </thead>
                    <tbody class="divide-y divide-border">
                        @forelse ($organization->users->sortBy('name') as $user)
                            <tr>
                                <td class="px-4 py-3 font-medium text-ink">{{ $user->name }}</td>
                                <td class="px-4 py-3 text-muted">{{ $user->email }}</td>
                                <td class="px-4 py-3 text-muted">{{ ucfirst($user->pivot->role) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="3" class="px-4 py-5 text-muted">No staff members yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        <section class="grid gap-3 border-t border-border pt-6 sm:max-w-xl">
            <h2 class="text-lg font-bold text-ink">{{ $organization->isActive() ? 'Deactivate organization' : 'Reactivate organization' }}</h2>
            <p class="text-sm leading-relaxed text-muted">
                @if ($organization->isActive())
                    Deactivation keeps this organization's data and memberships but blocks public and normal staff access.
                @else
                    Reactivation restores public and normal staff access for this organization.
                @endif
            </p>
            <form method="POST" action="{{ route('platform.organizations.status', $organization) }}">
                @csrf
                @method('PATCH')
                <x-button type="submit" variant="secondary">
                    {{ $organization->isActive() ? 'Deactivate organization' : 'Reactivate organization' }}
                </x-button>
            </form>
        </section>
    </section>
@endsection
