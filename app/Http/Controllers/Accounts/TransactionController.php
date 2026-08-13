<?php

namespace App\Http\Controllers\Accounts;

use App\Enums\PurchaseStatus;
use App\Enums\SubscriptionStatus;
use App\Enums\TransactionStatus;
use App\Http\Controllers\Controller;
use App\Models\Purchase;
use App\Models\Subscription;
use App\Models\Transaction;
use App\Notifications\PurchaseStatusUpdated;
use App\Notifications\SubscriptionStatusUpdated;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class TransactionController extends Controller
{
    public function index(Request $request): View
    {
        $status = $request->string('status')->value();

        $transactions = Transaction::with(['user', 'payable' => function ($morphTo) {
            $morphTo->morphWith([
                Purchase::class => ['purchasable'],
                Subscription::class => ['plan'],
            ]);
        }])
            ->when($status, fn ($query) => $query->where('status', $status))
            ->latest()
            ->paginate(20);

        return view('accounts.transactions.index', ['transactions' => $transactions, 'statusFilter' => $status]);
    }

    public function succeed(Transaction $transaction): RedirectResponse
    {
        abort_unless($transaction->status === TransactionStatus::Pending, 422, 'Only pending transactions can be marked succeeded.');

        // Ledger (transaction) and the thing it pays for (purchase/
        // subscription) are two different tables — committed together so a
        // failure mid-way can never leave a "succeeded" transaction against
        // a purchase that's still "pending", or vice versa.
        $notify = DB::transaction(function () use ($transaction) {
            $transaction->update(['status' => TransactionStatus::Succeeded]);

            $payable = $transaction->payable;

            if ($payable instanceof Purchase) {
                $payable->update(['status' => PurchaseStatus::Completed]);

                return new PurchaseStatusUpdated($payable->purchasable->title, PurchaseStatus::Completed);
            }

            if ($payable instanceof Subscription) {
                // A subscription only has no current_period_ends_at before
                // its very first payment clears — a later succeeded
                // transaction (e.g. a prorated plan-switch charge) must not
                // push the period out again, since proration deliberately
                // keeps it unchanged.
                $isInitialActivation = $payable->current_period_ends_at === null;

                $payable->update([
                    'status' => SubscriptionStatus::Active,
                    ...($isInitialActivation
                        ? ['current_period_ends_at' => now()->addDays($payable->plan->billing_interval->periodDays())]
                        : []),
                ]);

                return $isInitialActivation
                    ? new SubscriptionStatusUpdated($payable->plan->name, SubscriptionStatus::Active)
                    : null;
            }

            return null;
        });

        $notify && $transaction->user->notify($notify);

        return back()->with('status', 'Transaction marked as succeeded.');
    }

    public function fail(Transaction $transaction): RedirectResponse
    {
        abort_unless($transaction->status === TransactionStatus::Pending, 422, 'Only pending transactions can be marked failed.');

        $notify = DB::transaction(function () use ($transaction) {
            $transaction->update(['status' => TransactionStatus::Failed]);

            $payable = $transaction->payable;

            if ($payable instanceof Purchase) {
                $payable->update(['status' => PurchaseStatus::Failed]);

                return new PurchaseStatusUpdated($payable->purchasable->title, PurchaseStatus::Failed);
            }

            if ($payable instanceof Subscription && $payable->status === SubscriptionStatus::Pending) {
                // Only the subscription's first (activating) payment
                // cancels it on failure. A later charge failing — e.g. a
                // prorated plan-switch — leaves an already-active
                // subscription alone; Accounts follows up manually.
                $payable->update(['status' => SubscriptionStatus::Cancelled, 'cancelled_at' => now()]);

                return new SubscriptionStatusUpdated($payable->plan->name, SubscriptionStatus::Cancelled);
            }

            return null;
        });

        $notify && $transaction->user->notify($notify);

        return back()->with('status', 'Transaction marked as failed.');
    }
}
