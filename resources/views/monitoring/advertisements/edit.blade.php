@extends('layouts.dashboard')

@section('title', 'Edit Advertisement')

@section('content')
    <h1 class="h3 fw-bold mb-4">Edit advertisement</h1>
    <p class="text-body-secondary mb-4">Submitted by {{ $advertisement->creator->name }}.</p>

    <div class="card border-0 shadow-sm" style="max-width: 40rem;">
        <div class="card-body">
            @include('partials.advertisement-form', [
                'action' => route('monitoring.advertisements.update', $advertisement),
                'method' => 'PUT',
                'advertisement' => $advertisement,
                'submitLabel' => 'Save changes',
                'cancelUrl' => route('monitoring.advertisements.index'),
            ])
        </div>
    </div>
@endsection
