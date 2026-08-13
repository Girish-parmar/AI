<?php

namespace App\Http\Controllers\Monitoring;

use App\Enums\ApprovalStatus;
use App\Enums\ContentStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Advertisement\UpdateAdvertisementRequest;
use App\Models\Advertisement;
use App\Notifications\ContentReviewed;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdvertisementController extends Controller
{
    public function index(Request $request): View
    {
        $status = $request->string('status')->value();

        $advertisements = Advertisement::with('creator')
            ->when($status, fn ($query) => $query->where('status', $status))
            ->latest()
            ->get();

        return view('monitoring.advertisements.index', [
            'advertisements' => $advertisements,
            'statusFilter' => $status,
        ]);
    }

    public function edit(Advertisement $advertisement): View
    {
        return view('monitoring.advertisements.edit', ['advertisement' => $advertisement]);
    }

    public function update(UpdateAdvertisementRequest $request, Advertisement $advertisement): RedirectResponse
    {
        $advertisement->update($request->validated());

        return redirect()->route('monitoring.advertisements.index')->with('status', 'Advertisement updated.');
    }

    public function destroy(Advertisement $advertisement): RedirectResponse
    {
        $advertisement->delete();

        return redirect()->route('monitoring.advertisements.index')->with('status', 'Advertisement deleted.');
    }

    public function approve(Request $request, Advertisement $advertisement): RedirectResponse
    {
        abort_unless($advertisement->status === ContentStatus::Pending, 422, 'Only pending advertisements can be approved.');

        $approval = $advertisement->approvals()->where('status', ApprovalStatus::Pending)->latest()->firstOrFail();
        $approval->update([
            'status' => ApprovalStatus::Approved,
            'reviewed_by' => $request->user()->id,
            'reviewed_at' => now(),
        ]);

        $advertisement->update(['status' => ContentStatus::Approved]);

        $advertisement->creator->notify(new ContentReviewed('advertisement', $advertisement->title, $advertisement->id, ApprovalStatus::Approved, null));

        return back()->with('status', "\"{$advertisement->title}\" approved.");
    }

    public function reject(Request $request, Advertisement $advertisement): RedirectResponse
    {
        abort_unless($advertisement->status === ContentStatus::Pending, 422, 'Only pending advertisements can be rejected.');

        $validated = $request->validate([
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $approval = $advertisement->approvals()->where('status', ApprovalStatus::Pending)->latest()->firstOrFail();
        $approval->update([
            'status' => ApprovalStatus::Rejected,
            'reviewed_by' => $request->user()->id,
            'reviewed_at' => now(),
            'notes' => $validated['notes'] ?? null,
        ]);

        $advertisement->update(['status' => ContentStatus::Rejected]);

        $advertisement->creator->notify(new ContentReviewed('advertisement', $advertisement->title, $advertisement->id, ApprovalStatus::Rejected, $validated['notes'] ?? null));

        return back()->with('status', "\"{$advertisement->title}\" rejected.");
    }
}
