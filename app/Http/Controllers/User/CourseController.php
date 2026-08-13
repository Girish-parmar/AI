<?php

namespace App\Http\Controllers\User;

use App\Enums\ContentStatus;
use App\Enums\PurchaseStatus;
use App\Enums\TransactionStatus;
use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\DemoAccess;
use App\Models\Purchase;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class CourseController extends Controller
{
    public function index(): View
    {
        $courses = Course::approved()->with('creator')->latest()->get();

        return view('user.courses.index', ['courses' => $courses]);
    }

    public function show(Request $request, Course $course): View
    {
        abort_unless($course->status === ContentStatus::Approved, 404);

        $purchase = Purchase::where('user_id', $request->user()->id)
            ->where('purchasable_type', Course::class)
            ->where('purchasable_id', $course->id)
            ->latest()
            ->first();

        $demoAccess = DemoAccess::where('user_id', $request->user()->id)
            ->where('resource_type', Course::class)
            ->where('resource_id', $course->id)
            ->active()
            ->first();

        return view('user.courses.show', [
            'course' => $course->load('creator'),
            'purchase' => $purchase,
            // A failed/refunded purchase is shown but isn't blocking — only
            // an open (pending) or already-completed one is, matching what
            // purchase() below actually enforces.
            'canPurchase' => ! $purchase || ! in_array($purchase->status, [PurchaseStatus::Pending, PurchaseStatus::Completed], true),
            'demoAccess' => $demoAccess,
        ]);
    }

    public function purchase(Request $request, Course $course): RedirectResponse
    {
        abort_unless($course->status === ContentStatus::Approved, 404);

        // The exists-check-then-create below is otherwise a classic race:
        // two near-simultaneous requests (a double-click) can both pass the
        // check before either has written a row. Locking the user's own
        // row for the transaction serializes their requests — the second
        // one re-runs the check only after the first has committed.
        DB::transaction(function () use ($request, $course) {
            User::whereKey($request->user()->id)->lockForUpdate()->first();

            $alreadyOwned = Purchase::where('user_id', $request->user()->id)
                ->where('purchasable_type', Course::class)
                ->where('purchasable_id', $course->id)
                ->whereIn('status', [PurchaseStatus::Pending, PurchaseStatus::Completed])
                ->exists();

            abort_if($alreadyOwned, 422, 'You already own or have a pending purchase for this course.');

            $purchase = Purchase::create([
                'user_id' => $request->user()->id,
                'purchasable_type' => Course::class,
                'purchasable_id' => $course->id,
                'price' => $course->price,
                'status' => PurchaseStatus::Pending,
            ]);

            $purchase->transactions()->create([
                'user_id' => $request->user()->id,
                'amount' => $course->price,
                'gateway' => 'manual',
                'status' => TransactionStatus::Pending,
            ]);
        });

        return redirect()->route('user.purchases.index')
            ->with('status', "Purchase submitted for \"{$course->title}\" — pending payment confirmation.");
    }
}
