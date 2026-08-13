@extends('layouts.dashboard')

@section('title', 'Subscription Plans')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 fw-bold mb-0">Subscription Plans</h1>
        <a href="{{ route('admin.subscription-plans.create') }}" class="btn btn-primary">New plan</a>
    </div>

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
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($plans as $plan)
                        <tr>
                            <td>{{ $plan->name }}</td>
                            <td>${{ number_format($plan->price, 2) }}</td>
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
                            <td class="text-end">
                                <div class="d-inline-flex gap-2">
                                    <a href="{{ route('admin.subscription-plans.edit', $plan) }}" class="btn btn-sm btn-outline-secondary">Edit</a>

                                    @if ($plan->subscriptions_count === 0)
                                        <form method="POST" action="{{ route('admin.subscription-plans.destroy', $plan) }}" onsubmit="return confirm('Delete this plan?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
@endsection
