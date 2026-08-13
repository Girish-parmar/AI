@extends('layouts.dashboard')

@section('title', 'Earnings')

@section('content')
    <h1 class="h3 fw-bold mb-4">Earnings</h1>

    <div class="card border-0 shadow-sm mb-4" style="max-width: 20rem;">
        <div class="card-body">
            <div class="text-body-secondary small text-uppercase">Total earned (completed sales)</div>
            <div class="fs-3 fw-bold">${{ number_format($totalEarned, 2) }}</div>
        </div>
    </div>

    @if ($purchases->isEmpty())
        <p class="text-body-secondary">No one has purchased your content yet.</p>
    @else
        <div class="table-responsive">
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
                            <td>${{ number_format($purchase->price, 2) }}</td>
                            <td><span class="badge {{ $purchase->status->badgeClass() }}">{{ $purchase->status->label() }}</span></td>
                            <td>{{ $purchase->created_at->format('Y-m-d') }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
@endsection
