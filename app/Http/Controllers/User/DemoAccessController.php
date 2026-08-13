<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\DemoAccess;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DemoAccessController extends Controller
{
    public function index(Request $request): View
    {
        $grants = DemoAccess::with('resource')
            ->where('user_id', $request->user()->id)
            ->latest('expires_at')
            ->get();

        return view('user.demo-access.index', ['grants' => $grants]);
    }
}
