@extends('layouts.dashboard')

@section('title', 'My Advertisements')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 fw-bold mb-0">My Advertisements</h1>
        <a href="{{ route('creator.advertisements.create') }}" class="btn btn-primary">New advertisement</a>
    </div>

    @if ($advertisements->isEmpty())
        <p class="text-body-secondary">You haven't created any advertisements yet.</p>
    @else
        <div class="table-responsive">
            <table class="table align-middle bg-white">
                <thead>
                    <tr>
                        <th>Title</th>
                        <th>Target URL</th>
                        <th>Status</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($advertisements as $advertisement)
                        <tr>
                            <td>{{ $advertisement->title }}</td>
                            <td><a href="{{ $advertisement->target_url }}" target="_blank" rel="noopener">{{ Str::limit($advertisement->target_url, 40) }}</a></td>
                            <td><span class="badge {{ $advertisement->status->badgeClass() }}">{{ $advertisement->status->label() }}</span></td>
                            <td class="text-end">
                                <div class="d-inline-flex gap-2">
                                    <a href="{{ route('creator.advertisements.edit', $advertisement) }}" class="btn btn-sm btn-outline-secondary">Edit</a>

                                    @if ($advertisement->status->canSubmit())
                                        <form method="POST" action="{{ route('creator.advertisements.submit', $advertisement) }}">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-outline-primary">Submit</button>
                                        </form>
                                    @endif

                                    <form method="POST" action="{{ route('creator.advertisements.destroy', $advertisement) }}" onsubmit="return confirm('Delete this advertisement?');">
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
