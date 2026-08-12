<?php

namespace App\Http\Controllers\Creator;

use App\Enums\ApprovalStatus;
use App\Enums\ContentStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Content\StoreContentRequest;
use App\Http\Requests\Content\UpdateContentRequest;
use App\Models\Script;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ScriptController extends Controller
{
    public function index(Request $request): View
    {
        $scripts = Script::ownedBy($request->user()->id)->latest()->get();

        return view('creator.scripts.index', ['scripts' => $scripts]);
    }

    public function create(): View
    {
        return view('creator.scripts.create');
    }

    public function store(StoreContentRequest $request): RedirectResponse
    {
        $script = new Script($request->validated());
        $script->creator_id = $request->user()->id;
        $script->status = ContentStatus::Draft;
        $script->save();

        return redirect()->route('creator.scripts.index')->with('status', 'Script created as a draft.');
    }

    public function edit(Script $script): View
    {
        $this->authorize('update', $script);

        return view('creator.scripts.edit', ['script' => $script]);
    }

    public function update(UpdateContentRequest $request, Script $script): RedirectResponse
    {
        $this->authorize('update', $script);

        $script->update($request->validated());

        return redirect()->route('creator.scripts.index')->with('status', 'Script updated.');
    }

    public function destroy(Script $script): RedirectResponse
    {
        $this->authorize('delete', $script);

        $script->delete();

        return redirect()->route('creator.scripts.index')->with('status', 'Script deleted.');
    }

    public function submit(Request $request, Script $script): RedirectResponse
    {
        $this->authorize('update', $script);

        abort_unless($script->status->canSubmit(), 422, 'This script cannot be submitted from its current status.');

        $script->update(['status' => ContentStatus::Pending]);

        $script->approvals()->create([
            'requested_by' => $request->user()->id,
            'status' => ApprovalStatus::Pending,
        ]);

        return redirect()->route('creator.scripts.index')->with('status', 'Script submitted for review.');
    }
}
