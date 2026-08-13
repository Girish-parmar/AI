<?php

namespace App\Http\Controllers\Admin;

use App\Enums\PayoutStatus;
use App\Enums\PurchaseStatus;
use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Payout;
use App\Models\Purchase;
use App\Models\Script;
use App\Models\User;
use Illuminate\View\View;

class PayoutController extends Controller
{
    public function index(): View
    {
        $summaries = User::where('role', Role::Creator)->get()->map(function (User $creator) {
            $earned = $this->earnedFor($creator);
            $paidOut = $this->paidOutFor($creator);

            return [
                'creator' => $creator,
                'earned' => $earned,
                'paidOut' => $paidOut,
                'outstanding' => $earned - $paidOut,
            ];
        });

        $payouts = Payout::with('creator')->latest()->get();

        return view('admin.payouts.index', [
            'summaries' => $summaries,
            'payouts' => $payouts,
        ]);
    }

    private function earnedFor(User $creator): float
    {
        return (float) Purchase::where('status', PurchaseStatus::Completed)
            ->whereHasMorph('purchasable', [Course::class, Script::class], function ($query) use ($creator) {
                $query->where('creator_id', $creator->id);
            })
            ->sum('price');
    }

    private function paidOutFor(User $creator): float
    {
        return (float) Payout::where('creator_id', $creator->id)
            ->where('status', PayoutStatus::Paid)
            ->sum('amount');
    }
}
