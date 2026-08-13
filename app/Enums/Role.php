<?php

namespace App\Enums;

enum Role: string
{
    case SuperAdmin = 'superadmin';
    case Admin = 'admin';
    case Monitoring = 'monitoring';
    case Creator = 'creator';
    case User = 'user';
    case Accounts = 'accounts';

    public function label(): string
    {
        return match ($this) {
            self::SuperAdmin => 'Super Admin',
            self::Admin => 'Admin',
            self::Monitoring => 'Monitoring',
            self::Creator => 'Creator',
            self::User => 'User',
            self::Accounts => 'Accounts',
        };
    }

    /**
     * Named route for this role's dashboard, used to redirect users after login.
     */
    public function dashboardRoute(): string
    {
        return "{$this->value}.dashboard";
    }

    /**
     * Modules visible in this role's sidebar. 'available' modules carry a
     * 'route' and link to a built page; the rest are shown as upcoming so
     * the nav previews what's coming without dead links.
     *
     * @return array<int, array{label: string, available: bool, route?: string, note?: string}>
     */
    public function navItems(): array
    {
        return match ($this) {
            self::SuperAdmin => [
                ['label' => 'Dashboard', 'available' => true, 'route' => 'superadmin.dashboard'],
                ['label' => 'System Settings', 'available' => false],
                ['label' => 'Admin Accounts', 'available' => false],
                ['label' => 'Courses & Scripts', 'available' => true, 'route' => 'admin.courses.index'],
                ['label' => 'Approvals', 'available' => false],
                ['label' => 'Subscriptions', 'available' => false],
                ['label' => 'Purchases & Orders', 'available' => true, 'route' => 'accounts.transactions.index'],
                ['label' => 'User Management', 'available' => true, 'route' => 'admin.users.index'],
                ['label' => 'Audit Logs', 'available' => true, 'route' => 'monitoring.audit-logs.index'],
                ['label' => 'Legal & Compliance', 'available' => false],
                ['label' => 'Advertising', 'available' => true, 'route' => 'admin.advertisements.index'],
                ['label' => 'Finance & Payouts', 'available' => true, 'route' => 'accounts.payouts.index'],
            ],
            self::Admin => [
                ['label' => 'Dashboard', 'available' => true, 'route' => 'admin.dashboard'],
                ['label' => 'Courses & Scripts', 'available' => true, 'route' => 'admin.courses.index'],
                ['label' => 'Approvals', 'available' => false],
                ['label' => 'Advertising', 'available' => true, 'route' => 'admin.advertisements.index'],
                ['label' => 'Subscriptions', 'available' => false],
                ['label' => 'Purchases & Orders', 'available' => false],
                ['label' => 'User Management', 'available' => true, 'route' => 'admin.users.index'],
            ],
            self::Monitoring => [
                ['label' => 'Dashboard', 'available' => true, 'route' => 'monitoring.dashboard'],
                ['label' => 'Audit Logs', 'available' => true, 'route' => 'monitoring.audit-logs.index'],
                ['label' => 'Legal & Compliance', 'available' => false],
                ['label' => 'Advertising', 'available' => true, 'route' => 'monitoring.advertisements.index', 'note' => 'View only'],
                ['label' => 'Courses & Scripts', 'available' => true, 'route' => 'monitoring.courses.index', 'note' => 'View only'],
                ['label' => 'Subscriptions', 'available' => false, 'note' => 'View only'],
                ['label' => 'Purchases & Orders', 'available' => false, 'note' => 'View only'],
                ['label' => 'User Management', 'available' => false, 'note' => 'View only'],
            ],
            self::Creator => [
                ['label' => 'Dashboard', 'available' => true, 'route' => 'creator.dashboard'],
                ['label' => 'My Courses', 'available' => true, 'route' => 'creator.courses.index'],
                ['label' => 'My Scripts', 'available' => true, 'route' => 'creator.scripts.index'],
                ['label' => 'My Advertisements', 'available' => true, 'route' => 'creator.advertisements.index'],
                ['label' => 'Earnings', 'available' => true, 'route' => 'creator.earnings.index'],
            ],
            self::User => [
                ['label' => 'Dashboard', 'available' => true, 'route' => 'user.dashboard'],
                ['label' => 'Browse Courses', 'available' => true, 'route' => 'user.courses.index'],
                ['label' => 'Browse Scripts', 'available' => true, 'route' => 'user.scripts.index'],
                ['label' => 'My Subscriptions', 'available' => true, 'route' => 'user.subscription.show'],
                ['label' => 'My Purchases', 'available' => true, 'route' => 'user.purchases.index'],
                ['label' => 'Demo Content', 'available' => false],
            ],
            self::Accounts => [
                ['label' => 'Dashboard', 'available' => true, 'route' => 'accounts.dashboard'],
                ['label' => 'Invoices', 'available' => false],
                ['label' => 'Payouts', 'available' => true, 'route' => 'accounts.payouts.index'],
                ['label' => 'Revenue Reports', 'available' => false],
                ['label' => 'Purchases & Orders', 'available' => true, 'route' => 'accounts.transactions.index'],
            ],
        };
    }
}
