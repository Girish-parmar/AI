<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\SubscriptionPlan\StoreSubscriptionPlanRequest;
use App\Http\Requests\SubscriptionPlan\UpdateSubscriptionPlanRequest;
use App\Models\SubscriptionPlan;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class SubscriptionPlanController extends Controller
{
    public function index(): View
    {
        $plans = SubscriptionPlan::withCount('subscriptions')->orderBy('price')->get();

        return view('admin.subscription-plans.index', ['plans' => $plans]);
    }

    public function create(): View
    {
        return view('admin.subscription-plans.create');
    }

    public function store(StoreSubscriptionPlanRequest $request): RedirectResponse
    {
        SubscriptionPlan::create([
            ...$request->validated(),
            'is_active' => $request->boolean('is_active'),
        ]);

        return redirect()->route('admin.subscription-plans.index')->with('status', 'Subscription plan created.');
    }

    public function edit(SubscriptionPlan $subscriptionPlan): View
    {
        return view('admin.subscription-plans.edit', ['plan' => $subscriptionPlan]);
    }

    public function update(UpdateSubscriptionPlanRequest $request, SubscriptionPlan $subscriptionPlan): RedirectResponse
    {
        $subscriptionPlan->update([
            ...$request->validated(),
            'is_active' => $request->boolean('is_active'),
        ]);

        return redirect()->route('admin.subscription-plans.index')->with('status', 'Subscription plan updated.');
    }

    public function destroy(SubscriptionPlan $subscriptionPlan): RedirectResponse
    {
        abort_if($subscriptionPlan->subscriptions()->exists(), 422, 'This plan has subscriptions on record and cannot be deleted — deactivate it instead.');

        $subscriptionPlan->delete();

        return redirect()->route('admin.subscription-plans.index')->with('status', 'Subscription plan deleted.');
    }
}
