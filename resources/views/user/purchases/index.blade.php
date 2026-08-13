@extends('layouts.dashboard')

@section('title', 'My Purchases')

@section('content')
    <h1 class="h3 fw-bold mb-4">My Purchases</h1>

    @if ($hasUnpaid)
        @include('partials.payment-instructions')
    @endif

    @if ($purchases->isEmpty())
        <p class="text-body-secondary">
            You haven't bought anything yet. Browse
            <a href="{{ route('user.courses.index') }}">courses</a> or
            <a href="{{ route('user.scripts.index') }}">scripts</a> to get started.
        </p>
    @else
        <div class="table-responsive">
            <table class="table align-middle bg-white">
                <thead>
                    <tr>
                        <th>Item</th>
                        <th>Type</th>
                        <th>Price</th>
                        <th>Status</th>
                        <th>Payment reference</th>
                        <th>Purchased</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($purchases as $purchase)
                        <tr>
                            <td>{{ $purchase->purchasable->title ?? 'Deleted item' }}</td>
                            <td>{{ class_basename($purchase->purchasable_type) }}</td>
                            <td>{{ money($purchase->price) }}</td>
                            <td><span class="badge {{ $purchase->status->badgeClass() }}">{{ $purchase->status->label() }}</span></td>
                            <td>
                                @php $pendingTransaction = $purchase->transactions->firstWhere('status', \App\Enums\TransactionStatus::Pending); @endphp
                                @if ($pendingTransaction)
                                    <span class="badge text-bg-dark font-monospace">{{ $pendingTransaction->reference() }}</span>
                                @else
                                    <span class="text-body-secondary">&mdash;</span>
                                @endif
                            </td>
                            <td>{{ $purchase->created_at->format('Y-m-d') }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
@endsection
