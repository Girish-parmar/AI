<?php

namespace App\Http\Controllers\User;

use App\Enums\Role;
use App\Http\Controllers\Controller;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        return view('user.dashboard', ['role' => Role::User]);
    }
}
