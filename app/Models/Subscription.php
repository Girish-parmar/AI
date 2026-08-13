<?php

namespace App\Models;

use App\Enums\SubscriptionStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

#[Fillable(['user_id', 'subscription_plan_id', 'status', 'starts_at', 'trial_ends_at', 'current_period_ends_at', 'ends_at', 'cancelled_at'])]
class Subscription extends Model
{
    protected function casts(): array
    {
        return [
            'status' => SubscriptionStatus::class,
            'starts_at' => 'datetime',
            'trial_ends_at' => 'datetime',
            'current_period_ends_at' => 'datetime',
            'ends_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(SubscriptionPlan::class, 'subscription_plan_id');
    }

    public function transactions(): MorphMany
    {
        return $this->morphMany(Transaction::class, 'payable');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', SubscriptionStatus::Active);
    }

    public function onTrial(): bool
    {
        return $this->trial_ends_at !== null && $this->trial_ends_at->isFuture();
    }

    /**
     * Prorated cost of switching to $newPlan right now: credit for the
     * unused remainder of the current period on the current plan, charged
     * at the new plan's daily rate for that same remainder. The period end
     * itself doesn't move — only the plan (and its price) does.
     *
     * @return array{remaining_days: int, credit: float, charge: float, amount_due: float}
     */
    public function prorate(SubscriptionPlan $newPlan): array
    {
        $remainingDays = $this->current_period_ends_at?->isFuture()
            ? now()->diffInDays($this->current_period_ends_at)
            : 0;

        $credit = round((float) $this->plan->price * $remainingDays / $this->plan->billing_interval->periodDays(), 2);
        $charge = round((float) $newPlan->price * $remainingDays / $newPlan->billing_interval->periodDays(), 2);
        $amountDue = max(0.0, round($charge - $credit, 2));

        return [
            'remaining_days' => $remainingDays,
            'credit' => $credit,
            'charge' => $charge,
            'amount_due' => $amountDue,
        ];
    }
}
