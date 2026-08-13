<?php

if (! function_exists('money')) {
    /**
     * Format an amount for display in the configured currency. Every
     * amount shown anywhere in the app goes through here, so switching
     * currency is a config change rather than an edit to 40-odd views.
     */
    function money(float|int|string|null $amount): string
    {
        return config('payments.currency_symbol').number_format((float) $amount, 2);
    }
}
