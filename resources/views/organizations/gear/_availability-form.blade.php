@php
    $values = old() ?: $periodInputs;
@endphp

<form method="GET" action="{{ $action }}" class="grid gap-4 rounded-xl border border-border bg-sage p-4 sm:p-5">
    @foreach ($preserved as $name => $value)
        @if ($value !== '')
            <input type="hidden" name="{{ $name }}" value="{{ $value }}">
        @endif
    @endforeach

    <div class="grid gap-1">
        <h2 class="text-lg font-bold text-ink">Check dates</h2>
        <p class="text-sm text-muted">
            Times use {{ $organization->timezone }}. This check is preliminary and does not reserve gear.
        </p>
    </div>

    <div class="grid gap-4 sm:grid-cols-2">
        <fieldset class="grid gap-2">
            <legend class="text-sm font-bold text-ink">Pickup</legend>
            <div class="grid grid-cols-2 gap-3">
                <div class="grid gap-1.5">
                    <label for="pickup_date" class="text-xs font-medium text-muted">Date</label>
                    <input
                        id="pickup_date"
                        name="pickup_date"
                        type="date"
                        required
                        value="{{ $values['pickup_date'] ?? '' }}"
                        class="min-h-11 w-full rounded-lg border border-border bg-white px-3 py-2 text-sm text-ink outline-none focus:border-leaf focus:ring-2 focus:ring-leaf/20"
                    >
                    @error('pickup_date')
                        <p class="text-sm text-red-800">{{ $message }}</p>
                    @enderror
                </div>
                <div class="grid gap-1.5">
                    <label for="pickup_time" class="text-xs font-medium text-muted">Time</label>
                    <input
                        id="pickup_time"
                        name="pickup_time"
                        type="time"
                        required
                        value="{{ $values['pickup_time'] ?? '' }}"
                        class="min-h-11 w-full rounded-lg border border-border bg-white px-3 py-2 text-sm text-ink outline-none focus:border-leaf focus:ring-2 focus:ring-leaf/20"
                    >
                    @error('pickup_time')
                        <p class="text-sm text-red-800">{{ $message }}</p>
                    @enderror
                </div>
            </div>
        </fieldset>

        <fieldset class="grid gap-2">
            <legend class="text-sm font-bold text-ink">Return</legend>
            <div class="grid grid-cols-2 gap-3">
                <div class="grid gap-1.5">
                    <label for="return_date" class="text-xs font-medium text-muted">Date</label>
                    <input
                        id="return_date"
                        name="return_date"
                        type="date"
                        required
                        value="{{ $values['return_date'] ?? '' }}"
                        class="min-h-11 w-full rounded-lg border border-border bg-white px-3 py-2 text-sm text-ink outline-none focus:border-leaf focus:ring-2 focus:ring-leaf/20"
                    >
                    @error('return_date')
                        <p class="text-sm text-red-800">{{ $message }}</p>
                    @enderror
                </div>
                <div class="grid gap-1.5">
                    <label for="return_time" class="text-xs font-medium text-muted">Time</label>
                    <input
                        id="return_time"
                        name="return_time"
                        type="time"
                        required
                        value="{{ $values['return_time'] ?? '' }}"
                        class="min-h-11 w-full rounded-lg border border-border bg-white px-3 py-2 text-sm text-ink outline-none focus:border-leaf focus:ring-2 focus:ring-leaf/20"
                    >
                    @error('return_time')
                        <p class="text-sm text-red-800">{{ $message }}</p>
                    @enderror
                </div>
            </div>
        </fieldset>
    </div>

    <div>
        <x-button type="submit">Check availability</x-button>
    </div>
</form>
