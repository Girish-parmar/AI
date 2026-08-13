<?php

namespace App\Models;

use App\Enums\TransactionStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

#[Fillable(['user_id', 'amount', 'currency', 'gateway', 'gateway_reference', 'status'])]
class Transaction extends Model
{
    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'status' => TransactionStatus::class,
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function payable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * The code a buyer quotes when paying out of band, and the one
     * Accounts matches an incoming transfer against. Derived from the
     * primary key rather than stored, so it's stable for the life of the
     * transaction and can't collide or drift out of sync.
     */
    public function reference(): string
    {
        return config('payments.reference_prefix').'-'.str_pad((string) $this->id, 6, '0', STR_PAD_LEFT);
    }
}
