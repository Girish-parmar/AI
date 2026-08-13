@extends('layouts.dashboard')

@section('title', 'New Advertisement')

@section('content')
    <h1 class="h3 fw-bold mb-4">New advertisement</h1>

    <div class="card border-0 shadow-sm" style="max-width: 40rem;">
        <div class="card-body">
            @include('partials.advertisement-form', [
                'action' => route('creator.advertisements.store'),
                'method' => 'POST',
                'submitLabel' => 'Create advertisement',
                'cancelUrl' => route('creator.advertisements.index'),
            ])
        </div>
    </div>
@endsection
