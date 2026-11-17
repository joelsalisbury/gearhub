@extends('layouts.organization')

@section('title', 'Settings · '.$organization->name)

@section('content')
    <section class="grid max-w-4xl gap-10">
        <div class="grid gap-2">
            <p class="font-mono text-xs font-medium uppercase tracking-wider text-leaf">{{ $organization->name }}</p>
            <h1 class="text-3xl font-bold tracking-tight text-ink">Settings</h1>
        </div>

        @if ($errors->any())
            <div class="grid gap-1 rounded-lg border border-red-200 bg-white px-4 py-3 text-sm text-red-800" role="alert">
                @foreach ($errors->all() as $error)
                    <p>{{ $error }}</p>
                @endforeach
            </div>
        @endif

        <form method="POST" action="{{ route('organizations.admin.settings.update', ['organization' => $organization->slug]) }}" class="grid gap-5 rounded-xl border border-border bg-white p-5 sm:p-6">
            @csrf
            @method('PATCH')
            <div class="grid gap-2">
                <label for="name" class="text-sm font-bold text-ink">Organization name</label>
                <input id="name" name="name" value="{{ old('name', $organization->name) }}" required maxlength="255" class="min-h-11 rounded-lg border border-border bg-white px-3 py-2 text-sm outline-none focus:border-leaf focus:ring-2 focus:ring-leaf/20">
                @error('name') <p class="text-sm text-red-800">{{ $message }}</p> @enderror
            </div>
            <div class="grid gap-2">
                <label for="description" class="text-sm font-bold text-ink">Description <span class="font-normal text-muted">(optional)</span></label>
                <textarea id="description" name="description" rows="3" maxlength="2000" class="rounded-lg border border-border bg-white px-3 py-2 text-sm outline-none focus:border-leaf focus:ring-2 focus:ring-leaf/20">{{ old('description', $organization->description) }}</textarea>
                @error('description') <p class="text-sm text-red-800">{{ $message }}</p> @enderror
            </div>
            <div class="grid gap-2">
                <label for="timezone" class="text-sm font-bold text-ink">Timezone</label>
                <input id="timezone" name="timezone" value="{{ old('timezone', $organization->timezone) }}" required class="min-h-11 rounded-lg border border-border bg-white px-3 py-2 text-sm outline-none focus:border-leaf focus:ring-2 focus:ring-leaf/20">
                @error('timezone') <p class="text-sm text-red-800">{{ $message }}</p> @enderror
            </div>
            <div class="grid gap-2 border-t border-border pt-5 sm:grid-cols-2">
                <div>
                    <p class="text-sm font-bold text-ink">Organization address</p>
                    <p class="mt-1 font-mono text-sm text-muted">/{{ $organization->slug }}</p>
                    <p class="mt-1 text-xs text-muted">Only platform administrators can change this.</p>
                </div>
                <div>
                    <p class="text-sm font-bold text-ink">Parent organization</p>
                    <p class="mt-1 text-sm text-muted">{{ $organization->parent?->name ?? 'None' }}</p>
                    <p class="mt-1 text-xs text-muted">Only platform administrators can change this.</p>
                </div>
            </div>
            <div><x-button type="submit">Save settings</x-button></div>
        </form>

        <section class="grid gap-5">
            <div>
                <h2 class="text-xl font-bold text-ink">Staff</h2>
                <p class="mt-1 text-sm text-muted">Add existing GearHub users and manage their role in this organization.</p>
            </div>

            <form method="POST" action="{{ route('organizations.admin.settings.staff.store', ['organization' => $organization->slug]) }}" class="grid gap-3 rounded-xl border border-border bg-white p-5 sm:grid-cols-[1fr_12rem_auto] sm:items-end">
                @csrf
                <div class="grid gap-2">
                    <label for="staff-email" class="text-sm font-bold text-ink">User email</label>
                    <input id="staff-email" name="email" type="email" value="{{ old('email') }}" required autocomplete="email" class="min-h-11 rounded-lg border border-border bg-white px-3 py-2 text-sm outline-none focus:border-leaf focus:ring-2 focus:ring-leaf/20">
                    @error('email') <p class="text-sm text-red-800">{{ $message }}</p> @enderror
                </div>
                <div class="grid gap-2">
                    <label for="staff-role" class="text-sm font-bold text-ink">Role</label>
                    <select id="staff-role" name="role" required class="min-h-11 rounded-lg border border-border bg-white px-3 py-2 text-sm outline-none focus:border-leaf focus:ring-2 focus:ring-leaf/20">
                        <option value="manager" @selected(old('role', 'manager') === 'manager')>Manager</option>
                        <option value="admin" @selected(old('role') === 'admin')>Admin</option>
                    </select>
                </div>
                <x-button type="submit">Add staff</x-button>
            </form>

            <div class="overflow-x-auto rounded-xl border border-border bg-white">
                <table class="w-full min-w-[40rem] text-left text-sm">
                    <thead class="border-b border-border bg-sage/60 text-xs uppercase tracking-wide text-muted">
                        <tr><th class="px-4 py-3">Staff member</th><th class="px-4 py-3">Role</th><th class="px-4 py-3"><span class="sr-only">Actions</span></th></tr>
                    </thead>
                    <tbody class="divide-y divide-border">
                        @forelse ($organization->users->sortBy('name') as $staffMember)
                            <tr>
                                <td class="px-4 py-4">
                                    <p class="font-bold text-ink">{{ $staffMember->name }}</p>
                                    <p class="mt-1 text-xs text-muted">{{ $staffMember->email }}</p>
                                </td>
                                <td class="px-4 py-4 text-muted">{{ ucfirst($staffMember->pivot->role) }}</td>
                                <td class="px-4 py-4">
                                    @if ($staffMember->pivot->role !== 'admin' || $adminCount > 1)
                                        <div class="flex flex-wrap items-center justify-end gap-3">
                                            <form method="POST" action="{{ route('organizations.admin.settings.staff.update', ['organization' => $organization->slug, 'user' => $staffMember->id]) }}" class="flex items-center gap-2">
                                                @csrf
                                                @method('PATCH')
                                                <label for="role-{{ $staffMember->id }}" class="sr-only">Role for {{ $staffMember->name }}</label>
                                                <select id="role-{{ $staffMember->id }}" name="role" class="min-h-9 rounded-md border border-border bg-white px-2 text-sm">
                                                    <option value="admin" @selected($staffMember->pivot->role === 'admin')>Admin</option>
                                                    <option value="manager" @selected($staffMember->pivot->role === 'manager')>Manager</option>
                                                </select>
                                                <button type="submit" class="font-bold text-forest hover:text-ink">Save</button>
                                            </form>
                                            <form method="POST" action="{{ route('organizations.admin.settings.staff.destroy', ['organization' => $organization->slug, 'user' => $staffMember->id]) }}">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="text-sm font-bold text-forest hover:text-ink">Remove</button>
                                            </form>
                                        </div>
                                    @else
                                        <span class="text-sm text-muted">Only organization admin</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="3" class="px-4 py-5 text-muted">No staff members yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </section>
@endsection
