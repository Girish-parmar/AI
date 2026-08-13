<?php

namespace App\Http\Requests\SubscriptionPlan;

use App\Enums\BillingInterval;
use Illuminate\Validation\Rule;

class UpdateSubscriptionPlanRequest extends StoreSubscriptionPlanRequest
{
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', 'alpha_dash', Rule::unique('subscription_plans', 'slug')->ignore($this->route('subscription_plan'))],
            'description' => ['nullable', 'string', 'max:2000'],
            'price' => ['required', 'numeric', 'min:0', 'max:99999.99'],
            'billing_interval' => ['required', Rule::enum(BillingInterval::class)],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
