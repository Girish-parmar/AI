@extends('layouts.dashboard')

@section('title', 'Browse Courses')

@section('content')
    <h1 class="h3 fw-bold mb-4">Browse Courses</h1>

    @include('partials.content-type-tabs', ['active' => 'courses', 'prefix' => 'user'])

    @if ($courses->isEmpty())
        <p class="text-body-secondary">No courses are available yet &mdash; check back soon.</p>
    @else
        <div class="row g-4">
            @foreach ($courses as $course)
                <div class="col-md-6 col-lg-4">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-body d-flex flex-column">
                            <span class="badge text-bg-light text-body-secondary align-self-start mb-2">{{ $course->category }}</span>
                            <h2 class="h5 fw-semibold">
                                <a href="{{ route('user.courses.show', $course) }}" class="text-decoration-none">{{ $course->title }}</a>
                            </h2>
                            <p class="text-body-secondary small flex-grow-1">{{ \Illuminate\Support\Str::limit($course->description, 120) }}</p>
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="fw-bold">${{ number_format($course->price, 2) }}</span>
                                <span class="text-body-secondary small">by {{ $course->creator->name }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
@endsection
