@extends('layouts.dashboard')

@section('title', 'Monitoring Dashboard')

@section('content')
    <h1 class="h3 fw-bold mb-1">Welcome, {{ auth()->user()->name }}</h1>
    <p class="text-body-secondary mb-4">
        You have view access across the platform, plus full control over audit logs, legal &amp; compliance,
        and advertising.
    </p>

    @include('partials.dashboard-modules-card')
@endsection
