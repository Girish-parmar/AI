@extends('layouts.dashboard')

@section('title', 'Advertisements')

@section('content')
    <h1 class="h3 fw-bold mb-4">Advertising <span class="badge text-bg-light text-body-secondary">View only</span></h1>

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
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
@endsection
