@extends('layouts.dashboard')

@section('title', 'Edit Script')

@section('content')
    <h1 class="h3 fw-bold mb-4">Edit script</h1>
    <p class="text-body-secondary mb-4">Owned by {{ $script->creator->name }}.</p>

    <div class="card border-0 shadow-sm" style="max-width: 40rem;">
        <div class="card-body">
            @include('partials.content-form', [
                'action' => route('admin.scripts.update', $script),
                'method' => 'PUT',
                'content' => $script,
                'submitLabel' => 'Save changes',
                'cancelUrl' => route('admin.scripts.index'),
            ])
        </div>
    </div>
@endsection
