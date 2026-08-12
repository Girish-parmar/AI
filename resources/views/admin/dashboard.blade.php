@extends('layouts.dashboard')

@section('title', 'Admin Dashboard')

@section('content')
    <h1 class="h3 fw-bold mb-1">Welcome, {{ auth()->user()->name }}</h1>
    <p class="text-body-secondary mb-4">
        Manage courses, scripts, approvals, subscriptions, purchases, and users from here.
    </p>

    @include('partials.dashboard-modules-card')
@endsection
