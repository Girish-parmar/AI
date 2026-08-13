@extends('layouts.dashboard')

@section('title', 'Demo Access')

@section('content')
    <h1 class="h3 fw-bold mb-4">Demo Access <span class="badge text-bg-light text-body-secondary">View only</span></h1>

    @if ($grants->isEmpty())
        <p class="text-body-secondary">No demo access has been granted yet.</p>
    @else
        <div class="table-responsive">
            <table class="table align-middle bg-white">
                <thead>
                    <tr>
                        <th>User</th>
                        <th>Content</th>
                        <th>Granted by</th>
                        <th>Expires</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($grants as $grant)
                        <tr>
                            <td>{{ $grant->user->name }}</td>
                            <td>{{ $grant->resource?->title ?? 'Content removed' }} <span class="badge text-bg-light text-body-secondary">{{ class_basename($grant->resource_type) }}</span></td>
                            <td>{{ $grant->grantedBy?->name ?? '—' }}</td>
                            <td>{{ $grant->expires_at->format('Y-m-d H:i') }}</td>
                            <td>
                                @if ($grant->expires_at->isFuture())
                                    <span class="badge text-bg-success">Active</span>
                                @else
                                    <span class="badge text-bg-secondary">Expired</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
@endsection
