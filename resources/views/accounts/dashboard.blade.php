@extends('layouts.dashboard')

@section('title', 'Accounts Dashboard')

@section('content')
    <h1 class="h3 fw-bold mb-1">Welcome, {{ auth()->user()->name }}</h1>
    <p class="text-body-secondary mb-4">
        Manage invoices, payouts, and revenue reconciliation for the platform.
    </p>

    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <h2 class="h6 fw-semibold mb-3">Modules</h2>
            <ul class="list-group list-group-flush">
                @foreach ($role->navItems() as $item)
                    <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                        {{ $item['label'] }}
                        @if ($item['available'])
                            <span class="badge text-bg-success">Live</span>
                        @else
                            <span class="badge text-bg-secondary">{{ $item['note'] ?? 'Coming soon' }}</span>
                        @endif
                    </li>
                @endforeach
            </ul>
        </div>
    </div>
@endsection
