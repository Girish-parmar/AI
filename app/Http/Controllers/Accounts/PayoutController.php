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
use App\Notifications\PayoutStatusUpdated;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
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
            // Not just "any user" — the outstanding-balance math below
            // (earnedFor/paidOutFor) is meaningless for a non-Creator, and
            // without this a payout could be recorded against one anyway.
            'creator_id' => ['required', Rule::exists('users', 'id')->where('role', Role::Creator->value)],
            'amount' => ['required', 'numeric', 'min:0.01'],
        ]);

        $creator = User::findOrFail($validated['creator_id']);
        $outstanding = $this->earnedFor($creator) - $this->paidOutFor($creator);

        // Compared in integer cents, not raw floats — a payout decision is
        // exactly the kind of comparison where float representation error
        // must not be able to nudge the result either direction.
        $outstandingCents = (int) round($outstanding * 100);
        $amountCents = (int) round($validated['amount'] * 100);

        abort_if($amountCents > $outstandingCents, 422, 'Payout amount cannot exceed the outstanding balance.');

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

        $payout->creator->notify(new PayoutStatusUpdated(number_format($payout->amount, 2), PayoutStatus::Paid));

        return back()->with('status', 'Payout marked as paid.');
    }

    public function fail(Payout $payout): RedirectResponse
    {
        abort_unless($payout->status === PayoutStatus::Pending, 422, 'Only pending payouts can be marked failed.');

        $payout->update(['status' => PayoutStatus::Failed]);

        $payout->creator->notify(new PayoutStatusUpdated(number_format($payout->amount, 2), PayoutStatus::Failed));

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
