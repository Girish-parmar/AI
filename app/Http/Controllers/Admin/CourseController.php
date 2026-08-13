<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ApprovalStatus;
use App\Enums\ContentStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Content\UpdateContentRequest;
use App\Models\Course;
use App\Notifications\ContentReviewed;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class CourseController extends Controller
{
    public function index(Request $request): View
    {
        $status = $request->string('status')->value();

        $courses = Course::with('creator')
            ->when($status, fn ($query) => $query->where('status', $status))
            ->latest()
            ->get();

        return view('admin.courses.index', [
            'courses' => $courses,
            'statusFilter' => $status,
        ]);
    }

    public function edit(Course $course): View
    {
        return view('admin.courses.edit', ['course' => $course]);
    }

    public function update(UpdateContentRequest $request, Course $course): RedirectResponse
    {
        $course->update($request->validated());

        return redirect()->route('admin.courses.index')->with('status', 'Course updated.');
    }

    public function destroy(Course $course): RedirectResponse
    {
        $course->delete();

        return redirect()->route('admin.courses.index')->with('status', 'Course deleted.');
    }

    public function approve(Request $request, Course $course): RedirectResponse
    {
        abort_unless($course->status === ContentStatus::Pending, 422, 'Only pending courses can be approved.');

        // The approval record and the course's own status must land or
        // fail together — a crash between the two writes would otherwise
        // leave an "approved" approval against a course still "pending".
        DB::transaction(function () use ($request, $course) {
            $approval = $course->approvals()->where('status', ApprovalStatus::Pending)->latest()->firstOrFail();
            $approval->update([
                'status' => ApprovalStatus::Approved,
                'reviewed_by' => $request->user()->id,
                'reviewed_at' => now(),
            ]);

            $course->update(['status' => ContentStatus::Approved]);
        });

        $course->creator->notify(new ContentReviewed('course', $course->title, $course->id, ApprovalStatus::Approved, null));

        return back()->with('status', "\"{$course->title}\" approved.");
    }

    public function reject(Request $request, Course $course): RedirectResponse
    {
        abort_unless($course->status === ContentStatus::Pending, 422, 'Only pending courses can be rejected.');

        $validated = $request->validate([
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        DB::transaction(function () use ($request, $course, $validated) {
            $approval = $course->approvals()->where('status', ApprovalStatus::Pending)->latest()->firstOrFail();
            $approval->update([
                'status' => ApprovalStatus::Rejected,
                'reviewed_by' => $request->user()->id,
                'reviewed_at' => now(),
                'notes' => $validated['notes'] ?? null,
            ]);

            $course->update(['status' => ContentStatus::Rejected]);
        });

        $course->creator->notify(new ContentReviewed('course', $course->title, $course->id, ApprovalStatus::Rejected, $validated['notes'] ?? null));

        return back()->with('status', "\"{$course->title}\" rejected.");
    }
}
