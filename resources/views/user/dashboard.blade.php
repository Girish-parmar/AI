@extends('layouts.dashboard')

@section('title', 'Dashboard')

@section('content')
    <h1 class="h3 fw-bold mb-1">Welcome, {{ auth()->user()->name }}</h1>
    <p class="text-body-secondary mb-4">
        Browse courses, manage your subscriptions and purchases, and try out demo content.
    </p>

    @if ($advertisements->isNotEmpty())
        <div class="row row-cols-1 row-cols-md-3 g-3 mb-4">
            @foreach ($advertisements as $advertisement)
                <div class="col">
                    <a href="{{ $advertisement->target_url }}" target="_blank" rel="noopener sponsored" class="card border-0 shadow-sm text-decoration-none h-100">
                        @if ($advertisement->banner_path)
                            <img src="{{ $advertisement->banner_path }}" alt="{{ $advertisement->title }}" class="card-img-top" style="object-fit: cover; max-height: 8rem;">
                        @endif
                        <div class="card-body">
                            <span class="badge text-bg-light text-body-secondary mb-2">Sponsored</span>
                            <p class="card-text fw-semibold text-body mb-0">{{ $advertisement->title }}</p>
                        </div>
                    </a>
                </div>
            @endforeach
        </div>
    @endif

    @include('partials.dashboard-modules-card')
@endsection
