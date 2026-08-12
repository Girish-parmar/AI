<?php

namespace App\Http\Controllers\User;

use App\Enums\ContentStatus;
use App\Http\Controllers\Controller;
use App\Models\Course;
use Illuminate\View\View;

class CourseController extends Controller
{
    public function index(): View
    {
        $courses = Course::approved()->with('creator')->latest()->get();

        return view('user.courses.index', ['courses' => $courses]);
    }

    public function show(Course $course): View
    {
        abort_unless($course->status === ContentStatus::Approved, 404);

        return view('user.courses.show', ['course' => $course->load('creator')]);
    }
}
