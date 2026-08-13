<?php

namespace App\Http\Controllers\Creator;

use App\Enums\PayoutStatus;
use App\Enums\PurchaseStatus;
use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Payout;
use App\Models\Purchase;
use App\Models\Script;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EarningsController extends Controller
{
    public function index(Request $request): View
    {
        $creatorId = $request->user()->id;

        $purchases = Purchase::with('purchasable')
            ->whereHasMorph('purchasable', [Course::class, Script::class], function ($query) use ($creatorId) {
                $query->where('creator_id', $creatorId);
            })
            ->latest()
            ->get();

        $totalEarned = $purchases->where('status', PurchaseStatus::Completed)->sum('price');

        $payouts = Payout::where('creator_id', $creatorId)->latest()->get();
        $totalPaidOut = $payouts->where('status', PayoutStatus::Paid)->sum('amount');

        return view('creator.earnings.index', [
            'purchases' => $purchases,
            'totalEarned' => $totalEarned,
            'payouts' => $payouts,
            'totalPaidOut' => $totalPaidOut,
            'outstanding' => $totalEarned - $totalPaidOut,
        ]);
    }
}
