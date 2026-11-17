@csrf
@if ($organization->exists)
    @method('PUT')
@endif

<div class="grid max-w-2xl gap-5">
    <div class="grid gap-2">
        <label for="name" class="text-sm font-bold text-ink">Organization name</label>
        <input id="name" name="name" value="{{ old('name', $organization->name) }}" required maxlength="255" class="min-h-11 rounded-lg border border-border bg-white px-3 py-2 text-sm outline-none focus:border-leaf focus:ring-2 focus:ring-leaf/20">
        @error('name') <p class="text-sm text-red-800">{{ $message }}</p> @enderror
    </div>

    <div class="grid gap-2">
        <label for="slug" class="text-sm font-bold text-ink">Web address</label>
        <div class="flex items-center rounded-lg border border-border bg-white px-3 focus-within:border-leaf focus-within:ring-2 focus-within:ring-leaf/20">
            <span class="font-mono text-sm text-muted">/</span>
            <input id="slug" name="slug" value="{{ old('slug', $organization->slug) }}" required maxlength="255" pattern="[a-z0-9]+(?:-[a-z0-9]+)*" class="min-h-11 w-full border-0 bg-transparent px-2 py-2 text-sm outline-none">
        </div>
        @error('slug') <p class="text-sm text-red-800">{{ $message }}</p> @enderror
        @if ($organization->exists)
            <p class="text-xs leading-relaxed text-muted">Changing this address may break existing links to the organization.</p>
        @endif
    </div>

    <div class="grid gap-2">
        <label for="description" class="text-sm font-bold text-ink">Description <span class="font-normal text-muted">(optional)</span></label>
        <textarea id="description" name="description" rows="3" maxlength="2000" class="rounded-lg border border-border bg-white px-3 py-2 text-sm outline-none focus:border-leaf focus:ring-2 focus:ring-leaf/20">{{ old('description', $organization->description) }}</textarea>
        @error('description') <p class="text-sm text-red-800">{{ $message }}</p> @enderror
    </div>

    <div class="grid gap-2">
        <label for="timezone" class="text-sm font-bold text-ink">Timezone</label>
        <input id="timezone" name="timezone" value="{{ old('timezone', $organization->timezone ?: 'UTC') }}" required maxlength="255" placeholder="America/New_York" class="min-h-11 rounded-lg border border-border bg-white px-3 py-2 text-sm outline-none focus:border-leaf focus:ring-2 focus:ring-leaf/20">
        @error('timezone') <p class="text-sm text-red-800">{{ $message }}</p> @enderror
    </div>

    <div class="grid gap-2">
        <label for="parent_id" class="text-sm font-bold text-ink">Parent organization</label>
        <select id="parent_id" name="parent_id" class="min-h-11 rounded-lg border border-border bg-white px-3 py-2 text-sm outline-none focus:border-leaf focus:ring-2 focus:ring-leaf/20">
            <option value="">None — independent organization</option>
            @foreach ($parents as $parent)
                <option value="{{ $parent->id }}" @selected((string) old('parent_id', $organization->parent_id) === (string) $parent->id)>
                    {{ $parent->name }}
                </option>
            @endforeach
        </select>
        @error('parent_id') <p class="text-sm text-red-800">{{ $message }}</p> @enderror
        <p class="text-xs leading-relaxed text-muted">Parent relationships do not grant staff access between organizations.</p>
    </div>

    <div class="flex flex-wrap gap-3 pt-2">
        <x-button type="submit">{{ $organization->exists ? 'Save organization' : 'Create organization' }}</x-button>
        @if ($organization->exists)
            <x-button href="{{ route('platform.organizations.show', $organization) }}" variant="secondary">Cancel</x-button>
        @else
            <x-button href="{{ route('platform.organizations.index') }}" variant="secondary">Cancel</x-button>
        @endif
    </div>
</div>
