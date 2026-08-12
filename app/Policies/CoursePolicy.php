<?php

namespace App\Policies;

use App\Enums\Role;
use App\Models\Course;
use App\Models\User;

class CoursePolicy
{
    public function update(User $user, Course $course): bool
    {
        return $user->id === $course->creator_id
            || in_array($user->role, [Role::Admin, Role::SuperAdmin], true);
    }

    public function delete(User $user, Course $course): bool
    {
        return $this->update($user, $course);
    }
}
