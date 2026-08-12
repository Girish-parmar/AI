<?php

namespace App\Enums;

enum PurchaseStatus: string
{
    case Pending = 'pending';
    case Completed = 'completed';
    case Refunded = 'refunded';
    case Failed = 'failed';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pending',
            self::Completed => 'Completed',
            self::Refunded => 'Refunded',
            self::Failed => 'Failed',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::Pending => 'text-bg-warning',
            self::Completed => 'text-bg-success',
            self::Refunded => 'text-bg-secondary',
            self::Failed => 'text-bg-danger',
        };
    }
}
