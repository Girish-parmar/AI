@extends('layouts.dashboard')

@section('title', 'Purchases & Orders')

@section('content')
    <h1 class="h3 fw-bold mb-4">Purchases &amp; Orders</h1>

    <form method="GET" class="mb-4 d-flex align-items-center gap-2">
        <label for="status" class="form-label mb-0 small text-body-secondary">Filter by status</label>
        <select name="status" id="status" class="form-select form-select-sm w-auto" onchange="this.form.submit()">
            <option value="">All</option>
            @foreach (['pending', 'succeeded', 'failed', 'refunded'] as $option)
                <option value="{{ $option }}" @selected($statusFilter === $option)>{{ ucfirst($option) }}</option>
            @endforeach
        </select>
    </form>

    @if ($transactions->isEmpty())
        <p class="text-body-secondary">No transactions match this filter.</p>
    @else
        <div class="table-responsive">
            <table class="table align-middle bg-white">
                <thead>
                    <tr>
                        <th>When</th>
                        <th>User</th>
                        <th>Item</th>
                        <th>Amount</th>
                        <th>Status</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($transactions as $transaction)
                        <tr>
                            <td class="text-nowrap">{{ $transaction->created_at->format('Y-m-d H:i') }}</td>
                            <td>{{ $transaction->user->name }}</td>
                            <td>
                                @php $payable = $transaction->payable; @endphp
                                @if ($payable instanceof \App\Models\Purchase)
                                    {{ $payable->purchasable->title ?? 'Deleted item' }}
                                    <span class="badge text-bg-light text-body-secondary">Purchase</span>
                                @elseif ($payable instanceof \App\Models\Subscription)
                                    {{ $payable->plan->name ?? 'Deleted plan' }}
                                    <span class="badge text-bg-light text-body-secondary">Subscription</span>
                                @else
                                    &mdash;
                                @endif
                            </td>
                            <td>${{ number_format($transaction->amount, 2) }}</td>
                            <td><span class="badge {{ $transaction->status->badgeClass() }}">{{ $transaction->status->label() }}</span></td>
                            <td class="text-end">
                                @if ($transaction->status->value === 'pending')
                                    <div class="d-inline-flex gap-2">
                                        <form method="POST" action="{{ route('accounts.transactions.succeed', $transaction) }}">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-success">Mark succeeded</button>
                                        </form>
                                        <form method="POST" action="{{ route('accounts.transactions.fail', $transaction) }}">
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

        {{ $transactions->links() }}
    @endif
@endsection
