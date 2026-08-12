@extends('layouts.dashboard')

@section('title', 'My Courses')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 fw-bold mb-0">My Courses</h1>
        <a href="{{ route('creator.courses.create') }}" class="btn btn-primary">New course</a>
    </div>

    @include('partials.content-type-tabs', ['active' => 'courses', 'prefix' => 'creator'])

    @if ($courses->isEmpty())
        <p class="text-body-secondary">You haven't created any courses yet.</p>
    @else
        <div class="table-responsive">
            <table class="table align-middle bg-white">
                <thead>
                    <tr>
                        <th>Title</th>
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
                            <td>{{ $course->category }}</td>
                            <td>${{ number_format($course->price, 2) }}</td>
                            <td><span class="badge {{ $course->status->badgeClass() }}">{{ $course->status->label() }}</span></td>
                            <td class="text-end">
                                <div class="d-inline-flex gap-2">
                                    <a href="{{ route('creator.courses.edit', $course) }}" class="btn btn-sm btn-outline-secondary">Edit</a>

                                    @if ($course->status->canSubmit())
                                        <form method="POST" action="{{ route('creator.courses.submit', $course) }}">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-outline-primary">Submit</button>
                                        </form>
                                    @endif

                                    <form method="POST" action="{{ route('creator.courses.destroy', $course) }}" onsubmit="return confirm('Delete this course?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
@endsection
