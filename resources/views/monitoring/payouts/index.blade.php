@extends('layouts.dashboard')

@section('title', 'Finance & Payouts')

@section('content')
    <h1 class="h3 fw-bold mb-4">Finance &amp; Payouts <span class="badge text-bg-light text-body-secondary">View only</span></h1>

    <h2 class="h5 fw-semibold mb-3">Creator balances</h2>
    @if ($summaries->isEmpty())
        <p class="text-body-secondary">No creators yet.</p>
    @else
        <div class="table-responsive mb-5">
            <table class="table align-middle bg-white">
                <thead>
                    <tr>
                        <th>Creator</th>
                        <th>Earned</th>
                        <th>Paid out</th>
                        <th>Outstanding</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($summaries as $summary)
                        <tr>
                            <td>{{ $summary['creator']->name }}</td>
                            <td>${{ number_format($summary['earned'], 2) }}</td>
                            <td>${{ number_format($summary['paidOut'], 2) }}</td>
                            <td class="fw-semibold">${{ number_format($summary['outstanding'], 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

    <h2 class="h5 fw-semibold mb-3">Payout history</h2>
    @if ($payouts->isEmpty())
        <p class="text-body-secondary">No payouts recorded yet.</p>
    @else
        <div class="table-responsive">
            <table class="table align-middle bg-white">
                <thead>
                    <tr>
                        <th>Creator</th>
                        <th>Amount</th>
                        <th>Status</th>
                        <th>Requested</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($payouts as $payout)
                        <tr>
                            <td>{{ $payout->creator->name }}</td>
                            <td>${{ number_format($payout->amount, 2) }}</td>
                            <td><span class="badge {{ $payout->status->badgeClass() }}">{{ $payout->status->label() }}</span></td>
                            <td>{{ $payout->created_at->format('Y-m-d') }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
@endsection
