<?php

/*
|--------------------------------------------------------------------------
| Payments
|--------------------------------------------------------------------------
|
| No payment gateway is integrated: orders are settled out of band (bank
| transfer, UPI, or similar) and confirmed by hand in the Accounts panel.
| That only works if buyers are actually told where to send the money and
| what to quote, so the instructions below are rendered to them at
| checkout and on every unpaid order.
|
| Everything here is env-driven — real bank details must never be
| committed. Leave the fields blank and the app degrades honestly: it
| tells buyers payment instructions are being finalised rather than
| rendering an empty panel.
|
*/

return [

    // Displayed with every amount across the app. Set both together —
    // the symbol is what buyers see, the code is what they need when
    // making an international transfer.
    'currency' => env('PAYMENT_CURRENCY', 'USD'),
    'currency_symbol' => env('PAYMENT_CURRENCY_SYMBOL', '$'),

    /*
    | Where buyers send money. Any field left blank is simply omitted from
    | the rendered instructions, so a UPI-only or bank-only setup both
    | look deliberate rather than half-filled.
    */
    'manual' => [
        'account_name' => env('PAYMENT_ACCOUNT_NAME'),
        'bank_name' => env('PAYMENT_BANK_NAME'),
        'account_number' => env('PAYMENT_ACCOUNT_NUMBER'),
        'ifsc' => env('PAYMENT_IFSC'),
        'swift' => env('PAYMENT_SWIFT'),
        'upi_id' => env('PAYMENT_UPI_ID'),

        // Free-text line for anything the fields above don't cover
        // (e.g. "Payments are confirmed within one business day").
        'notes' => env('PAYMENT_NOTES'),
    ],

    // Prefix for the reference buyers quote when paying, so Accounts can
    // match an incoming transfer to a specific transaction.
    'reference_prefix' => env('PAYMENT_REFERENCE_PREFIX', 'AWK'),

];
