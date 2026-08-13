@extends('layouts.dashboard')

@section('title', $course->title)

@section('content')
    <a href="{{ route('user.courses.index') }}" class="d-inline-block mb-3">&larr; Back to courses</a>

    <div class="card border-0 shadow-sm" style="max-width: 40rem;">
        <div class="card-body">
            <span class="badge text-bg-light text-body-secondary mb-2">{{ $course->category }}</span>
            <h1 class="h3 fw-bold">{{ $course->title }}</h1>
            <p class="text-body-secondary mb-3">by {{ $course->creator->name }}</p>
            <p class="fs-4 fw-bold mb-3">${{ number_format($course->price, 2) }}</p>

            @if ($purchase)
                <div class="alert {{ $purchase->status->value === 'completed' ? 'alert-success' : 'alert-warning' }}">
                    Purchase status: <strong>{{ $purchase->status->label() }}</strong>
                    @if ($purchase->status->value === 'pending')
                        &mdash; awaiting payment confirmation.
                    @endif
                </div>
            @else
                <form method="POST" action="{{ route('user.courses.purchase', $course) }}" class="mb-3">
                    @csrf
                    <button type="submit" class="btn btn-primary">Buy this course</button>
                </form>
            @endif

            <p style="white-space: pre-line;">{{ $course->description }}</p>
        </div>
    </div>
@endsection
