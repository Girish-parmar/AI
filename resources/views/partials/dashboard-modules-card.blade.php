<div class="card border-0 shadow-sm">
    <div class="card-body">
        <h2 class="h6 fw-semibold mb-3">Modules</h2>
        <ul class="list-group list-group-flush">
            @foreach ($role->navItems() as $item)
                <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                    @if ($item['available'])
                        <a href="{{ route($item['route']) }}">{{ $item['label'] }}</a>
                    @else
                        <span>{{ $item['label'] }}</span>
                    @endif

                    @if ($item['available'])
                        <span class="badge text-bg-success">{{ $item['note'] ?? 'Live' }}</span>
                    @else
                        <span class="badge text-bg-secondary">{{ $item['note'] ?? 'Coming soon' }}</span>
                    @endif
                </li>
            @endforeach
        </ul>
    </div>
</div>
