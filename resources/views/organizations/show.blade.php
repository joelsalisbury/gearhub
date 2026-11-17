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
                <h2 class="text-lg font-bold text-ink">Equipment lending</h2>
                <p class="mt-1 text-sm leading-relaxed text-muted">Public gear listings are not available yet.</p>
            </div>
            <div>
                <x-button href="{{ route('organizations.admin', ['organization' => $organization->slug]) }}" variant="secondary">Staff sign in</x-button>
            </div>
        </div>
    </section>
@endsection
