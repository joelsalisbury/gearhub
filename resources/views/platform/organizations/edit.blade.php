@extends('layouts.platform')

@section('title', 'Edit '.$organization->name.' · GearHub')

@section('content')
    <section class="grid gap-6">
        <div class="grid gap-2">
            <a href="{{ route('platform.organizations.show', $organization) }}" class="text-sm font-bold text-forest hover:text-ink">← {{ $organization->name }}</a>
            <h1 class="text-3xl font-bold tracking-tight text-ink">Edit organization</h1>
        </div>
        <form method="POST" action="{{ route('platform.organizations.update', $organization) }}">
            @include('platform.organizations._form')
        </form>
    </section>
@endsection
