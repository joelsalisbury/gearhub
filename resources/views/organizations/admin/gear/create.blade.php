@extends('layouts.organization')

@section('title', 'Add gear · '.$organization->name)

@section('content')
    <section class="grid max-w-3xl gap-6">
        <div class="grid gap-2">
            <p class="font-mono text-xs font-medium uppercase tracking-wider text-leaf">{{ $organization->name }}</p>
            <h1 class="text-3xl font-bold tracking-tight text-ink">Add gear</h1>
            <p class="text-sm text-muted">Add a single item or a group of interchangeable items.</p>
        </div>

        @include('organizations.admin.gear._form', [
            'action' => route('organizations.admin.gear.store', ['organization' => $organization->slug]),
            'method' => 'POST',
            'submitLabel' => 'Add gear',
        ])
    </section>
@endsection
