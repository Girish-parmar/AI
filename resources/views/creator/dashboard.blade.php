@extends('layouts.dashboard')

@section('title', 'Creator Dashboard')

@section('content')
    <h1 class="h3 fw-bold mb-1">Welcome, {{ auth()->user()->name }}</h1>
    <p class="text-body-secondary mb-4">
        Publish courses and scripts, submit them for approval, and track what you've earned.
    </p>

    @include('partials.dashboard-modules-card')
@endsection
