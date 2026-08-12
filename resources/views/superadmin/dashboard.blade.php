@extends('layouts.dashboard')

@section('title', 'Super Admin Dashboard')

@section('content')
    <h1 class="h3 fw-bold mb-1">Welcome, {{ auth()->user()->name }}</h1>
    <p class="text-body-secondary mb-4">
        You have full control over the platform: system settings, every admin account, and every module below.
    </p>

    @include('partials.dashboard-modules-card')
@endsection
