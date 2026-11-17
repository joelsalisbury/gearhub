@extends('layouts.organization')

@section('title', 'Edit gear · '.$organization->name)

@section('content')
    <section class="grid max-w-3xl gap-6">
        <div class="grid gap-2">
            <p class="font-mono text-xs font-medium uppercase tracking-wider text-leaf">{{ $organization->name }}</p>
            <h1 class="text-3xl font-bold tracking-tight text-ink">Edit gear</h1>
            <p class="text-sm text-muted">{{ $gearItem->name }}</p>
        </div>

        @if (session('status'))
            <p role="status" class="rounded-lg border border-border bg-sage px-4 py-3 text-sm text-forest">
                {{ session('status') }}
            </p>
        @endif
        @error('gear')
            <p role="alert" class="rounded-lg border border-red-300 bg-red-50 px-4 py-3 text-sm text-red-800">
                {{ $message }}
            </p>
        @enderror

        @include('organizations.admin.gear._form', [
            'action' => route('organizations.admin.gear.update', ['organization' => $organization->slug, 'gearItem' => $gearItem]),
            'method' => 'PUT',
            'submitLabel' => 'Save changes',
        ])

        <form
            method="POST"
            action="{{ route('organizations.admin.gear.destroy', ['organization' => $organization->slug, 'gearItem' => $gearItem]) }}"
            onsubmit="return confirm('Permanently delete this gear item?')"
        >
            @csrf
            @method('DELETE')
            <button type="submit" class="min-h-10 rounded-lg border border-red-300 bg-white px-4 py-2 text-sm font-bold text-red-800 hover:bg-red-50 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-leaf">
                Delete gear
            </button>
        </form>
    </section>
@endsection
