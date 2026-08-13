@extends('layouts.dashboard')

@section('title', 'Legal & Compliance')

@section('content')
    <h1 class="h3 fw-bold mb-4">Legal &amp; Compliance <span class="badge text-bg-light text-body-secondary">View only</span></h1>

    @if ($documents->isEmpty())
        <p class="text-body-secondary">No legal documents yet.</p>
    @else
        <div class="table-responsive">
            <table class="table align-middle bg-white">
                <thead>
                    <tr>
                        <th>Type</th>
                        <th>Title</th>
                        <th>Version</th>
                        <th>Author</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($documents as $document)
                        <tr>
                            <td>{{ $document->type->label() }}</td>
                            <td>{{ $document->title }}</td>
                            <td>{{ $document->version }}</td>
                            <td>{{ $document->author?->name ?? '—' }}</td>
                            <td>
                                @if ($document->published_at)
                                    <span class="badge text-bg-success">Published {{ $document->published_at->format('Y-m-d') }}</span>
                                @else
                                    <span class="badge text-bg-secondary">Draft</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
@endsection
