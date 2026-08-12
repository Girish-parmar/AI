@extends('layouts.dashboard')

@section('title', 'Scripts')

@section('content')
    <h1 class="h3 fw-bold mb-4">Courses &amp; Scripts <span class="badge text-bg-light text-body-secondary">View only</span></h1>

    @include('partials.content-type-tabs', ['active' => 'scripts', 'prefix' => 'monitoring'])

    <form method="GET" class="mb-4 d-flex align-items-center gap-2">
        <label for="status" class="form-label mb-0 small text-body-secondary">Filter by status</label>
        <select name="status" id="status" class="form-select form-select-sm w-auto" onchange="this.form.submit()">
            <option value="">All</option>
            @foreach (['draft', 'pending', 'approved', 'rejected'] as $option)
                <option value="{{ $option }}" @selected($statusFilter === $option)>{{ ucfirst($option) }}</option>
            @endforeach
        </select>
    </form>

    @if ($scripts->isEmpty())
        <p class="text-body-secondary">No scripts match this filter.</p>
    @else
        <div class="table-responsive">
            <table class="table align-middle bg-white">
                <thead>
                    <tr>
                        <th>Title</th>
                        <th>Creator</th>
                        <th>Category</th>
                        <th>Price</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($scripts as $script)
                        <tr>
                            <td>{{ $script->title }}</td>
                            <td>{{ $script->creator->name }}</td>
                            <td>{{ $script->category }}</td>
                            <td>${{ number_format($script->price, 2) }}</td>
                            <td><span class="badge {{ $script->status->badgeClass() }}">{{ $script->status->label() }}</span></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
@endsection
