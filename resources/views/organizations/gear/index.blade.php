@extends('layouts.public')

@section('title', 'Gear · '.$organization->name.' · GearHub')

@section('content')
    <section class="grid gap-7">
        <div class="grid gap-2">
            <a href="{{ route('organizations.show', ['organization' => $organization->slug]) }}" class="text-sm font-bold text-forest hover:text-ink">
                ← {{ $organization->name }}
            </a>
            <p class="font-mono text-xs font-medium uppercase tracking-wider text-leaf">Organization gear</p>
            <h1 class="text-3xl font-bold tracking-tight text-ink sm:text-4xl">Gear</h1>
        </div>

        <form method="GET" action="{{ route('organizations.gear.index', ['organization' => $organization->slug]) }}" class="grid gap-3 rounded-xl border border-border bg-white p-4 sm:grid-cols-[minmax(12rem,1fr)_minmax(10rem,0.7fr)_auto] sm:items-end">
            <div class="grid gap-1.5">
                <label for="q" class="text-sm font-bold text-ink">Search gear</label>
                <input
                    id="q"
                    name="q"
                    type="search"
                    value="{{ $filters['q'] }}"
                    placeholder="Name or description"
                    class="min-h-11 w-full rounded-lg border border-border bg-white px-3 py-2 text-sm text-ink outline-none focus:border-leaf focus:ring-2 focus:ring-leaf/20"
                >
            </div>
            <div class="grid gap-1.5">
                <label for="category" class="text-sm font-bold text-ink">Category</label>
                <select id="category" name="category" class="min-h-11 w-full rounded-lg border border-border bg-white px-3 py-2 text-sm text-ink outline-none focus:border-leaf focus:ring-2 focus:ring-leaf/20">
                    <option value="">All categories</option>
                    @foreach ($categories as $category)
                        <option value="{{ $category }}" @selected($filters['category'] === $category)>{{ $category }}</option>
                    @endforeach
                </select>
            </div>
            <div class="flex items-center gap-3">
                <x-button type="submit">Search</x-button>
                @if ($filters['q'] !== '' || $filters['category'] !== '')
                    <a href="{{ route('organizations.gear.index', ['organization' => $organization->slug]) }}" class="text-sm font-bold text-forest underline underline-offset-2">
                        Clear
                    </a>
                @endif
            </div>
        </form>

        @if ($gearItems->isEmpty())
            <div class="grid justify-items-start gap-3 rounded-xl border border-border bg-white p-6 sm:p-8">
                <h2 class="text-lg font-bold text-ink">
                    {{ $filters['q'] !== '' || $filters['category'] !== '' ? 'No matching gear' : 'No gear listed yet' }}
                </h2>
                <p class="max-w-xl text-sm leading-relaxed text-muted">
                    {{ $filters['q'] !== '' || $filters['category'] !== ''
                        ? 'Try changing or clearing the search and category filters.'
                        : $organization->name.' has not added any public gear to its catalog yet.' }}
                </p>
                @if ($filters['q'] !== '' || $filters['category'] !== '')
                    <a href="{{ route('organizations.gear.index', ['organization' => $organization->slug]) }}" class="text-sm font-bold text-forest underline underline-offset-2">
                        Clear filters
                    </a>
                @endif
            </div>
        @else
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($gearItems as $gearItem)
                    <a
                        href="{{ route('organizations.gear.show', ['organization' => $organization->slug, 'gearItem' => $gearItem]) }}"
                        class="group grid content-start overflow-hidden rounded-xl border border-border bg-white transition-colors hover:bg-sage focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-leaf"
                    >
                        @if ($gearItem->image_path)
                            <img
                                src="{{ Storage::disk('public')->url($gearItem->image_path) }}"
                                alt="Photo of {{ $gearItem->name }}"
                                class="h-48 w-full border-b border-border object-cover"
                                loading="lazy"
                            >
                        @else
                            <div class="grid h-48 place-items-center border-b border-border bg-sage text-sm text-muted" aria-hidden="true">
                                No photo
                            </div>
                        @endif
                        <div class="grid gap-2 p-4">
                            @if ($gearItem->category)
                                <p class="font-mono text-xs font-medium uppercase tracking-wider text-leaf">{{ $gearItem->category }}</p>
                            @endif
                            <h2 class="text-lg font-bold text-ink group-hover:text-forest">{{ $gearItem->name }}</h2>
                            @if ($gearItem->description)
                                <p class="text-sm leading-relaxed text-muted">{{ \Illuminate\Support\Str::limit($gearItem->description, 140) }}</p>
                            @endif
                            @if ($gearItem->quantity > 1)
                                <p class="text-xs text-muted">Quantity: {{ $gearItem->quantity }}</p>
                            @endif
                            <span class="pt-1 text-sm font-bold text-forest">View details <span aria-hidden="true">→</span></span>
                        </div>
                    </a>
                @endforeach
            </div>

            {{ $gearItems->links() }}
        @endif
    </section>
@endsection
