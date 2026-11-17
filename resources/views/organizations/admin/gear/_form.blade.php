<form method="POST" action="{{ $action }}" enctype="multipart/form-data" class="grid gap-5">
    @csrf
    @if ($method !== 'POST')
        @method($method)
    @endif

    <div class="grid gap-5 rounded-xl border border-border bg-white p-5 sm:p-6">
        <x-form-field name="name" label="Name" :value="$gearItem->name" required />

        <div class="grid gap-1.5">
            <label for="description" class="text-sm font-bold text-ink">Description</label>
            <textarea
                id="description"
                name="description"
                rows="3"
                class="w-full rounded-lg border border-border bg-white px-3 py-2 text-sm text-ink outline-none focus:border-leaf focus:ring-2 focus:ring-leaf/20"
            >{{ old('description', $gearItem->description) }}</textarea>
            @error('description')
                <p class="text-sm text-red-800">{{ $message }}</p>
            @enderror
        </div>

        <div class="grid gap-5 sm:grid-cols-2">
            <x-form-field name="category" label="Category" :value="$gearItem->category" />
            <x-form-field name="identifier" label="Identifier" :value="$gearItem->identifier" />
        </div>

        <div class="grid gap-5 sm:grid-cols-2">
            <x-form-field name="quantity" label="Quantity" type="number" :value="$gearItem->quantity ?? 1" min="1" required />

            <div class="grid gap-1.5">
                <label for="status" class="text-sm font-bold text-ink">Status</label>
                <select
                    id="status"
                    name="status"
                    required
                    class="min-h-11 w-full rounded-lg border border-border bg-white px-3 py-2 text-sm text-ink outline-none focus:border-leaf focus:ring-2 focus:ring-leaf/20"
                >
                    <option value="active" @selected(old('status', $gearItem->status ?? 'active') === 'active')>Active</option>
                    <option value="unavailable" @selected(old('status', $gearItem->status ?? 'active') === 'unavailable')>Unavailable</option>
                </select>
                @error('status')
                    <p class="text-sm text-red-800">{{ $message }}</p>
                @enderror
            </div>
        </div>

        <div class="grid gap-1.5">
            <label for="photo" class="text-sm font-bold text-ink">Photo <span class="font-normal text-muted">(optional, up to 5 MB)</span></label>
            <input
                id="photo"
                name="photo"
                type="file"
                accept="image/*"
                class="min-h-11 w-full rounded-lg border border-border bg-white px-3 py-2 text-sm text-ink file:mr-3 file:rounded-md file:border-0 file:bg-sage file:px-3 file:py-1 file:text-sm file:font-bold file:text-forest"
                @if ($errors->has('photo')) aria-invalid="true" aria-describedby="photo-error" @endif
            >
            @error('photo')
                <p id="photo-error" class="text-sm text-red-800">{{ $message }}</p>
            @enderror
            @if ($gearItem->image_path)
                <img
                    src="{{ Storage::disk('public')->url($gearItem->image_path) }}"
                    alt="Current photo of {{ $gearItem->name }}"
                    class="mt-2 size-24 rounded-lg border border-border object-cover"
                >
                <p class="text-xs text-muted">Choose another image to replace the current photo.</p>
            @endif
        </div>

        <div class="grid gap-1.5">
            <label for="notes" class="text-sm font-bold text-ink">Internal notes</label>
            <textarea
                id="notes"
                name="notes"
                rows="3"
                class="w-full rounded-lg border border-border bg-white px-3 py-2 text-sm text-ink outline-none focus:border-leaf focus:ring-2 focus:ring-leaf/20"
            >{{ old('notes', $gearItem->notes) }}</textarea>
            @error('notes')
                <p class="text-sm text-red-800">{{ $message }}</p>
            @enderror
        </div>
    </div>

    <div class="flex flex-wrap items-center gap-3">
        <x-button type="submit">{{ $submitLabel }}</x-button>
        <a
            href="{{ route('organizations.admin.gear.index', ['organization' => $organization->slug]) }}"
            class="text-sm font-bold text-forest underline underline-offset-2"
        >
            Cancel
        </a>
    </div>
</form>
