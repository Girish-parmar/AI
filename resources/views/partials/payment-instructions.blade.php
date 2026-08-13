@php
    // $transaction is optional — the panel also renders standalone (e.g.
    // on the purchases list) where there's no single reference to quote.
    $transaction ??= null;
    $manual = config('payments.manual');
    $fields = array_filter([
        'Account name' => $manual['account_name'],
        'Bank' => $manual['bank_name'],
        'Account number' => $manual['account_number'],
        'IFSC' => $manual['ifsc'],
        'SWIFT' => $manual['swift'],
        'UPI ID' => $manual['upi_id'],
    ]);
@endphp

<div class="card border-warning-subtle bg-warning-subtle mb-4">
    <div class="card-body">
        <h2 class="h6 fw-bold mb-2">How to pay</h2>

        @if (empty($fields))
            <p class="mb-0 small">
                Payment details are being finalised. Your order is saved and held &mdash;
                we'll be in touch with payment instructions shortly.
            </p>
        @else
            <p class="small mb-3">
                Transfer the amount shown using the details below. Your order stays
                pending until we confirm the payment.
            </p>

            <dl class="row small mb-0">
                @foreach ($fields as $label => $value)
                    <dt class="col-5 col-sm-4 fw-normal text-body-secondary">{{ $label }}</dt>
                    <dd class="col-7 col-sm-8 fw-semibold">{{ $value }}</dd>
                @endforeach

                @if ($transaction)
                    <dt class="col-5 col-sm-4 fw-normal text-body-secondary">Reference</dt>
                    <dd class="col-7 col-sm-8">
                        <span class="badge text-bg-dark font-monospace">{{ $transaction->reference() }}</span>
                    </dd>
                    <dt class="col-5 col-sm-4 fw-normal text-body-secondary">Amount</dt>
                    <dd class="col-7 col-sm-8 fw-semibold">{{ money($transaction->amount) }}</dd>
                @endif
            </dl>

            @if ($transaction)
                <p class="small mt-3 mb-0">
                    <strong>Quote the reference</strong> with your transfer so we can match it to this order.
                </p>
            @endif

            @if ($manual['notes'])
                <p class="small text-body-secondary mt-2 mb-0">{{ $manual['notes'] }}</p>
            @endif
        @endif
    </div>
</div>
