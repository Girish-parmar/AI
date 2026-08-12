@extends('layouts.dashboard')

@section('title', 'Dashboard')

@section('content')
    <h1 class="h3 fw-bold mb-1">Welcome, {{ auth()->user()->name }}</h1>
    <p class="text-body-secondary mb-4">
        Browse courses, manage your subscriptions and purchases, and try out demo content.
    </p>

    @include('partials.dashboard-modules-card')
@endsection
