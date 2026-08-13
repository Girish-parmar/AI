<?php

namespace App\Http\Controllers\User;

use App\Enums\PurchaseStatus;
use App\Http\Controllers\Controller;
use App\Models\Purchase;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PurchaseController extends Controller
{
    public function index(Request $request): View
    {
        $purchases = Purchase::where('user_id', $request->user()->id)
            ->with(['purchasable', 'transactions'])
            ->latest()
            ->get();

        return view('user.purchases.index', [
            'purchases' => $purchases,
            // Orders are settled out of band, so anything still awaiting
            // payment needs the how-to-pay panel shown alongside it.
            'hasUnpaid' => $purchases->contains(fn (Purchase $purchase) => $purchase->status === PurchaseStatus::Pending),
        ]);
    }
}
