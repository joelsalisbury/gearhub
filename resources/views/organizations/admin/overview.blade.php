@extends('layouts.organization')

@section('title', 'Overview · '.$organization->name)

@section('content')
    <section class="grid max-w-3xl gap-6">
        <div class="grid gap-2">
            <p class="font-mono text-xs font-medium uppercase tracking-wider text-leaf">{{ $organization->name }}</p>
            <h1 class="text-3xl font-bold tracking-tight text-ink">Overview</h1>
        </div>

        <div class="rounded-xl border border-border bg-white p-5 sm:p-6">
            <h2 class="text-lg font-bold text-ink">Your organization workspace</h2>
            <p class="mt-2 max-w-2xl text-sm leading-relaxed text-muted">
                Manage the equipment {{ $organization->name }} lends to its community.
            </p>
            <div class="mt-5">
                <x-button href="{{ route('organizations.admin.gear.index', ['organization' => $organization->slug]) }}">Manage gear</x-button>
            </div>
        </div>
    </section>
@endsection
