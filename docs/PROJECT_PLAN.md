# Project Plan — Multi-Role Content & Course Marketplace Platform

Planning document for the platform, structured using the **5W1H** framework (What / Why / Who / When / Where / How).

## 1. WHAT — What is being built

A multi-role **learning/content marketplace platform**: creators publish courses and scripts, users browse/subscribe/purchase, admins manage approvals and operations, monitoring handles audit/legal/advertising oversight, and accounts handles finance. Built as a Laravel (PHP) + MySQL application, deployed to Hostinger Shared Business hosting.

## 2. WHY — Why it's being built

- Centralize course/script sales and creator payouts under one system with proper approval gates (nothing goes live without admin sign-off).
- Separate concerns that are currently conflated in a single "admin" role: operational admin work vs. compliance/audit vs. finance vs. content creation vs. end-user consumption — each gets its own accountable role.
- Give the business auditability (Monitoring) and financial control (Accounts) independent of day-to-day Admin actions, which matters once money and legal exposure (course content, ads, subscriptions) are involved.

## 3. WHO — Roles & permission matrix

| Module | SuperAdmin | Admin | Monitoring | Creator | User | Accounts |
|---|---|---|---|---|---|---|
| System settings, admin accounts | Full | — | — | — | — | — |
| Courses / Scripts (CRUD) | Full | Full | View only | Create/manage own | Browse/consume | View only |
| Content approval workflow | Full | Full (approve/reject) | View only | Submit for approval | — | — |
| Subscription plans | Full | Full | View only | — | Subscribe | View/manage billing |
| Purchases / orders | Full | Full | View only | View own sales | Purchase | Full (reconcile) |
| User management | Full | Full (excl. SuperAdmin) | View only | Own profile | Own profile | View only |
| Audit logs | Full | View | Full (own domain) | — | — | View own txns |
| Legal / compliance docs | Full | View | Full | — | — | View |
| Advertising | Full | Approve | Full | Submit (if seller) | View | — |
| Finance: invoices, payouts, revenue | Full | View | View | View own earnings | View own invoices | Full |
| Demo access | Full | Grant | View | — | Use (if demo user) | — |

Monitoring explicitly gets **view rights equal to Admin across all modules**, plus exclusive manage rights on Audit/Legal/Advertising — it's a read-heavy oversight role, not an operational one.

### Role summary

1. **SuperAdmin** — super user, full control over the entire system, including managing Admin accounts and system settings.
2. **Admin** — day-to-day operations: courses, scripts, approvals, subscriptions, purchases, and user management.
3. **Monitoring** — audit, legal, and advertising oversight, with the same *view* rights as Admin across all modules, but write access limited to its own domain (audit/legal/advertising).
4. **Creator** — content creators and sellers who publish courses/scripts and view their own sales/earnings.
5. **User** — subscribers, purchasers, and demo users who consume content.
6. **Accounts** — finance management: invoices, payouts, revenue reconciliation.

## 4. WHEN — Phased roadmap

1. **Phase 1 — Foundation + thin slice across all roles:** Laravel install, full DB schema/migrations, roles & permissions (spatie/laravel-permission), auth (login/register/reset), one dashboard shell per role behind role middleware, basic Course/Script CRUD (Creator submits → Admin approves), purchase-flow stub, audit-log middleware capturing key actions, seeded SuperAdmin.
2. **Phase 2:** Full course/script content management, subscription plans, payment gateway integration, real checkout/purchase flow.
3. **Phase 3:** Accounts module (invoices, payouts, reconciliation reports); Monitoring module (audit dashboards, legal doc management, advertising approval).
4. **Phase 4:** Advertising end-to-end (submit → approve → display), demo-user flow, email notifications, UX polish.
5. **Phase 5:** Security hardening, backups, performance pass, production deploy/go-live on Hostinger.

## 5. WHERE — Hosting & environment

- **Hostinger Shared Business plan**: has SSH access + Git + Composer support via hPanel terminal (available at Business tier and above).
- Laravel app deployed outside `public_html`; domain's document root pointed at the app's `public/` folder via hPanel (Business plans allow a custom document root) — avoids exposing app internals.
- MySQL database provisioned via hPanel, credentials in `.env` (never committed).
- Cron: one hPanel cron job running `php artisan schedule:run` every minute (drives subscription-expiry checks, queued email, etc. — no long-running worker since shared hosting has no supervisor/daemon support, so the queue driver is `database`, processed via scheduled `queue:work --stop-when-empty`).
- Deploy flow: `git pull` on server (or Hostinger's Git integration) → `composer install --no-dev --optimize-autoloader` → `php artisan migrate --force` → `php artisan config:cache`.
- SSL via Hostinger's free Let's Encrypt certificate, HTTPS enforced.

## 6. HOW — Architecture & implementation approach

**Stack:** Laravel (latest LTS), MySQL, Blade views + Bootstrap 5 (server-rendered, minimal JS — keeps it deployable on shared hosting without a JS build pipeline; can be swapped for Tailwind/Vite later if desired).

**RBAC:** `spatie/laravel-permission` package — 6 roles seeded (`superadmin, admin, monitoring, creator, user, accounts`), permissions defined per the matrix above, enforced via route middleware + Blade `@can` directives + Policy classes per model.

**Core DB schema (initial):**

- `users` (id, name, email, password, role, status, timestamps)
- `courses`, `scripts` (id, creator_id, title, description, price, status[pending/approved/rejected], category, timestamps)
- `approvals` (id, item_type, item_id, requested_by, reviewed_by, status, notes, timestamps) — generic approval-queue table used by Admin for courses/scripts/ads
- `subscription_plans`, `subscriptions` (id, user_id, plan_id, status, starts_at, ends_at)
- `orders`/`purchases`, `transactions` (payment gateway refs, amounts, status) — feeds Accounts
- `payouts` (creator_id, amount, status, processed_by, processed_at)
- `audit_logs` (user_id, action, entity_type, entity_id, ip_address, meta, timestamps) — written by middleware on key actions
- `advertisements` (title, banner_path, target_url, status, created_by, starts_at, ends_at)
- `legal_documents` (type, content, version, published_at)
- `demo_access` (user_id, resource_type, resource_id, expires_at)

**Folder structure:** standard Laravel layout, with controllers/views namespaced per role (`app/Http/Controllers/Admin/...`, `.../Creator/...`, etc.) and route groups per role prefix + middleware group.

**Security:** Laravel's built-in CSRF, bcrypt password hashing, form-request validation, file-upload MIME/size checks for course/script uploads, login rate-limiting, `audit_logs` middleware on all state-changing admin/monitoring/accounts actions, `.env` kept out of git and outside `public_html`.

## Open items / assumptions to confirm before Phase 1 coding starts

- Payment gateway choice (Razorpay, Stripe, PayPal, or an Indian-market-specific option) — not decided yet, doesn't block planning.
- Whether SuperAdmin is a single hardcoded account or supports multiple.
- Exact subscription billing cycle rules (monthly/annual, proration, trials) — deferred to Phase 2 design.
