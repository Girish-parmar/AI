<?php

namespace App\Http\Controllers\Accounts;

use App\Http\Controllers\Controller;
use App\Models\SubscriptionPlan;
use Illuminate\View\View;

class SubscriptionPlanController extends Controller
{
    public function index(): View
    {
        $plans = SubscriptionPlan::withCount('subscriptions')->orderBy('price')->get();

        return view('accounts.subscription-plans.index', ['plans' => $plans]);
    }
}
