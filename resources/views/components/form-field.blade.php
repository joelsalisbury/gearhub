@props([
    'name',
    'label',
    'type' => 'text',
    'autocomplete' => null,
])

<div class="grid gap-1.5">
    <label for="{{ $name }}" class="text-sm font-bold text-ink">{{ $label }}</label>
    <input
        id="{{ $name }}"
        name="{{ $name }}"
        type="{{ $type }}"
        @if ($type !== 'password') value="{{ old($name) }}" @endif
        @if ($autocomplete) autocomplete="{{ $autocomplete }}" @endif
        @if ($errors->has($name)) aria-invalid="true" aria-describedby="{{ $name }}-error" @endif
        {{ $attributes->merge(['class' => 'min-h-11 w-full rounded-lg border border-border bg-white px-3 py-2 text-sm text-ink outline-none focus:border-leaf focus:ring-2 focus:ring-leaf/20']) }}
    >
    @error($name)
        <p id="{{ $name }}-error" class="text-sm text-red-800">{{ $message }}</p>
    @enderror
</div>
