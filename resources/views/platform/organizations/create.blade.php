@extends('layouts.platform')

@section('title', 'Create organization · GearHub')

@section('content')
    <section class="grid gap-6">
        <div class="grid gap-2">
            <a href="{{ route('platform.organizations.index') }}" class="text-sm font-bold text-forest hover:text-ink">← Organizations</a>
            <h1 class="text-3xl font-bold tracking-tight text-ink">Create organization</h1>
        </div>
        <form method="POST" action="{{ route('platform.organizations.store') }}">
            @include('platform.organizations._form')
        </form>
    </section>
@endsection
