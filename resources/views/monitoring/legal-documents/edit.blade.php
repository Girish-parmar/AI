@extends('layouts.dashboard')

@section('title', 'Edit Legal Document')

@section('content')
    <h1 class="h3 fw-bold mb-4">Edit legal document</h1>

    @if ($document->published_at)
        <div class="alert alert-warning" style="max-width: 44rem;">
            This document is currently published. Saving changes here updates the live version immediately —
            consider creating a new draft with a bumped version number instead if this is a substantive change.
        </div>
    @endif

    <div class="card border-0 shadow-sm" style="max-width: 44rem;">
        <div class="card-body">
            @include('partials.legal-document-form', [
                'action' => route('monitoring.legal-documents.update', $document),
                'method' => 'PUT',
                'document' => $document,
                'submitLabel' => 'Save changes',
                'cancelUrl' => route('monitoring.legal-documents.index'),
            ])
        </div>
    </div>
@endsection
