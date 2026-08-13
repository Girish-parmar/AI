@extends('layouts.app')

@section('title', $document->title)

@section('content')
    <section class="container py-5">
        <div class="row justify-content-center">
            <div class="col-12 col-lg-8">
                <h1 class="h2 fw-bold mb-1">{{ $document->title }}</h1>
                <p class="text-body-secondary mb-4">
                    Version {{ $document->version }} &middot; effective {{ $document->published_at->format('F j, Y') }}
                </p>
                <div style="white-space: pre-line;">{{ $document->content }}</div>
            </div>
        </div>
    </section>
@endsection
