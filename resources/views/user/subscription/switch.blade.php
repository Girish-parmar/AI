@extends('layouts.dashboard')

@section('title', 'Switch Plan')

@section('content')
    <a href="{{ route('user.plans.index') }}" class="d-inline-block mb-3">&larr; Back to plans</a>

    <h1 class="h3 fw-bold mb-4">Switch to &ldquo;{{ $newPlan->name }}&rdquo;</h1>

    <div class="card border-0 shadow-sm" style="max-width: 30rem;">
        <div class="card-body">
            <dl class="row mb-3">
                <dt class="col-7 fw-normal text-body-secondary">Current plan</dt>
                <dd class="col-5 text-end">{{ $subscription->plan->name }}</dd>

                <dt class="col-7 fw-normal text-body-secondary">New plan</dt>
                <dd class="col-5 text-end">{{ $newPlan->name }}</dd>

                <dt class="col-7 fw-normal text-body-secondary">Days remaining in period</dt>
                <dd class="col-5 text-end">{{ $proration['remaining_days'] }}</dd>

                <dt class="col-7 fw-normal text-body-secondary">Credit for unused time</dt>
                <dd class="col-5 text-end">&minus;${{ number_format($proration['credit'], 2) }}</dd>

                <dt class="col-7 fw-normal text-body-secondary">New plan, prorated</dt>
                <dd class="col-5 text-end">${{ number_format($proration['charge'], 2) }}</dd>

                <dt class="col-7 fw-semibold border-top pt-2 mt-1">Due now</dt>
                <dd class="col-5 text-end fw-semibold border-top pt-2 mt-1">${{ number_format($proration['amount_due'], 2) }}</dd>
            </dl>

            @if ($proration['amount_due'] > 0)
                <p class="text-body-secondary small">This charge will be pending until confirmed, same as a new subscription.</p>
            @else
                <p class="text-body-secondary small">Your unused credit covers the new plan for the rest of this period — no charge.</p>
            @endif

            <form method="POST" action="{{ route('user.subscription.switch', $newPlan) }}">
                @csrf
                <button type="submit" class="btn btn-primary w-100">Confirm switch</button>
            </form>
        </div>
    </div>
@endsection
