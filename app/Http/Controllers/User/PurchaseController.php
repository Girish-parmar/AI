<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Purchase;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PurchaseController extends Controller
{
    public function index(Request $request): View
    {
        $purchases = Purchase::where('user_id', $request->user()->id)
            ->with('purchasable')
            ->latest()
            ->get();

        return view('user.purchases.index', ['purchases' => $purchases]);
    }
}
