@extends('layouts.dashboard')

@section('title', 'Notifications')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 fw-bold mb-0">Notifications</h1>
        @if ($notifications->contains(fn ($notification) => is_null($notification->read_at)))
            <form method="POST" action="{{ route('notifications.read-all') }}">
                @csrf
                <button type="submit" class="btn btn-outline-secondary btn-sm">Mark all read</button>
            </form>
        @endif
    </div>

    @if ($notifications->isEmpty())
        <p class="text-body-secondary">You don't have any notifications yet.</p>
    @else
        <div class="list-group">
            @foreach ($notifications as $notification)
                <div class="list-group-item d-flex justify-content-between align-items-start gap-3 {{ $notification->read_at ? '' : 'bg-body-tertiary' }}">
                    <div>
                        <p class="mb-1">
                            @if (! empty($notification->data['url']))
                                <a href="{{ $notification->data['url'] }}" class="text-body text-decoration-none">{{ $notification->data['message'] ?? 'Notification' }}</a>
                            @else
                                {{ $notification->data['message'] ?? 'Notification' }}
                            @endif
                        </p>
                        <span class="text-body-secondary small">{{ $notification->created_at->diffForHumans() }}</span>
                    </div>
                    @if (! $notification->read_at)
                        <form method="POST" action="{{ route('notifications.read', $notification->id) }}">
                            @csrf
                            <button type="submit" class="btn btn-sm btn-outline-secondary">Mark read</button>
                        </form>
                    @endif
                </div>
            @endforeach
        </div>

        <div class="mt-4">
            {{ $notifications->links() }}
        </div>
    @endif
@endsection
