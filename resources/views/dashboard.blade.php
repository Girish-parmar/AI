@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
    <section class="container py-5">
        <h1 class="h3 fw-bold mb-2">Welcome, {{ auth()->user()->name }}</h1>
        <p class="text-body-secondary">
            You're signed in as <strong>{{ auth()->user()->role }}</strong>. Role-specific dashboards
            (courses, approvals, subscriptions, finance, audit) are coming in a later phase.
        </p>
    </section>
@endsection
