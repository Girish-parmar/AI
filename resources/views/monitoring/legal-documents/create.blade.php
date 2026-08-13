@extends('layouts.dashboard')

@section('title', 'New Legal Document')

@section('content')
    <h1 class="h3 fw-bold mb-4">New legal document</h1>

    <div class="card border-0 shadow-sm" style="max-width: 44rem;">
        <div class="card-body">
            @include('partials.legal-document-form', [
                'action' => route('monitoring.legal-documents.store'),
                'method' => 'POST',
                'submitLabel' => 'Create draft',
                'cancelUrl' => route('monitoring.legal-documents.index'),
            ])
        </div>
    </div>
@endsection
