@extends('layouts.dashboard')

@section('title', $script->title)

@section('content')
    <a href="{{ route('user.scripts.index') }}" class="d-inline-block mb-3">&larr; Back to scripts</a>

    <div class="card border-0 shadow-sm" style="max-width: 40rem;">
        <div class="card-body">
            <span class="badge text-bg-light text-body-secondary mb-2">{{ $script->category }}</span>
            <h1 class="h3 fw-bold">{{ $script->title }}</h1>
            <p class="text-body-secondary mb-3">by {{ $script->creator->name }}</p>
            <p class="fs-4 fw-bold mb-3">${{ number_format($script->price, 2) }}</p>
            <p style="white-space: pre-line;">{{ $script->description }}</p>
        </div>
    </div>
@endsection
