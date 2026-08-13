@extends('layouts.dashboard')

@section('title', 'My Scripts')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 fw-bold mb-0">My Scripts</h1>
        <a href="{{ route('creator.scripts.create') }}" class="btn btn-primary">New script</a>
    </div>

    @include('partials.content-type-tabs', ['active' => 'scripts', 'prefix' => 'creator'])

    @if ($scripts->isEmpty())
        <p class="text-body-secondary">You haven't created any scripts yet.</p>
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
                    @foreach ($scripts as $script)
                        <tr>
                            <td>{{ $script->title }}</td>
                            <td>{{ $script->category }}</td>
                            <td>{{ money($script->price) }}</td>
                            <td><span class="badge {{ $script->status->badgeClass() }}">{{ $script->status->label() }}</span></td>
                            <td class="text-end">
                                <div class="d-inline-flex gap-2">
                                    <a href="{{ route('creator.scripts.edit', $script) }}" class="btn btn-sm btn-outline-secondary">Edit</a>

                                    @if ($script->status->canSubmit())
                                        <form method="POST" action="{{ route('creator.scripts.submit', $script) }}">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-outline-primary">Submit</button>
                                        </form>
                                    @endif

                                    <form method="POST" action="{{ route('creator.scripts.destroy', $script) }}" onsubmit="return confirm('Delete this script?');">
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
