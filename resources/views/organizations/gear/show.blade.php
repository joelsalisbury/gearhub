@extends('layouts.public')

@section('title', $gearItem->name.' · '.$organization->name.' · GearHub')

@section('content')
    <section class="mx-auto grid max-w-5xl gap-6">
        <div>
            <a href="{{ route('organizations.gear.index', ['organization' => $organization->slug]) }}" class="text-sm font-bold text-forest hover:text-ink">
                ← Back to {{ $organization->name }} gear
            </a>
        </div>

        <div class="grid gap-7 md:grid-cols-2 md:items-start md:gap-10">
            <div class="overflow-hidden rounded-xl border border-border bg-white">
                @if ($gearItem->image_path)
                    <img
                        src="{{ Storage::disk('public')->url($gearItem->image_path) }}"
                        alt="Photo of {{ $gearItem->name }}"
                        class="h-64 w-full object-cover sm:h-80"
                    >
                @else
                    <div class="grid h-64 place-items-center bg-sage text-sm text-muted sm:h-80" aria-hidden="true">
                        No photo
                    </div>
                @endif
            </div>

            <div class="grid content-start gap-5">
                <div class="grid gap-2">
                    @if ($gearItem->category)
                        <p class="font-mono text-xs font-medium uppercase tracking-wider text-leaf">{{ $gearItem->category }}</p>
                    @endif
                    <h1 class="text-3xl font-bold tracking-tight text-ink sm:text-4xl">{{ $gearItem->name }}</h1>
                </div>

                @if ($gearItem->description)
                    <p class="text-base leading-relaxed text-muted">{{ $gearItem->description }}</p>
                @endif

                <dl class="grid gap-4 border-t border-border pt-5">
                    @if ($gearItem->category)
                        <div class="grid gap-1">
                            <dt class="text-xs font-bold uppercase tracking-wide text-muted">Category</dt>
                            <dd class="text-sm text-ink">{{ $gearItem->category }}</dd>
                        </div>
                    @endif
                    @if ($gearItem->quantity > 1)
                        <div class="grid gap-1">
                            <dt class="text-xs font-bold uppercase tracking-wide text-muted">Quantity</dt>
                            <dd class="text-sm text-ink">{{ $gearItem->quantity }}</dd>
                        </div>
                    @endif
                    <div class="grid gap-1">
                        <dt class="text-xs font-bold uppercase tracking-wide text-muted">Organization</dt>
                        <dd>
                            <a href="{{ route('organizations.show', ['organization' => $organization->slug]) }}" class="text-sm font-bold text-forest underline underline-offset-2">
                                {{ $organization->name }}
                            </a>
                        </dd>
                    </div>
                </dl>
            </div>
        </div>
    </section>
@endsection
