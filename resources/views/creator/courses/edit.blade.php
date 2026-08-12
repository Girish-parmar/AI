@extends('layouts.dashboard')

@section('title', 'Edit Course')

@section('content')
    <h1 class="h3 fw-bold mb-4">Edit course</h1>

    <div class="card border-0 shadow-sm" style="max-width: 40rem;">
        <div class="card-body">
            @include('partials.content-form', [
                'action' => route('creator.courses.update', $course),
                'method' => 'PUT',
                'content' => $course,
                'submitLabel' => 'Save changes',
                'cancelUrl' => route('creator.courses.index'),
            ])
        </div>
    </div>
@endsection
