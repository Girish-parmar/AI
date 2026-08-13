<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class RoleAccessTest extends TestCase
{
    use RefreshDatabase;

    public static function roleDashboards(): array
    {
        return [
            'superadmin' => [Role::SuperAdmin, 'superadmin.dashboard'],
            'admin' => [Role::Admin, 'admin.dashboard'],
            'monitoring' => [Role::Monitoring, 'monitoring.dashboard'],
            'creator' => [Role::Creator, 'creator.dashboard'],
            'user' => [Role::User, 'user.dashboard'],
            'accounts' => [Role::Accounts, 'accounts.dashboard'],
        ];
    }

    #[DataProvider('roleDashboards')]
    public function test_each_role_is_redirected_to_its_own_dashboard(Role $role, string $dashboardRoute): void
    {
        $user = User::factory()->create(['role' => $role]);

        $this->actingAs($user)
            ->get('/dashboard')
            ->assertRedirect(route($dashboardRoute));

        $this->actingAs($user)
            ->get(route($dashboardRoute))
            ->assertStatus(200);
    }

    #[DataProvider('roleDashboards')]
    public function test_a_role_cannot_reach_a_different_roles_exclusive_dashboard(Role $ownRole, string $ownDashboard): void
    {
        // creator/user/accounts dashboards are single-role only (no shared
        // group), so any other role must be blocked from all three.
        $exclusiveDashboards = [
            'creator.dashboard' => Role::Creator,
            'user.dashboard' => Role::User,
        ];

        $user = User::factory()->create(['role' => $ownRole]);

        foreach ($exclusiveDashboards as $route => $owningRole) {
            if ($owningRole === $ownRole) {
                continue;
            }

            $this->actingAs($user)
                ->get(route($route))
                ->assertForbidden();
        }
    }

    public function test_superadmin_shares_the_admin_dashboard(): void
    {
        $superadmin = User::factory()->create(['role' => Role::SuperAdmin]);

        $this->actingAs($superadmin)->get(route('admin.dashboard'))->assertStatus(200);
        $this->actingAs($superadmin)->get(route('admin.courses.index'))->assertStatus(200);
    }

    public function test_superadmin_shares_the_accounts_dashboard(): void
    {
        $superadmin = User::factory()->create(['role' => Role::SuperAdmin]);

        $this->actingAs($superadmin)->get(route('accounts.dashboard'))->assertStatus(200);
    }

    public function test_superadmin_does_not_share_the_monitoring_only_dashboard(): void
    {
        // Unlike admin/accounts, the monitoring dashboard/courses/scripts/
        // ads group is role:monitoring only — SuperAdmin was never added
        // to it, only to the separate audit-logs group. Regression guard
        // for that asymmetry.
        $superadmin = User::factory()->create(['role' => Role::SuperAdmin]);

        $this->actingAs($superadmin)->get(route('monitoring.dashboard'))->assertForbidden();
    }

    public function test_superadmin_can_reach_the_shared_audit_log_group(): void
    {
        $superadmin = User::factory()->create(['role' => Role::SuperAdmin]);

        $this->actingAs($superadmin)->get(route('monitoring.audit-logs.index'))->assertStatus(200);
    }

    public function test_plain_admin_cannot_reach_superadmin_only_routes(): void
    {
        $admin = User::factory()->create(['role' => Role::Admin]);

        $this->actingAs($admin)->get(route('superadmin.dashboard'))->assertForbidden();
    }

    public function test_plain_admin_is_excluded_from_accounts_and_monitoring_groups(): void
    {
        $admin = User::factory()->create(['role' => Role::Admin]);

        $this->actingAs($admin)->get(route('accounts.dashboard'))->assertForbidden();
        $this->actingAs($admin)->get(route('monitoring.dashboard'))->assertForbidden();
        $this->actingAs($admin)->get(route('monitoring.audit-logs.index'))->assertForbidden();
    }

    public function test_guests_are_redirected_to_login_for_protected_routes(): void
    {
        $this->get('/dashboard')->assertRedirect(route('login'));
        $this->get(route('admin.dashboard'))->assertRedirect(route('login'));
    }
}
