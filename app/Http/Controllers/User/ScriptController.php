<?php

namespace App\Http\Controllers\User;

use App\Enums\ContentStatus;
use App\Http\Controllers\Controller;
use App\Models\Script;
use Illuminate\View\View;

class ScriptController extends Controller
{
    public function index(): View
    {
        $scripts = Script::approved()->with('creator')->latest()->get();

        return view('user.scripts.index', ['scripts' => $scripts]);
    }

    public function show(Script $script): View
    {
        abort_unless($script->status === ContentStatus::Approved, 404);

        return view('user.scripts.show', ['script' => $script->load('creator')]);
    }
}
