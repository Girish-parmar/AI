<?php

namespace App\Http\Controllers\Monitoring;

use App\Http\Controllers\Controller;
use App\Models\DemoAccess;
use Illuminate\View\View;

class DemoAccessController extends Controller
{
    public function index(): View
    {
        $grants = DemoAccess::with(['user', 'grantedBy', 'resource'])->latest()->get();

        return view('monitoring.demo-access.index', ['grants' => $grants]);
    }
}
