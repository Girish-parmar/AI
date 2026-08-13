@extends('layouts.dashboard')

@section('title', 'Advertisements')

@section('content')
    <h1 class="h3 fw-bold mb-4">Advertising</h1>

    <form method="GET" class="mb-4 d-flex align-items-center gap-2">
        <label for="status" class="form-label mb-0 small text-body-secondary">Filter by status</label>
        <select name="status" id="status" class="form-select form-select-sm w-auto" onchange="this.form.submit()">
            <option value="">All</option>
            @foreach (['draft', 'pending', 'approved', 'rejected'] as $option)
                <option value="{{ $option }}" @selected($statusFilter === $option)>{{ ucfirst($option) }}</option>
            @endforeach
        </select>
    </form>

    @if ($advertisements->isEmpty())
        <p class="text-body-secondary">No advertisements match this filter.</p>
    @else
        <div class="table-responsive">
            <table class="table align-middle bg-white">
                <thead>
                    <tr>
                        <th>Title</th>
                        <th>Creator</th>
                        <th>Target URL</th>
                        <th>Window</th>
                        <th>Status</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($advertisements as $advertisement)
                        <tr>
                            <td>{{ $advertisement->title }}</td>
                            <td>{{ $advertisement->creator->name }}</td>
                            <td><a href="{{ $advertisement->target_url }}" target="_blank" rel="noopener">{{ Str::limit($advertisement->target_url, 30) }}</a></td>
                            <td class="small text-body-secondary">
                                {{ $advertisement->starts_at?->toFormattedDateString() ?? 'Any time' }}
                                &ndash;
                                {{ $advertisement->ends_at?->toFormattedDateString() ?? 'Indefinite' }}
                            </td>
                            <td><span class="badge {{ $advertisement->status->badgeClass() }}">{{ $advertisement->status->label() }}</span></td>
                            <td class="text-end">
                                <div class="d-inline-flex gap-2">
                                    @if ($advertisement->status->value === 'pending')
                                        <form method="POST" action="{{ route('admin.advertisements.approve', $advertisement) }}">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-success">Approve</button>
                                        </form>
                                        <button type="button" class="btn btn-sm btn-danger" data-bs-toggle="modal" data-bs-target="#reject-ad-{{ $advertisement->id }}">Reject</button>
                                    @endif

                                    <a href="{{ route('admin.advertisements.edit', $advertisement) }}" class="btn btn-sm btn-outline-secondary">Edit</a>

                                    <form method="POST" action="{{ route('admin.advertisements.destroy', $advertisement) }}" onsubmit="return confirm('Delete this advertisement?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                                    </form>
                                </div>
                            </td>
                        </tr>

                        @if ($advertisement->status->value === 'pending')
                            <div class="modal fade" id="reject-ad-{{ $advertisement->id }}" tabindex="-1" aria-hidden="true">
                                <div class="modal-dialog">
                                    <form method="POST" action="{{ route('admin.advertisements.reject', $advertisement) }}" class="modal-content">
                                        @csrf
                                        <div class="modal-header">
                                            <h2 class="h6 modal-title">Reject &ldquo;{{ $advertisement->title }}&rdquo;</h2>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                        </div>
                                        <div class="modal-body">
                                            <label for="notes-{{ $advertisement->id }}" class="form-label">Reason (optional, shared with the creator)</label>
                                            <textarea id="notes-{{ $advertisement->id }}" name="notes" rows="3" class="form-control"></textarea>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                                            <button type="submit" class="btn btn-danger">Reject</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        @endif
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
@endsection
