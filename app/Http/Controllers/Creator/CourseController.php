<?php

namespace App\Http\Controllers\Creator;

use App\Enums\ApprovalStatus;
use App\Enums\ContentStatus;
use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Http\Requests\Content\StoreContentRequest;
use App\Http\Requests\Content\UpdateContentRequest;
use App\Models\Course;
use App\Models\User;
use App\Notifications\ContentSubmittedForReview;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\View\View;

class CourseController extends Controller
{
    public function index(Request $request): View
    {
        $courses = Course::ownedBy($request->user()->id)->latest()->get();

        return view('creator.courses.index', ['courses' => $courses]);
    }

    public function create(): View
    {
        return view('creator.courses.create');
    }

    public function store(StoreContentRequest $request): RedirectResponse
    {
        $course = new Course($request->validated());
        $course->creator_id = $request->user()->id;
        $course->status = ContentStatus::Draft;
        $course->save();

        return redirect()->route('creator.courses.index')->with('status', 'Course created as a draft.');
    }

    public function edit(Course $course): View
    {
        $this->authorize('update', $course);

        return view('creator.courses.edit', ['course' => $course]);
    }

    public function update(UpdateContentRequest $request, Course $course): RedirectResponse
    {
        $this->authorize('update', $course);

        $course->update($request->validated());

        return redirect()->route('creator.courses.index')->with('status', 'Course updated.');
    }

    public function destroy(Course $course): RedirectResponse
    {
        $this->authorize('delete', $course);

        $course->delete();

        return redirect()->route('creator.courses.index')->with('status', 'Course deleted.');
    }

    public function submit(Request $request, Course $course): RedirectResponse
    {
        $this->authorize('update', $course);

        abort_unless($course->status->canSubmit(), 422, 'This course cannot be submitted from its current status.');

        // A course marked "pending" must always have a matching pending
        // approval record — the admin queue is built by querying that
        // record, so a partial write here would silently hide the course
        // from reviewers.
        DB::transaction(function () use ($request, $course) {
            $course->update(['status' => ContentStatus::Pending]);

            $course->approvals()->create([
                'requested_by' => $request->user()->id,
                'status' => ApprovalStatus::Pending,
            ]);
        });

        $reviewers = User::whereIn('role', [Role::Admin, Role::SuperAdmin])->get();
        Notification::send($reviewers, new ContentSubmittedForReview('course', $course->title, $request->user()->name));

        return redirect()->route('creator.courses.index')->with('status', 'Course submitted for review.');
    }
}
