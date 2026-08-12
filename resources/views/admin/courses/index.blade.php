@extends('layouts.dashboard')

@section('title', 'Courses')

@section('content')
    <h1 class="h3 fw-bold mb-4">Courses &amp; Scripts</h1>

    @include('partials.content-type-tabs', ['active' => 'courses', 'prefix' => 'admin'])

    <form method="GET" class="mb-4 d-flex align-items-center gap-2">
        <label for="status" class="form-label mb-0 small text-body-secondary">Filter by status</label>
        <select name="status" id="status" class="form-select form-select-sm w-auto" onchange="this.form.submit()">
            <option value="">All</option>
            @foreach (['draft', 'pending', 'approved', 'rejected'] as $option)
                <option value="{{ $option }}" @selected($statusFilter === $option)>{{ ucfirst($option) }}</option>
            @endforeach
        </select>
    </form>

    @if ($courses->isEmpty())
        <p class="text-body-secondary">No courses match this filter.</p>
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
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($courses as $course)
                        <tr>
                            <td>{{ $course->title }}</td>
                            <td>{{ $course->creator->name }}</td>
                            <td>{{ $course->category }}</td>
                            <td>${{ number_format($course->price, 2) }}</td>
                            <td><span class="badge {{ $course->status->badgeClass() }}">{{ $course->status->label() }}</span></td>
                            <td class="text-end">
                                <div class="d-inline-flex gap-2">
                                    @if ($course->status->value === 'pending')
                                        <form method="POST" action="{{ route('admin.courses.approve', $course) }}">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-success">Approve</button>
                                        </form>
                                        <button type="button" class="btn btn-sm btn-danger" data-bs-toggle="modal" data-bs-target="#reject-course-{{ $course->id }}">Reject</button>
                                    @endif

                                    <a href="{{ route('admin.courses.edit', $course) }}" class="btn btn-sm btn-outline-secondary">Edit</a>

                                    <form method="POST" action="{{ route('admin.courses.destroy', $course) }}" onsubmit="return confirm('Delete this course?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                                    </form>
                                </div>
                            </td>
                        </tr>

                        @if ($course->status->value === 'pending')
                            <div class="modal fade" id="reject-course-{{ $course->id }}" tabindex="-1" aria-hidden="true">
                                <div class="modal-dialog">
                                    <form method="POST" action="{{ route('admin.courses.reject', $course) }}" class="modal-content">
                                        @csrf
                                        <div class="modal-header">
                                            <h2 class="h6 modal-title">Reject &ldquo;{{ $course->title }}&rdquo;</h2>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                        </div>
                                        <div class="modal-body">
                                            <label for="notes-{{ $course->id }}" class="form-label">Reason (optional, shared with the creator)</label>
                                            <textarea id="notes-{{ $course->id }}" name="notes" rows="3" class="form-control"></textarea>
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
