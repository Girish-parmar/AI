@extends('layouts.dashboard')

@section('title', 'Audit Logs')

@section('content')
    <h1 class="h3 fw-bold mb-4">Audit Logs</h1>

    @if ($logs->isEmpty())
        <p class="text-body-secondary">No activity has been recorded yet.</p>
    @else
        <div class="table-responsive">
            <table class="table align-middle bg-white">
                <thead>
                    <tr>
                        <th>When</th>
                        <th>User</th>
                        <th>Action</th>
                        <th>Entity</th>
                        <th>IP address</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($logs as $log)
                        <tr>
                            <td class="text-nowrap">{{ $log->created_at->format('Y-m-d H:i:s') }}</td>
                            <td>{{ $log->user?->name ?? 'Unknown' }}</td>
                            <td><code>{{ $log->action }}</code></td>
                            <td>
                                @if ($log->entity_type)
                                    {{ class_basename($log->entity_type) }} #{{ $log->entity_id }}
                                @else
                                    &mdash;
                                @endif
                            </td>
                            <td>{{ $log->ip_address ?? '—' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        {{ $logs->links() }}
    @endif
@endsection
