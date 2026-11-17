@extends('layouts.public')

@section('title', 'Staff sign in · GearHub')

@section('content')
    <section class="mx-auto grid w-full max-w-md gap-7">
        <div class="grid gap-2">
            <p class="font-mono text-xs font-medium uppercase tracking-wider text-leaf">Organization staff</p>
            <h1 class="text-3xl font-bold tracking-tight text-ink">Sign in to GearHub</h1>
            <p class="text-sm leading-relaxed text-muted">Use the email and password associated with your staff account.</p>
        </div>

        <form method="POST" action="{{ route('login.store') }}" class="grid gap-5 rounded-xl border border-border bg-white p-5 sm:p-6">
            @csrf

            <x-form-field name="email" label="Email address" type="email" autocomplete="username" required autofocus />
            <x-form-field name="password" label="Password" type="password" autocomplete="current-password" required />

            <label class="flex items-center gap-2 text-sm text-muted">
                <input type="checkbox" name="remember" value="1" class="size-4 accent-forest">
                Remember me
            </label>

            <x-button type="submit">Sign in</x-button>
        </form>
    </section>
@endsection
