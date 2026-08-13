<?php

namespace App\Policies;

use App\Enums\Role;
use App\Models\Advertisement;
use App\Models\User;

class AdvertisementPolicy
{
    public function update(User $user, Advertisement $advertisement): bool
    {
        return $user->id === $advertisement->created_by
            || in_array($user->role, [Role::Admin, Role::SuperAdmin], true);
    }

    public function delete(User $user, Advertisement $advertisement): bool
    {
        return $this->update($user, $advertisement);
    }
}
