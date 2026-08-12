<?php

namespace App\Enums;

enum ContentStatus: string
{
    case Draft = 'draft';
    case Pending = 'pending';
    case Approved = 'approved';
    case Rejected = 'rejected';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::Pending => 'Pending review',
            self::Approved => 'Approved',
            self::Rejected => 'Rejected',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::Draft => 'text-bg-secondary',
            self::Pending => 'text-bg-warning',
            self::Approved => 'text-bg-success',
            self::Rejected => 'text-bg-danger',
        };
    }

    /**
     * Only draft or previously-rejected content can be (re)submitted for review.
     */
    public function canSubmit(): bool
    {
        return in_array($this, [self::Draft, self::Rejected], true);
    }
}
