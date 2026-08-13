<?php

namespace App\Http\Controllers\Accounts;

use App\Enums\PayoutStatus;
use App\Enums\PurchaseStatus;
use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Payout;
use App\Models\Purchase;
use App\Models\Script;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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

        return view('accounts.payouts.index', [
            'summaries' => $summaries,
            'payouts' => $payouts,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'creator_id' => ['required', 'exists:users,id'],
            'amount' => ['required', 'numeric', 'min:0.01'],
        ]);

        $creator = User::findOrFail($validated['creator_id']);
        $outstanding = $this->earnedFor($creator) - $this->paidOutFor($creator);

        abort_if($validated['amount'] > $outstanding, 422, 'Payout amount cannot exceed the outstanding balance.');

        Payout::create([
            'creator_id' => $creator->id,
            'amount' => $validated['amount'],
            'status' => PayoutStatus::Pending,
        ]);

        return back()->with('status', 'Payout recorded as pending.');
    }

    public function pay(Payout $payout): RedirectResponse
    {
        abort_unless($payout->status === PayoutStatus::Pending, 422, 'Only pending payouts can be marked paid.');

        $payout->update(['status' => PayoutStatus::Paid, 'paid_at' => now()]);

        return back()->with('status', 'Payout marked as paid.');
    }

    public function fail(Payout $payout): RedirectResponse
    {
        abort_unless($payout->status === PayoutStatus::Pending, 422, 'Only pending payouts can be marked failed.');

        $payout->update(['status' => PayoutStatus::Failed]);

        return back()->with('status', 'Payout marked as failed.');
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
