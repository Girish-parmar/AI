<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ApprovalStatus;
use App\Enums\ContentStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Content\UpdateContentRequest;
use App\Models\Script;
use App\Notifications\ContentReviewed;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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

        // The approval record and the script's own status must land or
        // fail together — a crash between the two writes would otherwise
        // leave an "approved" approval against a script still "pending".
        DB::transaction(function () use ($request, $script) {
            $approval = $script->approvals()->where('status', ApprovalStatus::Pending)->latest()->firstOrFail();
            $approval->update([
                'status' => ApprovalStatus::Approved,
                'reviewed_by' => $request->user()->id,
                'reviewed_at' => now(),
            ]);

            $script->update(['status' => ContentStatus::Approved]);
        });

        $script->creator->notify(new ContentReviewed('script', $script->title, $script->id, ApprovalStatus::Approved, null));

        return back()->with('status', "\"{$script->title}\" approved.");
    }

    public function reject(Request $request, Script $script): RedirectResponse
    {
        abort_unless($script->status === ContentStatus::Pending, 422, 'Only pending scripts can be rejected.');

        $validated = $request->validate([
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        DB::transaction(function () use ($request, $script, $validated) {
            $approval = $script->approvals()->where('status', ApprovalStatus::Pending)->latest()->firstOrFail();
            $approval->update([
                'status' => ApprovalStatus::Rejected,
                'reviewed_by' => $request->user()->id,
                'reviewed_at' => now(),
                'notes' => $validated['notes'] ?? null,
            ]);

            $script->update(['status' => ContentStatus::Rejected]);
        });

        $script->creator->notify(new ContentReviewed('script', $script->title, $script->id, ApprovalStatus::Rejected, $validated['notes'] ?? null));

        return back()->with('status', "\"{$script->title}\" rejected.");
    }
}
