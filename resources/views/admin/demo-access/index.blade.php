@extends('layouts.dashboard')

@section('title', 'Demo Access')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 fw-bold mb-0">Demo Access</h1>
        <a href="{{ route('admin.demo-access.create') }}" class="btn btn-primary">Grant demo access</a>
    </div>

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
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($grants as $grant)
                        @php $active = $grant->expires_at->isFuture(); @endphp
                        <tr>
                            <td>{{ $grant->user->name }}</td>
                            <td>{{ $grant->resource?->title ?? 'Content removed' }} <span class="badge text-bg-light text-body-secondary">{{ class_basename($grant->resource_type) }}</span></td>
                            <td>{{ $grant->grantedBy?->name ?? '—' }}</td>
                            <td>{{ $grant->expires_at->format('Y-m-d H:i') }}</td>
                            <td>
                                @if ($active)
                                    <span class="badge text-bg-success">Active</span>
                                @else
                                    <span class="badge text-bg-secondary">Expired</span>
                                @endif
                            </td>
                            <td class="text-end">
                                @if ($active)
                                    <form method="POST" action="{{ route('admin.demo-access.revoke', $grant) }}" onsubmit="return confirm('Revoke this demo access now?');">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-outline-danger">Revoke</button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
@endsection
