<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ApprovalStatus;
use App\Enums\ContentStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Content\UpdateContentRequest;
use App\Models\Course;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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

        $approval = $course->approvals()->where('status', ApprovalStatus::Pending)->latest()->firstOrFail();
        $approval->update([
            'status' => ApprovalStatus::Approved,
            'reviewed_by' => $request->user()->id,
            'reviewed_at' => now(),
        ]);

        $course->update(['status' => ContentStatus::Approved]);

        return back()->with('status', "\"{$course->title}\" approved.");
    }

    public function reject(Request $request, Course $course): RedirectResponse
    {
        abort_unless($course->status === ContentStatus::Pending, 422, 'Only pending courses can be rejected.');

        $validated = $request->validate([
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $approval = $course->approvals()->where('status', ApprovalStatus::Pending)->latest()->firstOrFail();
        $approval->update([
            'status' => ApprovalStatus::Rejected,
            'reviewed_by' => $request->user()->id,
            'reviewed_at' => now(),
            'notes' => $validated['notes'] ?? null,
        ]);

        $course->update(['status' => ContentStatus::Rejected]);

        return back()->with('status', "\"{$course->title}\" rejected.");
    }
}
