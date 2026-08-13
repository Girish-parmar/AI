@extends('layouts.dashboard')

@section('title', $script->title)

@section('content')
    <a href="{{ route('user.scripts.index') }}" class="d-inline-block mb-3">&larr; Back to scripts</a>

    <div class="card border-0 shadow-sm" style="max-width: 40rem;">
        <div class="card-body">
            <span class="badge text-bg-light text-body-secondary mb-2">{{ $script->category }}</span>
            <h1 class="h3 fw-bold">{{ $script->title }}</h1>
            <p class="text-body-secondary mb-3">by {{ $script->creator->name }}</p>
            <p class="fs-4 fw-bold mb-3">${{ number_format($script->price, 2) }}</p>

            @if ($demoAccess)
                <div class="alert alert-info">
                    You have demo access to this script until <strong>{{ $demoAccess->expires_at->format('M j, Y g:ia') }}</strong>.
                </div>
            @endif

            @if ($purchase)
                <div class="alert {{ $purchase->status->value === 'completed' ? 'alert-success' : 'alert-warning' }}">
                    Purchase status: <strong>{{ $purchase->status->label() }}</strong>
                    @if ($purchase->status->value === 'pending')
                        &mdash; awaiting payment confirmation.
                    @endif
                </div>
            @else
                <form method="POST" action="{{ route('user.scripts.purchase', $script) }}" class="mb-3">
                    @csrf
                    <button type="submit" class="btn btn-primary">Buy this script</button>
                </form>
            @endif

            <p style="white-space: pre-line;">{{ $script->description }}</p>
        </div>
    </div>
@endsection
