@extends('layouts.dashboard')

@section('title', 'Subscription Plans')

@section('content')
    <a href="{{ route('user.subscription.show') }}" class="d-inline-block mb-3">&larr; Back to my subscription</a>

    <h1 class="h3 fw-bold mb-4">Subscription Plans</h1>

    @if ($plans->isEmpty())
        <p class="text-body-secondary">No plans are available right now.</p>
    @else
        <div class="row g-4">
            @foreach ($plans as $plan)
                <div class="col-md-6 col-lg-4">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-body d-flex flex-column">
                            <h2 class="h5 fw-semibold">{{ $plan->name }}</h2>
                            <p class="fs-4 fw-bold">
                                ${{ number_format($plan->price, 2) }}
                                <span class="fs-6 fw-normal text-body-secondary">/ {{ $plan->billing_interval->label() }}</span>
                            </p>
                            <p class="text-body-secondary flex-grow-1">{{ $plan->description }}</p>
                            <form method="POST" action="{{ route('user.plans.subscribe', $plan) }}">
                                @csrf
                                <button type="submit" class="btn btn-primary w-100">Subscribe</button>
                            </form>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
@endsection
