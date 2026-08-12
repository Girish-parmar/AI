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
     * Modules visible in this role's sidebar. 'available' modules link to a
     * built page; the rest are shown as upcoming so the nav previews what's
     * coming without dead links.
     *
     * @return array<int, array{label: string, available: bool, note?: string}>
     */
    public function navItems(): array
    {
        return match ($this) {
            self::SuperAdmin => [
                ['label' => 'Dashboard', 'available' => true],
                ['label' => 'System Settings', 'available' => false],
                ['label' => 'Admin Accounts', 'available' => false],
                ['label' => 'Courses & Scripts', 'available' => false],
                ['label' => 'Approvals', 'available' => false],
                ['label' => 'Subscriptions', 'available' => false],
                ['label' => 'Purchases & Orders', 'available' => false],
                ['label' => 'User Management', 'available' => false],
                ['label' => 'Audit Logs', 'available' => false],
                ['label' => 'Legal & Compliance', 'available' => false],
                ['label' => 'Advertising', 'available' => false],
                ['label' => 'Finance & Payouts', 'available' => false],
            ],
            self::Admin => [
                ['label' => 'Dashboard', 'available' => true],
                ['label' => 'Courses & Scripts', 'available' => false],
                ['label' => 'Approvals', 'available' => false],
                ['label' => 'Subscriptions', 'available' => false],
                ['label' => 'Purchases & Orders', 'available' => false],
                ['label' => 'User Management', 'available' => false],
            ],
            self::Monitoring => [
                ['label' => 'Dashboard', 'available' => true],
                ['label' => 'Audit Logs', 'available' => false],
                ['label' => 'Legal & Compliance', 'available' => false],
                ['label' => 'Advertising', 'available' => false],
                ['label' => 'Courses & Scripts', 'available' => false, 'note' => 'View only'],
                ['label' => 'Subscriptions', 'available' => false, 'note' => 'View only'],
                ['label' => 'Purchases & Orders', 'available' => false, 'note' => 'View only'],
                ['label' => 'User Management', 'available' => false, 'note' => 'View only'],
            ],
            self::Creator => [
                ['label' => 'Dashboard', 'available' => true],
                ['label' => 'My Courses', 'available' => false],
                ['label' => 'My Scripts', 'available' => false],
                ['label' => 'Submit for Approval', 'available' => false],
                ['label' => 'Earnings', 'available' => false],
            ],
            self::User => [
                ['label' => 'Dashboard', 'available' => true],
                ['label' => 'Browse Courses', 'available' => false],
                ['label' => 'My Subscriptions', 'available' => false],
                ['label' => 'My Purchases', 'available' => false],
                ['label' => 'Demo Content', 'available' => false],
            ],
            self::Accounts => [
                ['label' => 'Dashboard', 'available' => true],
                ['label' => 'Invoices', 'available' => false],
                ['label' => 'Payouts', 'available' => false],
                ['label' => 'Revenue Reports', 'available' => false],
                ['label' => 'Purchases & Orders', 'available' => false, 'note' => 'View only'],
            ],
        };
    }
}
