<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Purchase;
use App\Models\Subscription;
use App\Models\Transaction;
use Illuminate\Http\Request;
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

        return view('admin.transactions.index', ['transactions' => $transactions, 'statusFilter' => $status]);
    }
}
