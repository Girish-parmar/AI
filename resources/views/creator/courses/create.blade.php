@extends('layouts.dashboard')

@section('title', 'New Course')

@section('content')
    <h1 class="h3 fw-bold mb-4">New course</h1>

    <div class="card border-0 shadow-sm" style="max-width: 40rem;">
        <div class="card-body">
            @include('partials.content-form', [
                'action' => route('creator.courses.store'),
                'method' => 'POST',
                'submitLabel' => 'Create course',
                'cancelUrl' => route('creator.courses.index'),
            ])
        </div>
    </div>
@endsection
