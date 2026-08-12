<?php

namespace App\Http\Controllers\Accounts;

use App\Enums\Role;
use App\Http\Controllers\Controller;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        return view('accounts.dashboard', ['role' => Role::Accounts]);
    }
}
