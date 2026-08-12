@extends('layouts.dashboard')

@section('title', 'Edit Script')

@section('content')
    <h1 class="h3 fw-bold mb-4">Edit script</h1>

    <div class="card border-0 shadow-sm" style="max-width: 40rem;">
        <div class="card-body">
            @include('partials.content-form', [
                'action' => route('creator.scripts.update', $script),
                'method' => 'PUT',
                'content' => $script,
                'submitLabel' => 'Save changes',
                'cancelUrl' => route('creator.scripts.index'),
            ])
        </div>
    </div>
@endsection
