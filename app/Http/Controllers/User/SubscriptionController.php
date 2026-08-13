<?php

namespace App\Http\Controllers\User;

use App\Enums\SubscriptionStatus;
use App\Enums\TransactionStatus;
use App\Http\Controllers\Controller;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SubscriptionController extends Controller
{
    public function plans(Request $request): View
    {
        $plans = SubscriptionPlan::where('is_active', true)->orderBy('price')->get();

        $activeSubscription = Subscription::where('user_id', $request->user()->id)
            ->active()
            ->with('plan')
            ->latest('id')
            ->first();

        return view('user.plans.index', ['plans' => $plans, 'activeSubscription' => $activeSubscription]);
    }

    public function subscribe(Request $request, SubscriptionPlan $plan): RedirectResponse
    {
        abort_unless($plan->is_active, 404);

        $hasOpenSubscription = Subscription::where('user_id', $request->user()->id)
            ->whereIn('status', [SubscriptionStatus::Pending, SubscriptionStatus::Active])
            ->exists();

        abort_if($hasOpenSubscription, 422, 'You already have a pending or active subscription.');

        if ($plan->hasTrial()) {
            $trialEndsAt = now()->addDays($plan->trial_days);

            Subscription::create([
                'user_id' => $request->user()->id,
                'subscription_plan_id' => $plan->id,
                'status' => SubscriptionStatus::Active,
                'starts_at' => now(),
                'trial_ends_at' => $trialEndsAt,
                'current_period_ends_at' => $trialEndsAt,
            ]);

            return redirect()->route('user.subscription.show')
                ->with('status', "Your {$plan->trial_days}-day free trial of \"{$plan->name}\" has started — nothing charged yet.");
        }

        $subscription = Subscription::create([
            'user_id' => $request->user()->id,
            'subscription_plan_id' => $plan->id,
            'status' => SubscriptionStatus::Pending,
            'starts_at' => now(),
        ]);

        $subscription->transactions()->create([
            'user_id' => $request->user()->id,
            'amount' => $plan->price,
            'gateway' => 'manual',
            'status' => TransactionStatus::Pending,
        ]);

        return redirect()->route('user.subscription.show')
            ->with('status', "Subscribed to \"{$plan->name}\" — pending payment confirmation.");
    }

    public function show(Request $request): View
    {
        $subscription = Subscription::where('user_id', $request->user()->id)
            ->with('plan')
            ->latest('id')
            ->first();

        return view('user.subscription.show', [
            'subscription' => $subscription,
            // Settled out of band, so an unpaid charge needs the
            // how-to-pay panel and its reference shown alongside it.
            'pendingTransaction' => $subscription
                ?->transactions()
                ->where('status', TransactionStatus::Pending)
                ->latest('id')
                ->first(),
        ]);
    }

    public function cancel(Request $request): RedirectResponse
    {
        $subscription = Subscription::where('user_id', $request->user()->id)
            ->whereIn('status', [SubscriptionStatus::Pending, SubscriptionStatus::Active])
            ->latest('id')
            ->firstOrFail();

        $subscription->update([
            'status' => SubscriptionStatus::Cancelled,
            'cancelled_at' => now(),
        ]);

        return redirect()->route('user.subscription.show')->with('status', 'Subscription cancelled.');
    }

    public function switchPreview(Request $request, SubscriptionPlan $plan): View
    {
        $subscription = $this->switchableSubscription($request, $plan);

        return view('user.subscription.switch', [
            'subscription' => $subscription,
            'newPlan' => $plan,
            'proration' => $subscription->prorate($plan),
        ]);
    }

    public function switch(Request $request, SubscriptionPlan $plan): RedirectResponse
    {
        $subscription = $this->switchableSubscription($request, $plan);
        $proration = $subscription->prorate($plan);
        $oldPlanName = $subscription->plan->name;

        $subscription->update(['subscription_plan_id' => $plan->id]);

        if ($proration['amount_due'] > 0) {
            $subscription->transactions()->create([
                'user_id' => $request->user()->id,
                'amount' => $proration['amount_due'],
                'gateway' => 'manual',
                'status' => TransactionStatus::Pending,
            ]);

            $amount = money($proration['amount_due']);

            return redirect()->route('user.subscription.show')
                ->with('status', "Switched from \"{$oldPlanName}\" to \"{$plan->name}\" — a prorated charge of {$amount} is pending payment confirmation.");
        }

        return redirect()->route('user.subscription.show')
            ->with('status', "Switched from \"{$oldPlanName}\" to \"{$plan->name}\" — no additional charge.");
    }

    private function switchableSubscription(Request $request, SubscriptionPlan $plan): Subscription
    {
        abort_unless($plan->is_active, 404);

        $subscription = Subscription::where('user_id', $request->user()->id)
            ->active()
            ->with('plan')
            ->latest('id')
            ->first();

        abort_unless($subscription, 404, "You don't have an active subscription to switch.");
        abort_if($subscription->subscription_plan_id === $plan->id, 422, 'You are already on this plan.');
        abort_if($subscription->onTrial(), 422, 'You can switch plans once your free trial ends.');
        abort_if(
            $subscription->transactions()->where('status', TransactionStatus::Pending)->exists(),
            422,
            'Resolve your pending payment before switching plans.'
        );

        return $subscription;
    }
}
