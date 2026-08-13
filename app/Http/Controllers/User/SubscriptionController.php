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
    public function plans(): View
    {
        $plans = SubscriptionPlan::where('is_active', true)->orderBy('price')->get();

        return view('user.plans.index', ['plans' => $plans]);
    }

    public function subscribe(Request $request, SubscriptionPlan $plan): RedirectResponse
    {
        abort_unless($plan->is_active, 404);

        $hasOpenSubscription = Subscription::where('user_id', $request->user()->id)
            ->whereIn('status', [SubscriptionStatus::Pending, SubscriptionStatus::Active])
            ->exists();

        abort_if($hasOpenSubscription, 422, 'You already have a pending or active subscription.');

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

        return view('user.subscription.show', ['subscription' => $subscription]);
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
}
