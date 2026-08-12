<aside class="bg-body-tertiary border-end p-3" style="width: 15rem;">
    <div class="text-uppercase text-body-secondary small fw-semibold mb-2 px-2">{{ $role->label() }}</div>
    <ul class="nav nav-pills flex-column gap-1">
        @foreach ($role->navItems() as $item)
            <li class="nav-item">
                @if ($item['available'])
                    <a
                        href="{{ route($role->dashboardRoute()) }}"
                        class="nav-link {{ request()->routeIs($role->dashboardRoute()) ? 'active' : 'text-body' }}"
                    >
                        {{ $item['label'] }}
                    </a>
                @else
                    <span class="nav-link text-body-secondary d-flex justify-content-between align-items-center disabled">
                        {{ $item['label'] }}
                        <span class="badge text-bg-secondary ms-2">{{ $item['note'] ?? 'Soon' }}</span>
                    </span>
                @endif
            </li>
        @endforeach
    </ul>
</aside>
