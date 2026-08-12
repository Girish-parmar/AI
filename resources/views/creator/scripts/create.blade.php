@extends('layouts.dashboard')

@section('title', 'New Script')

@section('content')
    <h1 class="h3 fw-bold mb-4">New script</h1>

    <div class="card border-0 shadow-sm" style="max-width: 40rem;">
        <div class="card-body">
            @include('partials.content-form', [
                'action' => route('creator.scripts.store'),
                'method' => 'POST',
                'submitLabel' => 'Create script',
                'cancelUrl' => route('creator.scripts.index'),
            ])
        </div>
    </div>
@endsection
