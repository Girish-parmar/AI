@extends('layouts.dashboard')

@section('title', 'Legal & Compliance')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 fw-bold mb-0">Legal &amp; Compliance</h1>
        <a href="{{ route('monitoring.legal-documents.create') }}" class="btn btn-primary">New document</a>
    </div>

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
                        <th class="text-end">Actions</th>
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
                            <td class="text-end">
                                <div class="d-inline-flex gap-2">
                                    @if ($document->published_at)
                                        <a href="{{ route('legal.show', $document->type->value) }}" target="_blank" rel="noopener" class="btn btn-sm btn-outline-secondary">View live</a>
                                        <form method="POST" action="{{ route('monitoring.legal-documents.unpublish', $document) }}">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-outline-warning">Unpublish</button>
                                        </form>
                                    @else
                                        <form method="POST" action="{{ route('monitoring.legal-documents.publish', $document) }}">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-success">Publish</button>
                                        </form>
                                    @endif

                                    <a href="{{ route('monitoring.legal-documents.edit', $document) }}" class="btn btn-sm btn-outline-secondary">Edit</a>

                                    @unless ($document->published_at)
                                        <form method="POST" action="{{ route('monitoring.legal-documents.destroy', $document) }}" onsubmit="return confirm('Delete this document?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                                        </form>
                                    @endunless
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
@endsection
