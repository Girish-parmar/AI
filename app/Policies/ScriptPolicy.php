<?php

namespace App\Policies;

use App\Enums\Role;
use App\Models\Script;
use App\Models\User;

class ScriptPolicy
{
    public function update(User $user, Script $script): bool
    {
        return $user->id === $script->creator_id
            || in_array($user->role, [Role::Admin, Role::SuperAdmin], true);
    }

    public function delete(User $user, Script $script): bool
    {
        return $this->update($user, $script);
    }
}
