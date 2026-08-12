@extends('layouts.dashboard')

@section('title', 'Accounts Dashboard')

@section('content')
    <h1 class="h3 fw-bold mb-1">Welcome, {{ auth()->user()->name }}</h1>
    <p class="text-body-secondary mb-4">
        Manage invoices, payouts, and revenue reconciliation for the platform.
    </p>

    @include('partials.dashboard-modules-card')
@endsection
