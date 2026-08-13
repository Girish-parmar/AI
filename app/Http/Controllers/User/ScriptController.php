<?php

namespace App\Http\Controllers\User;

use App\Enums\ContentStatus;
use App\Enums\PurchaseStatus;
use App\Enums\TransactionStatus;
use App\Http\Controllers\Controller;
use App\Models\DemoAccess;
use App\Models\Purchase;
use App\Models\Script;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ScriptController extends Controller
{
    public function index(): View
    {
        $scripts = Script::approved()->with('creator')->latest()->get();

        return view('user.scripts.index', ['scripts' => $scripts]);
    }

    public function show(Request $request, Script $script): View
    {
        abort_unless($script->status === ContentStatus::Approved, 404);

        $purchase = Purchase::where('user_id', $request->user()->id)
            ->where('purchasable_type', Script::class)
            ->where('purchasable_id', $script->id)
            ->latest()
            ->first();

        $demoAccess = DemoAccess::where('user_id', $request->user()->id)
            ->where('resource_type', Script::class)
            ->where('resource_id', $script->id)
            ->active()
            ->first();

        return view('user.scripts.show', [
            'script' => $script->load('creator'),
            'purchase' => $purchase,
            // A failed/refunded purchase is shown but isn't blocking — only
            // an open (pending) or already-completed one is, matching what
            // purchase() below actually enforces.
            'canPurchase' => ! $purchase || ! in_array($purchase->status, [PurchaseStatus::Pending, PurchaseStatus::Completed], true),
            'demoAccess' => $demoAccess,
        ]);
    }

    public function purchase(Request $request, Script $script): RedirectResponse
    {
        abort_unless($script->status === ContentStatus::Approved, 404);

        // The exists-check-then-create below is otherwise a classic race:
        // two near-simultaneous requests (a double-click) can both pass the
        // check before either has written a row. Locking the user's own
        // row for the transaction serializes their requests — the second
        // one re-runs the check only after the first has committed.
        DB::transaction(function () use ($request, $script) {
            User::whereKey($request->user()->id)->lockForUpdate()->first();

            $alreadyOwned = Purchase::where('user_id', $request->user()->id)
                ->where('purchasable_type', Script::class)
                ->where('purchasable_id', $script->id)
                ->whereIn('status', [PurchaseStatus::Pending, PurchaseStatus::Completed])
                ->exists();

            abort_if($alreadyOwned, 422, 'You already own or have a pending purchase for this script.');

            $purchase = Purchase::create([
                'user_id' => $request->user()->id,
                'purchasable_type' => Script::class,
                'purchasable_id' => $script->id,
                'price' => $script->price,
                'status' => PurchaseStatus::Pending,
            ]);

            $purchase->transactions()->create([
                'user_id' => $request->user()->id,
                'amount' => $script->price,
                'gateway' => 'manual',
                'status' => TransactionStatus::Pending,
            ]);
        });

        return redirect()->route('user.purchases.index')
            ->with('status', "Purchase submitted for \"{$script->title}\" — pending payment confirmation.");
    }
}
