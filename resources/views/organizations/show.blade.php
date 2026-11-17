@extends('layouts.public')

@section('title', $organization->name.' · GearHub')

@section('content')
    <section class="mx-auto grid max-w-3xl gap-8">
        <div class="grid gap-4">
            <p class="font-mono text-xs font-medium uppercase tracking-wider text-leaf">Organization</p>
            <h1 class="text-4xl font-bold leading-tight tracking-tight text-ink sm:text-5xl">{{ $organization->name }}</h1>
            @if ($organization->description)
                <p class="max-w-2xl text-lg leading-relaxed text-muted">{{ $organization->description }}</p>
            @endif
        </div>

        <div class="grid gap-4 rounded-xl border border-border bg-white p-5 sm:p-6">
            <div>
                <h2 class="text-lg font-bold text-ink">Gear catalog</h2>
                @if ($hasPublicGear)
                    <p class="mt-1 text-sm leading-relaxed text-muted">Browse the equipment {{ $organization->name }} lists for its community.</p>
                @else
                    <p class="mt-1 text-sm leading-relaxed text-muted">This organization has not listed any public gear yet.</p>
                @endif
            </div>
            <div class="flex flex-wrap items-center gap-4">
                <x-button href="{{ route('organizations.gear.index', ['organization' => $organization->slug]) }}">Browse gear</x-button>
                <a href="{{ route('organizations.admin', ['organization' => $organization->slug]) }}" class="text-sm font-bold text-forest underline underline-offset-2">
                    Staff sign in
                </a>
            </div>
        </div>
    </section>
@endsection
