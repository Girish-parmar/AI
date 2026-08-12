@extends('layouts.dashboard')

@section('title', 'Browse Scripts')

@section('content')
    <h1 class="h3 fw-bold mb-4">Browse Scripts</h1>

    @include('partials.content-type-tabs', ['active' => 'scripts', 'prefix' => 'user'])

    @if ($scripts->isEmpty())
        <p class="text-body-secondary">No scripts are available yet &mdash; check back soon.</p>
    @else
        <div class="row g-4">
            @foreach ($scripts as $script)
                <div class="col-md-6 col-lg-4">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-body d-flex flex-column">
                            <span class="badge text-bg-light text-body-secondary align-self-start mb-2">{{ $script->category }}</span>
                            <h2 class="h5 fw-semibold">
                                <a href="{{ route('user.scripts.show', $script) }}" class="text-decoration-none">{{ $script->title }}</a>
                            </h2>
                            <p class="text-body-secondary small flex-grow-1">{{ \Illuminate\Support\Str::limit($script->description, 120) }}</p>
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="fw-bold">${{ number_format($script->price, 2) }}</span>
                                <span class="text-body-secondary small">by {{ $script->creator->name }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
@endsection
