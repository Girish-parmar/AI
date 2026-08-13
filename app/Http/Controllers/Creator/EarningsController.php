<?php

namespace App\Http\Controllers\Creator;

use App\Enums\PurchaseStatus;
use App\Http\Controllers\Controller;
use App\Models\Course;
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

        return view('creator.earnings.index', [
            'purchases' => $purchases,
            'totalEarned' => $totalEarned,
        ]);
    }
}
