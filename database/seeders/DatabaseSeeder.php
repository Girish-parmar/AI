<?php

namespace Database\Seeders;

use App\Enums\ApprovalStatus;
use App\Enums\ContentStatus;
use App\Enums\PayoutStatus;
use App\Enums\PurchaseStatus;
use App\Enums\Role;
use App\Enums\SubscriptionStatus;
use App\Enums\TransactionStatus;
use App\Models\Advertisement;
use App\Models\AuditLog;
use App\Models\Course;
use App\Models\DemoAccess;
use App\Models\LegalDocument;
use App\Models\Payout;
use App\Models\Purchase;
use App\Models\Script;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     *
     * Creates one demo account per role (password "password" for all of
     * them) so every dashboard can be exercised without registering
     * manually, a handful of hand-curated records covering every status
     * a reviewer would want to see first, and then a much larger spread
     * of randomized data on top so the app feels like a populated
     * marketplace rather than an empty shell. Local/dev use only.
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

        Advertisement::factory()->create([
            'created_by' => $creator->id,
            'title' => 'Learn Guitar in 30 Days',
        ]);

        $this->submitted(
            Advertisement::factory()->pending()->create(['created_by' => $creator->id, 'title' => '50% Off Laravel Course']),
            $creator
        );

        Advertisement::factory()->approved()->create([
            'created_by' => $creator->id,
            'title' => 'New: Automation Scripts',
        ]);

        LegalDocument::create([
            'type' => 'terms_of_service',
            'title' => 'Terms of Service',
            'content' => "These are the platform's terms of service.",
            'version' => '1.0',
            'created_by' => $users['monitoring']->id,
            'published_at' => now()->subMonth(),
        ]);

        LegalDocument::create([
            'type' => 'privacy_policy',
            'title' => 'Privacy Policy',
            'content' => 'Draft privacy policy, not yet published.',
            'version' => '0.1',
            'created_by' => $users['monitoring']->id,
            'published_at' => null,
        ]);

        DemoAccess::create([
            'user_id' => $subscriber->id,
            'resource_type' => Course::class,
            'resource_id' => $approvedCourse->id,
            'granted_by' => $users['admin']->id,
            'expires_at' => now()->addDays(7),
        ]);

        // --- Bulk data below: fills out every module with realistic
        // volume on top of the hand-curated scenarios above, so the app
        // reads as a populated marketplace instead of an empty shell. ---

        $extraCreators = $this->seedExtraUsers(Role::Creator, 5);
        $extraUsers = $this->seedExtraUsers(Role::User, 19);
        $this->seedExtraUsers(Role::Admin, 2);
        $this->seedExtraUsers(Role::Monitoring, 1);
        $this->seedExtraUsers(Role::Accounts, 1);

        $allCreators = $extraCreators->push($creator);
        $allSubscribers = $extraUsers->push($subscriber)->push($pendingSubscriber);

        $enterprisePlan = SubscriptionPlan::factory()->create(['name' => 'Enterprise Plan', 'slug' => 'enterprise', 'price' => 79.99]);
        $plans = collect([$basicPlan, $proPlan, $enterprisePlan]);

        [$approvedCourses, $approvedScripts] = $this->seedContent($allCreators, $users['admin']);
        $this->seedAdvertisements($allCreators);
        $this->seedSubscriptions($allSubscribers, $plans);
        $this->seedPurchases($allSubscribers, $approvedCourses->push($approvedCourse), $approvedScripts->push($approvedScript));
        $this->seedPayouts($allCreators);
        $this->seedDemoAccess($allSubscribers, $approvedCourses->concat($approvedScripts), $users['admin']);
        $this->seedLegalDocuments($users['monitoring']);
        $this->seedAuditLogs($users);
    }

    /**
     * @return Collection<int, User>
     */
    private function seedExtraUsers(Role $role, int $count): Collection
    {
        return User::factory()->count($count)->create(['role' => $role]);
    }

    /**
     * Creates a spread of courses and scripts per creator, weighted toward
     * approved (most content on a mature marketplace has already cleared
     * review) with a smaller pending/draft/rejected tail so the approval
     * queue and creator dashboards both have something to show.
     *
     * @param  Collection<int, User>  $creators
     * @return array{0: Collection<int, Course>, 1: Collection<int, Script>}
     */
    private function seedContent(Collection $creators, User $reviewer): array
    {
        $approvedCourses = collect();
        $approvedScripts = collect();

        foreach ($creators as $creator) {
            $approved = Course::factory()->count(3)->approved()->for($creator, 'creator')->create();
            $approvedCourses = $approvedCourses->merge($approved);

            $pending = Course::factory()->pending()->for($creator, 'creator')->create();
            $this->submitted($pending, $creator);

            Course::factory()->for($creator, 'creator')->create();

            if (fake()->boolean(30)) {
                $rejected = Course::factory()->rejected()->for($creator, 'creator')->create();
                $this->reviewed($rejected, $creator, $reviewer, ApprovalStatus::Rejected, fake()->sentence());
            }

            $approvedS = Script::factory()->count(2)->approved()->for($creator, 'creator')->create();
            $approvedScripts = $approvedScripts->merge($approvedS);

            $pendingS = Script::factory()->pending()->for($creator, 'creator')->create();
            $this->submitted($pendingS, $creator);

            Script::factory()->for($creator, 'creator')->create();
        }

        return [$approvedCourses, $approvedScripts];
    }

    /**
     * @param  Collection<int, User>  $creators
     */
    private function seedAdvertisements(Collection $creators): void
    {
        foreach ($creators as $creator) {
            Advertisement::factory()->approved()->create(['created_by' => $creator->id]);

            $pending = Advertisement::factory()->pending()->create(['created_by' => $creator->id]);
            $this->submitted($pending, $creator);

            if (fake()->boolean(40)) {
                Advertisement::factory()->create(['created_by' => $creator->id]);
            }
        }
    }

    /**
     * @param  Collection<int, User>  $subscribers
     * @param  Collection<int, SubscriptionPlan>  $plans
     */
    private function seedSubscriptions(Collection $subscribers, Collection $plans): void
    {
        // Roughly half the pool subscribes, weighted toward Active, so the
        // subscriber base looks real without every single account having one.
        foreach ($subscribers->random((int) ($subscribers->count() * 0.5)) as $user) {
            $status = fake()->randomElement([
                SubscriptionStatus::Active,
                SubscriptionStatus::Active,
                SubscriptionStatus::Active,
                SubscriptionStatus::Cancelled,
                SubscriptionStatus::Expired,
            ]);
            $plan = $plans->random();
            $startedAt = now()->subDays(fake()->numberBetween(1, 180));

            $subscription = Subscription::create([
                'user_id' => $user->id,
                'subscription_plan_id' => $plan->id,
                'status' => $status,
                'starts_at' => $startedAt,
                'cancelled_at' => $status === SubscriptionStatus::Cancelled ? $startedAt->copy()->addDays(fake()->numberBetween(1, 60)) : null,
            ]);

            $this->paid($user, $subscription, $plan->price);
        }
    }

    /**
     * @param  Collection<int, User>  $buyers
     * @param  Collection<int, Course>  $courses
     * @param  Collection<int, Script>  $scripts
     */
    private function seedPurchases(Collection $buyers, Collection $courses, Collection $scripts): void
    {
        $catalog = $courses->map(fn (Course $course) => ['type' => Course::class, 'model' => $course])
            ->concat($scripts->map(fn (Script $script) => ['type' => Script::class, 'model' => $script]));

        foreach ($buyers as $buyer) {
            foreach ($catalog->random(min($catalog->count(), fake()->numberBetween(1, 4))) as $item) {
                $status = fake()->randomElement([
                    PurchaseStatus::Completed,
                    PurchaseStatus::Completed,
                    PurchaseStatus::Completed,
                    PurchaseStatus::Pending,
                    PurchaseStatus::Failed,
                ]);

                $purchase = Purchase::create([
                    'user_id' => $buyer->id,
                    'purchasable_type' => $item['type'],
                    'purchasable_id' => $item['model']->id,
                    'price' => $item['model']->price,
                    'status' => $status,
                ]);

                match ($status) {
                    PurchaseStatus::Completed => $this->paid($buyer, $purchase, $purchase->price),
                    PurchaseStatus::Pending => $purchase->transactions()->create([
                        'user_id' => $buyer->id,
                        'amount' => $purchase->price,
                        'gateway' => 'manual',
                        'status' => TransactionStatus::Pending,
                    ]),
                    PurchaseStatus::Failed => $purchase->transactions()->create([
                        'user_id' => $buyer->id,
                        'amount' => $purchase->price,
                        'gateway' => 'manual',
                        'status' => TransactionStatus::Failed,
                    ]),
                    default => null,
                };
            }
        }
    }

    /**
     * @param  Collection<int, User>  $creators
     */
    private function seedPayouts(Collection $creators): void
    {
        foreach ($creators as $creator) {
            $earned = (float) Purchase::where('status', PurchaseStatus::Completed)
                ->where('purchasable_type', Course::class)
                ->whereIn('purchasable_id', Course::where('creator_id', $creator->id)->pluck('id'))
                ->sum('price')
                + (float) Purchase::where('status', PurchaseStatus::Completed)
                ->where('purchasable_type', Script::class)
                ->whereIn('purchasable_id', Script::where('creator_id', $creator->id)->pluck('id'))
                ->sum('price');

            if ($earned < 1) {
                continue;
            }

            $paidAmount = round($earned * fake()->randomFloat(2, 0.3, 0.7), 2);

            Payout::create([
                'creator_id' => $creator->id,
                'amount' => $paidAmount,
                'status' => PayoutStatus::Paid,
                'reference' => 'seed-payout-'.$creator->id.'-1',
                'paid_at' => now()->subDays(fake()->numberBetween(1, 30)),
            ]);

            if (fake()->boolean(40)) {
                Payout::create([
                    'creator_id' => $creator->id,
                    'amount' => round(($earned - $paidAmount) * 0.5, 2) ?: 1,
                    'status' => fake()->randomElement([PayoutStatus::Pending, PayoutStatus::Failed]),
                ]);
            }
        }
    }

    /**
     * @param  Collection<int, User>  $users
     * @param  Collection<int, Course|Script>  $resources
     */
    private function seedDemoAccess(Collection $users, Collection $resources, User $grantedBy): void
    {
        foreach ($users->random(min($users->count(), 8)) as $user) {
            $resource = $resources->random();
            $expiresInDays = fake()->numberBetween(-10, 14); // some already expired, most active

            DemoAccess::create([
                'user_id' => $user->id,
                'resource_type' => $resource::class,
                'resource_id' => $resource->id,
                'granted_by' => $grantedBy->id,
                'expires_at' => now()->addDays($expiresInDays),
            ]);
        }
    }

    private function seedLegalDocuments(User $author): void
    {
        LegalDocument::create([
            'type' => 'refund_policy',
            'title' => 'Refund Policy',
            'content' => 'Purchases are refundable within 14 days if the content has not been substantially accessed.',
            'version' => '1.0',
            'created_by' => $author->id,
            'published_at' => now()->subWeeks(2),
        ]);

        LegalDocument::create([
            'type' => 'cookie_policy',
            'title' => 'Cookie Policy',
            'content' => 'The platform uses only strictly necessary session cookies; no third-party tracking cookies are set.',
            'version' => '1.0',
            'created_by' => $author->id,
            'published_at' => now()->subWeeks(2),
        ]);
    }

    /**
     * Synthetic entries for the Monitoring audit log, backdated over the
     * last month, so that page isn't empty until real traffic generates
     * its own via the RecordAuditLog middleware.
     *
     * @param  Collection<string, User>  $users
     */
    private function seedAuditLogs(Collection $users): void
    {
        $actions = [
            'admin.courses.approve', 'admin.courses.reject', 'admin.scripts.approve',
            'admin.advertisements.approve', 'admin.users.update', 'accounts.transactions.succeed',
            'accounts.payouts.pay', 'admin.demo-access.store',
        ];

        $actors = $users->only(['admin', 'superadmin', 'accounts', 'monitoring'])->values();

        foreach (range(1, 30) as $i) {
            AuditLog::create([
                'user_id' => $actors->random()->id,
                'action' => fake()->randomElement($actions),
                'ip_address' => fake()->ipv4(),
                'user_agent' => fake()->userAgent(),
                'meta' => ['method' => 'POST', 'status' => 302],
                'created_at' => now()->subDays(fake()->numberBetween(0, 30))->subMinutes(fake()->numberBetween(0, 1440)),
            ]);
        }
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

    private function submitted(Course|Script|Advertisement $content, User $creator): void
    {
        $content->approvals()->create([
            'requested_by' => $creator->id,
            'status' => ApprovalStatus::Pending,
        ]);
    }

    private function reviewed(Course|Script|Advertisement $content, User $creator, User $reviewer, ApprovalStatus $status, ?string $notes): void
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
