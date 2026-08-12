<?php

namespace Database\Seeders;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     *
     * Creates one demo account per role for local development so every
     * dashboard can be exercised without registering manually. Password
     * for all demo accounts is "password" — local/dev use only.
     */
    public function run(): void
    {
        foreach (Role::cases() as $role) {
            User::factory()->create([
                'name' => $role->label().' Demo',
                'email' => "{$role->value}@example.com",
                'role' => $role,
            ]);
        }
    }
}
