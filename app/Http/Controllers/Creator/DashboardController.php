<?php

namespace App\Http\Controllers\Creator;

use App\Enums\Role;
use App\Http\Controllers\Controller;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        return view('creator.dashboard', ['role' => Role::Creator]);
    }
}
