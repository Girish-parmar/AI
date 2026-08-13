@extends('layouts.dashboard')

@section('title', 'Grant Demo Access')

@section('content')
    <h1 class="h3 fw-bold mb-4">Grant demo access</h1>

    <div class="card border-0 shadow-sm" style="max-width: 32rem;">
        <div class="card-body">
            @if ($errors->any())
                <div class="alert alert-danger" role="alert">
                    <ul class="mb-0 ps-3">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @if ($users->isEmpty())
                <p class="text-body-secondary mb-0">There are no User accounts to grant access to yet.</p>
            @elseif ($courses->isEmpty() && $scripts->isEmpty())
                <p class="text-body-secondary mb-0">There's no approved content to grant demo access to yet.</p>
            @else
                <form method="POST" action="{{ route('admin.demo-access.store') }}" novalidate>
                    @csrf

                    <div class="mb-3">
                        <label for="user_id" class="form-label">User</label>
                        <select id="user_id" name="user_id" class="form-select" required>
                            <option value="" disabled selected>Choose a user&hellip;</option>
                            @foreach ($users as $user)
                                <option value="{{ $user->id }}" @selected(old('user_id') == $user->id)>{{ $user->name }} ({{ $user->email }})</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="resource" class="form-label">Content</label>
                        <select id="resource" name="resource" class="form-select" required>
                            <option value="" disabled selected>Choose a course or script&hellip;</option>
                            @if ($courses->isNotEmpty())
                                <optgroup label="Courses">
                                    @foreach ($courses as $course)
                                        <option value="course:{{ $course->id }}" @selected(old('resource') === "course:{$course->id}")>{{ $course->title }}</option>
                                    @endforeach
                                </optgroup>
                            @endif
                            @if ($scripts->isNotEmpty())
                                <optgroup label="Scripts">
                                    @foreach ($scripts as $script)
                                        <option value="script:{{ $script->id }}" @selected(old('resource') === "script:{$script->id}")>{{ $script->title }}</option>
                                    @endforeach
                                </optgroup>
                            @endif
                        </select>
                    </div>

                    <div class="mb-4">
                        <label for="days" class="form-label">Duration (days)</label>
                        <input
                            id="days"
                            type="number"
                            name="days"
                            min="1"
                            max="365"
                            value="{{ old('days', 7) }}"
                            class="form-control"
                            style="max-width: 8rem;"
                            required
                        >
                    </div>

                    <button type="submit" class="btn btn-primary">Grant access</button>
                    <a href="{{ route('admin.demo-access.index') }}" class="btn btn-outline-secondary">Cancel</a>
                </form>
            @endif
        </div>
    </div>
@endsection
