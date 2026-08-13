@extends('layouts.dashboard')

@section('title', 'Subscription Plans')

@section('content')
    <h1 class="h3 fw-bold mb-4">Subscription Plans <span class="badge text-bg-light text-body-secondary">View only</span></h1>

    @if ($plans->isEmpty())
        <p class="text-body-secondary">No subscription plans yet.</p>
    @else
        <div class="table-responsive">
            <table class="table align-middle bg-white">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Price</th>
                        <th>Billing</th>
                        <th>Trial</th>
                        <th>Subscribers</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($plans as $plan)
                        <tr>
                            <td>{{ $plan->name }}</td>
                            <td>{{ money($plan->price) }}</td>
                            <td>{{ $plan->billing_interval->label() }}</td>
                            <td>{{ $plan->hasTrial() ? "{$plan->trial_days} days" : '—' }}</td>
                            <td>{{ $plan->subscriptions_count }}</td>
                            <td>
                                @if ($plan->is_active)
                                    <span class="badge text-bg-success">Active</span>
                                @else
                                    <span class="badge text-bg-secondary">Inactive</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
@endsection
