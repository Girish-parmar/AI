@extends('layouts.dashboard')

@section('title', 'Subscription Plans')

@section('content')
    <a href="{{ route('user.subscription.show') }}" class="d-inline-block mb-3">&larr; Back to my subscription</a>

    <h1 class="h3 fw-bold mb-4">Subscription Plans</h1>

    @if ($activeSubscription && $activeSubscription->onTrial())
        <p class="text-body-secondary mb-4">You're on a free trial until {{ $activeSubscription->trial_ends_at->format('Y-m-d') }}. You can switch plans once it ends.</p>
    @endif

    @if ($plans->isEmpty())
        <p class="text-body-secondary">No plans are available right now.</p>
    @else
        <div class="row g-4">
            @foreach ($plans as $plan)
                @php $isCurrentPlan = $activeSubscription && $activeSubscription->subscription_plan_id === $plan->id; @endphp
                <div class="col-md-6 col-lg-4">
                    <div class="card h-100 border-0 shadow-sm {{ $isCurrentPlan ? 'border border-primary' : '' }}">
                        <div class="card-body d-flex flex-column">
                            <h2 class="h5 fw-semibold">{{ $plan->name }}</h2>
                            <p class="fs-4 fw-bold">
                                ${{ number_format($plan->price, 2) }}
                                <span class="fs-6 fw-normal text-body-secondary">/ {{ $plan->billing_interval->label() }}</span>
                            </p>
                            @if ($plan->hasTrial())
                                <span class="badge text-bg-success align-self-start mb-2">{{ $plan->trial_days }}-day free trial</span>
                            @endif
                            <p class="text-body-secondary flex-grow-1">{{ $plan->description }}</p>

                            @if ($isCurrentPlan)
                                <button type="button" class="btn btn-outline-secondary w-100" disabled>Current plan</button>
                            @elseif ($activeSubscription && ! $activeSubscription->onTrial())
                                <a href="{{ route('user.subscription.switch-preview', $plan) }}" class="btn btn-primary w-100">Switch to this plan</a>
                            @elseif ($activeSubscription)
                                <button type="button" class="btn btn-outline-secondary w-100" disabled>Available after your trial</button>
                            @else
                                <form method="POST" action="{{ route('user.plans.subscribe', $plan) }}">
                                    @csrf
                                    <button type="submit" class="btn btn-primary w-100">{{ $plan->hasTrial() ? 'Start free trial' : 'Subscribe' }}</button>
                                </form>
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
@endsection
