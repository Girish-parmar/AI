@extends('layouts.dashboard')

@section('title', 'Earnings')

@section('content')
    <h1 class="h3 fw-bold mb-4">Earnings</h1>

    <div class="row g-3 mb-4">
        <div class="col-sm-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="text-body-secondary small text-uppercase">Total earned</div>
                    <div class="fs-4 fw-bold">{{ money($totalEarned) }}</div>
                </div>
            </div>
        </div>
        <div class="col-sm-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="text-body-secondary small text-uppercase">Paid out</div>
                    <div class="fs-4 fw-bold">{{ money($totalPaidOut) }}</div>
                </div>
            </div>
        </div>
        <div class="col-sm-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="text-body-secondary small text-uppercase">Outstanding</div>
                    <div class="fs-4 fw-bold">{{ money($outstanding) }}</div>
                </div>
            </div>
        </div>
    </div>

    <h2 class="h5 fw-semibold mb-3">Sales</h2>
    @if ($purchases->isEmpty())
        <p class="text-body-secondary">No one has purchased your content yet.</p>
    @else
        <div class="table-responsive mb-4">
            <table class="table align-middle bg-white">
                <thead>
                    <tr>
                        <th>Item</th>
                        <th>Type</th>
                        <th>Price</th>
                        <th>Status</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($purchases as $purchase)
                        <tr>
                            <td>{{ $purchase->purchasable->title ?? 'Deleted item' }}</td>
                            <td>{{ class_basename($purchase->purchasable_type) }}</td>
                            <td>{{ money($purchase->price) }}</td>
                            <td><span class="badge {{ $purchase->status->badgeClass() }}">{{ $purchase->status->label() }}</span></td>
                            <td>{{ $purchase->created_at->format('Y-m-d') }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

    <h2 class="h5 fw-semibold mb-3">Payouts</h2>
    @if ($payouts->isEmpty())
        <p class="text-body-secondary">No payouts recorded yet.</p>
    @else
        <div class="table-responsive">
            <table class="table align-middle bg-white">
                <thead>
                    <tr>
                        <th>Amount</th>
                        <th>Status</th>
                        <th>Requested</th>
                        <th>Paid</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($payouts as $payout)
                        <tr>
                            <td>{{ money($payout->amount) }}</td>
                            <td><span class="badge {{ $payout->status->badgeClass() }}">{{ $payout->status->label() }}</span></td>
                            <td>{{ $payout->created_at->format('Y-m-d') }}</td>
                            <td>{{ $payout->paid_at?->format('Y-m-d') ?? '—' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
@endsection
