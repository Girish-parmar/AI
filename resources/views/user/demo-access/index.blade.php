@extends('layouts.dashboard')

@section('title', 'Demo Content')

@section('content')
    <h1 class="h3 fw-bold mb-4">Demo Content</h1>

    @if ($grants->isEmpty())
        <p class="text-body-secondary">You don't have any demo access yet. An admin can grant you temporary access to a course or script.</p>
    @else
        <div class="table-responsive">
            <table class="table align-middle bg-white">
                <thead>
                    <tr>
                        <th>Content</th>
                        <th>Expires</th>
                        <th>Status</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($grants as $grant)
                        @php
                            $active = $grant->expires_at->isFuture();
                            $showRoute = $grant->resource_type === \App\Models\Course::class ? 'user.courses.show' : 'user.scripts.show';
                        @endphp
                        <tr>
                            <td>
                                {{ $grant->resource?->title ?? 'Content removed' }}
                                <span class="badge text-bg-light text-body-secondary">{{ class_basename($grant->resource_type) }}</span>
                            </td>
                            <td>{{ $grant->expires_at->format('Y-m-d H:i') }}</td>
                            <td>
                                @if ($active)
                                    <span class="badge text-bg-success">Active</span>
                                @else
                                    <span class="badge text-bg-secondary">Expired</span>
                                @endif
                            </td>
                            <td class="text-end">
                                @if ($grant->resource)
                                    <a href="{{ route($showRoute, $grant->resource_id) }}" class="btn btn-sm btn-outline-primary">View</a>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
@endsection
