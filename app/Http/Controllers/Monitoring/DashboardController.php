<?php

namespace App\Http\Controllers\Monitoring;

use App\Enums\Role;
use App\Http\Controllers\Controller;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        return view('monitoring.dashboard', ['role' => Role::Monitoring]);
    }
}
