@extends('layouts.public')

@section('title', 'Choose a workspace · GearHub')

@section('content')
    <section class="mx-auto grid max-w-3xl gap-6">
        <div class="grid gap-2">
            <p class="font-mono text-xs font-medium uppercase tracking-wider text-leaf">Staff workspace</p>
            <h1 class="text-3xl font-bold tracking-tight text-ink">Choose an organization</h1>
            <p class="text-sm leading-relaxed text-muted">Open a workspace where you have staff access.</p>
        </div>

        @if ($organizations->isEmpty())
            <div class="grid gap-3 rounded-xl border border-border bg-white p-5 sm:p-6">
                <h2 class="text-lg font-bold text-ink">No active workspaces yet</h2>
                <p class="text-sm leading-relaxed text-muted">
                    Your account is not a staff member of an active organization. Ask an organization administrator to add your account.
                </p>
                <div><x-button href="{{ route('home') }}" variant="secondary">Find an organization</x-button></div>
            </div>
        @else
            <div class="grid gap-3">
                @foreach ($organizations as $organization)
                    <a href="{{ route('organizations.admin', ['organization' => $organization->slug]) }}" class="grid gap-1 rounded-xl border border-border bg-white p-4 hover:bg-sage focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-leaf sm:p-5">
                        <span class="font-bold text-ink">{{ $organization->name }}</span>
                        @if ($organization->description)
                            <span class="text-sm leading-relaxed text-muted">{{ $organization->description }}</span>
                        @endif
                    </a>
                @endforeach
            </div>
        @endif
    </section>
@endsection
