<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ApprovalStatus;
use App\Enums\ContentStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Content\UpdateContentRequest;
use App\Models\Script;
use Illuminate\Http\RedirectResponse;
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

        return view('admin.scripts.index', [
            'scripts' => $scripts,
            'statusFilter' => $status,
        ]);
    }

    public function edit(Script $script): View
    {
        return view('admin.scripts.edit', ['script' => $script]);
    }

    public function update(UpdateContentRequest $request, Script $script): RedirectResponse
    {
        $script->update($request->validated());

        return redirect()->route('admin.scripts.index')->with('status', 'Script updated.');
    }

    public function destroy(Script $script): RedirectResponse
    {
        $script->delete();

        return redirect()->route('admin.scripts.index')->with('status', 'Script deleted.');
    }

    public function approve(Request $request, Script $script): RedirectResponse
    {
        abort_unless($script->status === ContentStatus::Pending, 422, 'Only pending scripts can be approved.');

        $approval = $script->approvals()->where('status', ApprovalStatus::Pending)->latest()->firstOrFail();
        $approval->update([
            'status' => ApprovalStatus::Approved,
            'reviewed_by' => $request->user()->id,
            'reviewed_at' => now(),
        ]);

        $script->update(['status' => ContentStatus::Approved]);

        return back()->with('status', "\"{$script->title}\" approved.");
    }

    public function reject(Request $request, Script $script): RedirectResponse
    {
        abort_unless($script->status === ContentStatus::Pending, 422, 'Only pending scripts can be rejected.');

        $validated = $request->validate([
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $approval = $script->approvals()->where('status', ApprovalStatus::Pending)->latest()->firstOrFail();
        $approval->update([
            'status' => ApprovalStatus::Rejected,
            'reviewed_by' => $request->user()->id,
            'reviewed_at' => now(),
            'notes' => $validated['notes'] ?? null,
        ]);

        $script->update(['status' => ContentStatus::Rejected]);

        return back()->with('status', "\"{$script->title}\" rejected.");
    }
}
