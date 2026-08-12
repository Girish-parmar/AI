@extends('layouts.dashboard')

@section('title', 'Edit Course')

@section('content')
    <h1 class="h3 fw-bold mb-4">Edit course</h1>
    <p class="text-body-secondary mb-4">Owned by {{ $course->creator->name }}.</p>

    <div class="card border-0 shadow-sm" style="max-width: 40rem;">
        <div class="card-body">
            @include('partials.content-form', [
                'action' => route('admin.courses.update', $course),
                'method' => 'PUT',
                'content' => $course,
                'submitLabel' => 'Save changes',
                'cancelUrl' => route('admin.courses.index'),
            ])
        </div>
    </div>
@endsection
