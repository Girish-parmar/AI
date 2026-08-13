<?php

namespace App\Http\Controllers\Monitoring;

use App\Http\Controllers\Controller;
use App\Models\Advertisement;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdvertisementController extends Controller
{
    public function index(Request $request): View
    {
        $status = $request->string('status')->value();

        $advertisements = Advertisement::with('creator')
            ->when($status, fn ($query) => $query->where('status', $status))
            ->latest()
            ->get();

        return view('monitoring.advertisements.index', [
            'advertisements' => $advertisements,
            'statusFilter' => $status,
        ]);
    }
}
