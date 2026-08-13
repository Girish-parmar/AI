<?php

namespace App\Enums;

enum BillingInterval: string
{
    case Monthly = 'monthly';
    case Yearly = 'yearly';

    public function label(): string
    {
        return match ($this) {
            self::Monthly => 'Monthly',
            self::Yearly => 'Yearly',
        };
    }

    /**
     * Nominal length of one billing period, used to compute the end of a
     * cycle and to prorate a mid-cycle plan switch.
     */
    public function periodDays(): int
    {
        return match ($this) {
            self::Monthly => 30,
            self::Yearly => 365,
        };
    }
}
