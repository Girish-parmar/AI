@extends('layouts.dashboard')

@section('title', 'Edit Subscription Plan')

@section('content')
    <h1 class="h3 fw-bold mb-4">Edit subscription plan</h1>

    <div class="card border-0 shadow-sm" style="max-width: 40rem;">
        <div class="card-body">
            @include('partials.subscription-plan-form', [
                'action' => route('admin.subscription-plans.update', $plan),
                'method' => 'PUT',
                'plan' => $plan,
                'submitLabel' => 'Save changes',
                'cancelUrl' => route('admin.subscription-plans.index'),
            ])
        </div>
    </div>
@endsection
