<?php

namespace App\Http\Controllers\Accounts;

use App\Http\Controllers\Controller;
use App\Models\Script;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ScriptController extends Controller
{
    public function index(Request $request): View
    {
        $status = $request->string('status')->value();

        $scripts = Script::with('creator')
            ->when($status, fn ($query) => $query->where('status', $status))
            ->latest()
            ->get();

        return view('accounts.scripts.index', ['scripts' => $scripts, 'statusFilter' => $status]);
    }
}
