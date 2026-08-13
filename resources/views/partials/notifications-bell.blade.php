@php
    $recentNotifications = auth()->user()->unreadNotifications()->latest()->take(5)->get();
    $unreadCount = auth()->user()->unreadNotifications()->count();
@endphp

<div class="dropdown">
    <button
        class="btn btn-sm btn-outline-light position-relative"
        type="button"
        data-bs-toggle="dropdown"
        aria-expanded="false"
    >
        Notifications
        @if ($unreadCount > 0)
            <span class="badge rounded-pill text-bg-danger ms-1">{{ $unreadCount > 9 ? '9+' : $unreadCount }}</span>
        @endif
    </button>
    <div class="dropdown-menu dropdown-menu-end p-0" style="width: 22rem;">
        <div class="d-flex justify-content-between align-items-center px-3 py-2 border-bottom">
            <span class="fw-semibold small">Notifications</span>
            @if ($unreadCount > 0)
                <form method="POST" action="{{ route('notifications.read-all') }}">
                    @csrf
                    <button type="submit" class="btn btn-link btn-sm p-0">Mark all read</button>
                </form>
            @endif
        </div>

        @forelse ($recentNotifications as $notification)
            <form method="POST" action="{{ route('notifications.read', $notification->id) }}">
                @csrf
                <button type="submit" class="dropdown-item py-2 border-bottom text-wrap text-start small">
                    {{ $notification->data['message'] ?? 'Notification' }}
                    <div class="text-body-secondary" style="font-size: 0.75rem;">{{ $notification->created_at->diffForHumans() }}</div>
                </button>
            </form>
        @empty
            <p class="px-3 py-3 mb-0 text-body-secondary small">No new notifications.</p>
        @endforelse

        <a href="{{ route('notifications.index') }}" class="dropdown-item text-center small py-2 border-top">View all</a>
    </div>
</div>
