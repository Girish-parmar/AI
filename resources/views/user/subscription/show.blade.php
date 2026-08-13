@extends('layouts.dashboard')

@section('title', 'My Subscription')

@section('content')
    <h1 class="h3 fw-bold mb-4">My Subscription</h1>

    @if (! $subscription)
        <p class="text-body-secondary mb-3">You don't have a subscription yet.</p>
        <a href="{{ route('user.plans.index') }}" class="btn btn-primary">Browse plans</a>
    @else
        <div class="card border-0 shadow-sm" style="max-width: 30rem;">
            <div class="card-body">
                <h2 class="h5 fw-semibold">{{ $subscription->plan->name }}</h2>
                <p class="text-body-secondary">
                    ${{ number_format($subscription->plan->price, 2) }} / {{ $subscription->plan->billing_interval->label() }}
                </p>
                <p>
                    Status:
                    <span class="badge {{ $subscription->status->badgeClass() }}">{{ $subscription->status->label() }}</span>
                </p>

                @if (in_array($subscription->status->value, ['pending', 'active']))
                    <form method="POST" action="{{ route('user.subscription.cancel') }}" onsubmit="return confirm('Cancel your subscription?');">
                        @csrf
                        <button type="submit" class="btn btn-outline-danger">Cancel subscription</button>
                    </form>
                @else
                    <a href="{{ route('user.plans.index') }}" class="btn btn-primary">Browse plans</a>
                @endif
            </div>
        </div>
    @endif
@endsection
