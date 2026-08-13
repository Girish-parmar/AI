<?php

namespace Database\Seeders;

use App\Enums\ApprovalStatus;
use App\Enums\ContentStatus;
use App\Enums\PayoutStatus;
use App\Enums\PurchaseStatus;
use App\Enums\Role;
use App\Enums\SubscriptionStatus;
use App\Enums\TransactionStatus;
use App\Models\Course;
use App\Models\Payout;
use App\Models\Purchase;
use App\Models\Script;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
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
     * dashboard can be exercised without registering manually (password
     * for all demo accounts is "password"), plus a spread of courses and
     * scripts across every status so approval/browse flows have data to
     * show. Local/dev use only.
     */
    public function run(): void
    {
        $users = collect(Role::cases())->mapWithKeys(function (Role $role) {
            $user = User::factory()->create([
                'name' => $role->label().' Demo',
                'email' => "{$role->value}@example.com",
                'role' => $role,
            ]);

            return [$role->value => $user];
        });

        $creator = $users['creator'];

        Course::factory()->create([
            'creator_id' => $creator->id,
            'title' => 'Intro to Guitar',
            'status' => ContentStatus::Draft,
        ]);

        $this->submitted(
            Course::factory()->pending()->create(['creator_id' => $creator->id, 'title' => 'Advanced Photography']),
            $creator
        );

        $approvedCourse = Course::factory()->approved()->create(['creator_id' => $creator->id, 'title' => 'Laravel for Beginners']);

        $this->reviewed(
            Course::factory()->rejected()->create(['creator_id' => $creator->id, 'title' => 'Get Rich Quick']),
            $creator,
            $users['admin'],
            ApprovalStatus::Rejected,
            'Content does not meet our quality guidelines.'
        );

        Script::factory()->create([
            'creator_id' => $creator->id,
            'title' => 'CSV Cleanup Utility',
            'status' => ContentStatus::Draft,
        ]);

        $this->submitted(
            Script::factory()->pending()->create(['creator_id' => $creator->id, 'title' => 'Auto-Deploy Pipeline']),
            $creator
        );

        $approvedScript = Script::factory()->approved()->create(['creator_id' => $creator->id, 'title' => 'Log Parser']);

        $basicPlan = SubscriptionPlan::factory()->create(['name' => 'Basic Plan', 'slug' => 'basic', 'price' => 9.99]);
        $proPlan = SubscriptionPlan::factory()->create(['name' => 'Pro Plan', 'slug' => 'pro', 'price' => 29.99]);
        SubscriptionPlan::factory()->inactive()->create(['name' => 'Legacy Plan', 'slug' => 'legacy', 'price' => 4.99]);

        $subscriber = $users['user'];

        $subscription = Subscription::create([
            'user_id' => $subscriber->id,
            'subscription_plan_id' => $proPlan->id,
            'status' => SubscriptionStatus::Active,
            'starts_at' => now()->subDays(10),
        ]);

        $this->paid($subscriber, $subscription, $proPlan->price);

        $purchase = Purchase::create([
            'user_id' => $subscriber->id,
            'purchasable_type' => Course::class,
            'purchasable_id' => $approvedCourse->id,
            'price' => $approvedCourse->price,
            'status' => PurchaseStatus::Completed,
        ]);

        $this->paid($subscriber, $purchase, $purchase->price);

        // A second, still-pending purchase for the same subscriber, and a
        // separate user with a pending subscription, so the Accounts
        // reconciliation queue has something to review out of the box.
        $pendingPurchase = Purchase::create([
            'user_id' => $subscriber->id,
            'purchasable_type' => Script::class,
            'purchasable_id' => $approvedScript->id,
            'price' => $approvedScript->price,
            'status' => PurchaseStatus::Pending,
        ]);
        $pendingPurchase->transactions()->create([
            'user_id' => $subscriber->id,
            'amount' => $pendingPurchase->price,
            'gateway' => 'manual',
            'status' => TransactionStatus::Pending,
        ]);

        $pendingSubscriber = User::factory()->create([
            'name' => 'Pending Subscriber',
            'email' => 'pending-subscriber@example.com',
            'role' => Role::User,
        ]);
        $pendingSubscription = Subscription::create([
            'user_id' => $pendingSubscriber->id,
            'subscription_plan_id' => $basicPlan->id,
            'status' => SubscriptionStatus::Pending,
            'starts_at' => now(),
        ]);
        $pendingSubscription->transactions()->create([
            'user_id' => $pendingSubscriber->id,
            'amount' => $basicPlan->price,
            'gateway' => 'manual',
            'status' => TransactionStatus::Pending,
        ]);

        // A partial payout already paid, leaving an outstanding balance so
        // the Accounts payouts page has something to reconcile.
        Payout::create([
            'creator_id' => $creator->id,
            'amount' => round($approvedCourse->price / 2, 2),
            'status' => PayoutStatus::Paid,
            'reference' => 'seed-payout-1',
            'paid_at' => now()->subDays(3),
        ]);
    }

    private function paid(User $user, Subscription|Purchase $payable, string $amount): void
    {
        $payable->transactions()->create([
            'user_id' => $user->id,
            'amount' => $amount,
            'gateway' => 'manual',
            'gateway_reference' => 'seed-'.strtolower(class_basename($payable)).'-'.$payable->id,
            'status' => TransactionStatus::Succeeded,
        ]);
    }

    private function submitted(Course|Script $content, User $creator): void
    {
        $content->approvals()->create([
            'requested_by' => $creator->id,
            'status' => ApprovalStatus::Pending,
        ]);
    }

    private function reviewed(Course|Script $content, User $creator, User $reviewer, ApprovalStatus $status, ?string $notes): void
    {
        $content->approvals()->create([
            'requested_by' => $creator->id,
            'reviewed_by' => $reviewer->id,
            'status' => $status,
            'notes' => $notes,
            'reviewed_at' => now(),
        ]);
    }
}
