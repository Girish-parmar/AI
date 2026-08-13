<?php

namespace Tests\Feature\Admin;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_a_non_superadmin_account(): void
    {
        $admin = User::factory()->create(['role' => Role::Admin]);

        $response = $this->actingAs($admin)->post(route('admin.users.store'), [
            'name' => 'New Creator',
            'email' => 'new-creator@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => 'creator',
        ]);

        $response->assertRedirect(route('admin.users.index'));
        $this->assertDatabaseHas('users', ['email' => 'new-creator@example.com', 'role' => 'creator']);
    }

    public function test_admin_cannot_create_a_superadmin_account(): void
    {
        $admin = User::factory()->create(['role' => Role::Admin]);

        $response = $this->actingAs($admin)->post(route('admin.users.store'), [
            'name' => 'Sneaky',
            'email' => 'sneaky@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => 'superadmin',
        ]);

        $response->assertSessionHasErrors('role');
        $this->assertDatabaseMissing('users', ['email' => 'sneaky@example.com']);
    }

    public function test_superadmin_can_create_a_superadmin_account(): void
    {
        $superadmin = User::factory()->create(['role' => Role::SuperAdmin]);

        $this->actingAs($superadmin)->post(route('admin.users.store'), [
            'name' => 'Second SuperAdmin',
            'email' => 'second-super@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => 'superadmin',
        ])->assertRedirect(route('admin.users.index'));

        $this->assertDatabaseHas('users', ['email' => 'second-super@example.com', 'role' => 'superadmin']);
    }

    public function test_admin_cannot_edit_a_superadmin_account(): void
    {
        $admin = User::factory()->create(['role' => Role::Admin]);
        $superadmin = User::factory()->create(['role' => Role::SuperAdmin]);

        $this->actingAs($admin)->get(route('admin.users.edit', $superadmin))->assertForbidden();
        $this->actingAs($admin)->put(route('admin.users.update', $superadmin), [
            'name' => 'Renamed',
            'email' => $superadmin->email,
            'role' => 'superadmin',
        ])->assertForbidden();
    }

    public function test_admin_cannot_delete_a_superadmin_account(): void
    {
        $admin = User::factory()->create(['role' => Role::Admin]);
        $superadmin = User::factory()->create(['role' => Role::SuperAdmin]);

        $this->actingAs($admin)->delete(route('admin.users.destroy', $superadmin))->assertForbidden();
        $this->assertDatabaseHas('users', ['id' => $superadmin->id]);
    }

    public function test_superadmin_can_edit_another_superadmin_account(): void
    {
        $actor = User::factory()->create(['role' => Role::SuperAdmin]);
        $target = User::factory()->create(['role' => Role::SuperAdmin]);

        $this->actingAs($actor)->put(route('admin.users.update', $target), [
            'name' => 'Updated Name',
            'email' => $target->email,
            'role' => 'superadmin',
        ])->assertRedirect(route('admin.users.index'));

        $this->assertSame('Updated Name', $target->fresh()->name);
    }

    public function test_no_one_can_delete_their_own_account(): void
    {
        $admin = User::factory()->create(['role' => Role::Admin]);
        $superadmin = User::factory()->create(['role' => Role::SuperAdmin]);

        $this->actingAs($admin)->delete(route('admin.users.destroy', $admin))->assertStatus(422);
        $this->assertDatabaseHas('users', ['id' => $admin->id]);

        $this->actingAs($superadmin)->delete(route('admin.users.destroy', $superadmin))->assertStatus(422);
        $this->assertDatabaseHas('users', ['id' => $superadmin->id]);
    }

    public function test_admin_can_edit_and_delete_a_non_superadmin_account(): void
    {
        $admin = User::factory()->create(['role' => Role::Admin]);
        $creator = User::factory()->create(['role' => Role::Creator]);

        $this->actingAs($admin)->put(route('admin.users.update', $creator), [
            'name' => 'Renamed Creator',
            'email' => $creator->email,
            'role' => 'creator',
        ])->assertRedirect(route('admin.users.index'));
        $this->assertSame('Renamed Creator', $creator->fresh()->name);

        $this->actingAs($admin)->delete(route('admin.users.destroy', $creator))->assertRedirect(route('admin.users.index'));
        $this->assertDatabaseMissing('users', ['id' => $creator->id]);
    }

    public function test_only_admin_and_superadmin_can_reach_user_management(): void
    {
        foreach ([Role::Creator, Role::User, Role::Monitoring, Role::Accounts] as $role) {
            $actor = User::factory()->create(['role' => $role]);

            $this->actingAs($actor)->get(route('admin.users.index'))->assertForbidden();
        }
    }
}
