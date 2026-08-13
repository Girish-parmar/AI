@extends('layouts.dashboard')

@section('title', 'Payouts')

@section('content')
    <h1 class="h3 fw-bold mb-4">Payouts</h1>

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
                        <th class="text-end">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($summaries as $summary)
                        <tr>
                            <td>{{ $summary['creator']->name }}</td>
                            <td>${{ number_format($summary['earned'], 2) }}</td>
                            <td>${{ number_format($summary['paidOut'], 2) }}</td>
                            <td class="fw-semibold">${{ number_format($summary['outstanding'], 2) }}</td>
                            <td class="text-end">
                                @if ($summary['outstanding'] > 0)
                                    <form method="POST" action="{{ route('accounts.payouts.store') }}" class="d-inline-flex gap-2 justify-content-end">
                                        @csrf
                                        <input type="hidden" name="creator_id" value="{{ $summary['creator']->id }}">
                                        <input
                                            type="number"
                                            name="amount"
                                            step="0.01"
                                            min="0.01"
                                            max="{{ $summary['outstanding'] }}"
                                            value="{{ $summary['outstanding'] }}"
                                            class="form-control form-control-sm"
                                            style="width: 8rem;"
                                        >
                                        <button type="submit" class="btn btn-sm btn-primary text-nowrap">Record payout</button>
                                    </form>
                                @endif
                            </td>
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
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($payouts as $payout)
                        <tr>
                            <td>{{ $payout->creator->name }}</td>
                            <td>${{ number_format($payout->amount, 2) }}</td>
                            <td><span class="badge {{ $payout->status->badgeClass() }}">{{ $payout->status->label() }}</span></td>
                            <td>{{ $payout->created_at->format('Y-m-d') }}</td>
                            <td class="text-end">
                                @if ($payout->status->value === 'pending')
                                    <div class="d-inline-flex gap-2">
                                        <form method="POST" action="{{ route('accounts.payouts.pay', $payout) }}">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-success">Mark paid</button>
                                        </form>
                                        <form method="POST" action="{{ route('accounts.payouts.fail', $payout) }}">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-danger">Mark failed</button>
                                        </form>
                                    </div>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
@endsection
