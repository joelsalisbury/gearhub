@extends('layouts.organization')

@section('title', 'Gear · '.$organization->name)

@section('content')
    <section class="grid gap-6">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div class="grid gap-2">
                <p class="font-mono text-xs font-medium uppercase tracking-wider text-leaf">{{ $organization->name }}</p>
                <h1 class="text-3xl font-bold tracking-tight text-ink">Gear</h1>
            </div>
            <div class="flex flex-wrap gap-3">
                <x-button href="{{ route('organizations.gear.index', ['organization' => $organization->slug]) }}" variant="secondary">
                    View public catalog
                </x-button>
                <x-button class="self-start" href="{{ route('organizations.admin.gear.create', ['organization' => $organization->slug]) }}">
                    Add gear
                </x-button>
            </div>
        </div>

        @if (session('status'))
            <p role="status" class="rounded-lg border border-border bg-sage px-4 py-3 text-sm text-forest">
                {{ session('status') }}
            </p>
        @endif

        <form method="GET" action="{{ route('organizations.admin.gear.index', ['organization' => $organization->slug]) }}" class="grid gap-3 rounded-xl border border-border bg-white p-4 sm:grid-cols-2 sm:items-end lg:grid-cols-[minmax(12rem,1fr)_minmax(10rem,0.7fr)_minmax(9rem,0.6fr)_auto]">
            <x-form-field name="q" label="Search gear" :value="$filters['q']" placeholder="Name or identifier" />

            <div class="grid gap-1.5">
                <label for="category" class="text-sm font-bold text-ink">Category</label>
                <select id="category" name="category" class="min-h-11 w-full rounded-lg border border-border bg-white px-3 py-2 text-sm text-ink outline-none focus:border-leaf focus:ring-2 focus:ring-leaf/20">
                    <option value="">All categories</option>
                    @foreach ($categories as $category)
                        <option value="{{ $category }}" @selected($filters['category'] === $category)>{{ $category }}</option>
                    @endforeach
                </select>
            </div>

            <div class="grid gap-1.5">
                <label for="status" class="text-sm font-bold text-ink">Status</label>
                <select id="status" name="status" class="min-h-11 w-full rounded-lg border border-border bg-white px-3 py-2 text-sm text-ink outline-none focus:border-leaf focus:ring-2 focus:ring-leaf/20">
                    <option value="">All statuses</option>
                    <option value="active" @selected($filters['status'] === 'active')>Active</option>
                    <option value="unavailable" @selected($filters['status'] === 'unavailable')>Unavailable</option>
                </select>
            </div>

            <div class="flex items-center gap-2">
                <x-button type="submit" variant="secondary">Search</x-button>
                @if ($filters['q'] !== '' || $filters['category'] !== '' || $filters['status'] !== '')
                    <a href="{{ route('organizations.admin.gear.index', ['organization' => $organization->slug]) }}" class="text-sm font-bold text-forest underline underline-offset-2">Clear</a>
                @endif
            </div>
        </form>

        @if ($gearItems->isEmpty())
            <div class="grid justify-items-start gap-3 rounded-xl border border-border bg-white p-6 sm:p-8">
                <h2 class="text-lg font-bold text-ink">
                    {{ $filters['q'] !== '' || $filters['category'] !== '' || $filters['status'] !== '' ? 'No matching gear' : 'No gear yet' }}
                </h2>
                <p class="max-w-xl text-sm leading-relaxed text-muted">
                    {{ $filters['q'] !== '' || $filters['category'] !== '' || $filters['status'] !== '' ? 'Try changing or clearing the filters.' : 'Add the equipment your organization lends to its community.' }}
                </p>
                @if ($filters['q'] === '' && $filters['category'] === '' && $filters['status'] === '')
                    <x-button href="{{ route('organizations.admin.gear.create', ['organization' => $organization->slug]) }}">Add gear</x-button>
                @endif
            </div>
        @else
            <div class="overflow-hidden rounded-xl border border-border bg-white">
                <ul class="divide-y divide-border">
                    @foreach ($gearItems as $gearItem)
                        <li class="flex flex-col gap-4 p-4 sm:flex-row sm:items-center sm:justify-between sm:px-5">
                            <div class="flex min-w-0 items-center gap-4">
                                @if ($gearItem->image_path)
                                    <img
                                        src="{{ Storage::disk('public')->url($gearItem->image_path) }}"
                                        alt="Photo of {{ $gearItem->name }}"
                                        class="size-16 shrink-0 rounded-lg border border-border object-cover"
                                    >
                                @else
                                    <div class="grid size-16 shrink-0 place-items-center rounded-lg border border-border bg-sage text-center text-xs text-muted">
                                        No photo
                                    </div>
                                @endif

                                <div class="grid min-w-0 gap-1">
                                    <h2 class="truncate font-bold text-ink">{{ $gearItem->name }}</h2>
                                    <p class="text-sm text-muted">
                                        @if ($gearItem->category)
                                            {{ $gearItem->category }}
                                        @endif
                                        @if ($gearItem->identifier)
                                            @if ($gearItem->category) <span aria-hidden="true">·</span> @endif
                                            ID: {{ $gearItem->identifier }}
                                        @endif
                                    </p>
                                </div>
                            </div>

                            <div class="flex flex-wrap items-center gap-4 pl-20 sm:pl-0">
                                <span class="text-sm text-muted">Qty {{ $gearItem->quantity }}</span>
                                <span class="rounded-md border border-border px-2.5 py-1 text-xs font-bold {{ $gearItem->status === 'active' ? 'bg-sage text-forest' : 'bg-cream text-muted' }}">
                                    {{ ucfirst($gearItem->status) }}
                                </span>
                                <a
                                    href="{{ route('organizations.admin.gear.edit', ['organization' => $organization->slug, 'gearItem' => $gearItem]) }}"
                                    class="text-sm font-bold text-forest underline underline-offset-2"
                                >
                                    Edit
                                </a>
                            </div>
                        </li>
                    @endforeach
                </ul>
            </div>

            {{ $gearItems->links() }}
        @endif
    </section>
@endsection
