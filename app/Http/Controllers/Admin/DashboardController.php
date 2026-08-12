<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        // Shared by Admin and SuperAdmin — the dashboard layout's view
        // composer fills in `role` from the signed-in user automatically.
        return view('admin.dashboard');
    }
}
