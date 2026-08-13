<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Advertisement;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        $advertisements = Advertisement::live()->inRandomOrder()->limit(3)->get();

        return view('user.dashboard', ['advertisements' => $advertisements]);
    }
}
