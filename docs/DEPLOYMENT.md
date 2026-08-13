# Deployment Guide — Hostinger

Step-by-step guide to get this Laravel app running on Hostinger. Written for a
**Business** (or higher) shared hosting plan — that's the tier that includes
SSH access, Git, and Composer via hPanel. The base/Premium shared plan doesn't
have these, and this app won't deploy cleanly without them.

## Before you start

- [ ] A Hostinger **Business** (or Cloud/VPS) hosting plan, with a domain pointed at it
- [ ] SSH access enabled (hPanel → Advanced → SSH Access)
- [ ] **PHP 8.4** selected for the domain (hPanel → Advanced → PHP Configuration) — the
      app's locked dependencies (Laravel 13.x / Symfony 8.1.x) require it; PHP 8.3 will
      fail during `composer install` with an unsatisfiable-platform-requirement error
- [ ] This repo pushed to GitHub with the code you want live
- [ ] 15–20 minutes

---

## 1. Create the MySQL database

In hPanel → **Databases → MySQL Databases**:

1. Create a new database (e.g. `u123456789_platform`) and a database user with a strong password
2. Attach the user to the database with **all privileges**
3. Note the database name, username, password, and host (usually `localhost`) — you'll need these for `.env`

## 2. SSH in and clone the repo

```bash
ssh u123456789@your-domain.com -p 65002   # exact user/port shown in hPanel → SSH Access
```

**Clone the app outside `public_html`** — never put the whole Laravel app inside
`public_html` directly, or the internet can read your `.env`, `app/`, and
`vendor/` folders.

```bash
cd ~
git clone https://github.com/<your-username>/AI.git platform
cd platform
```

## 3. Point the domain at `public/`, not the app root

Hostinger Business plans let you set a **custom document root** per domain:

**hPanel → Websites → [your domain] → Manage → Advanced → Document Root** →
set it to `/home/u123456789/platform/public`

If your plan/panel doesn't expose that option, use the symlink fallback instead:

```bash
rm -rf ~/public_html          # back it up first if anything is in there
ln -s ~/platform/public ~/public_html
```

Either way, the goal is the same: the web server serves `platform/public/`
as the site root, with everything else (`app/`, `.env`, `vendor/`, etc.)
outside the web-accessible directory.

## 4. Install dependencies and configure `.env`

```bash
cd ~/platform
composer install --no-dev --optimize-autoloader
cp .env.example .env
php artisan key:generate
```

Edit `.env` (`nano .env`) and set:

```ini
APP_NAME="Platform"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-domain.com

DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=u123456789_platform
DB_USERNAME=u123456789_dbuser
DB_PASSWORD=the-strong-password-from-step-1

SESSION_SECURE_COOKIE=true
```

**Don't skip `APP_ENV=production` and `APP_DEBUG=false`.** Leaving debug mode
on in production leaks stack traces (including config values) to anyone who
hits an error page.

**`SESSION_SECURE_COOKIE=true`** stops the session cookie from ever being
sent over plain HTTP — safe to set once SSL is on (step 8), and it's unset
in `.env.example` specifically so local dev over `http://localhost` still
works.

If Hostinger puts a reverse proxy or load balancer in front of the app (some
plans do for SSL termination), also set `TRUSTED_PROXIES` — otherwise the
app may not correctly detect that the original request was HTTPS, which
breaks secure-cookie handling and can generate `http://` links instead of
`https://`. Leave it blank if you're unsure; add `TRUSTED_PROXIES=*` only if
you notice mixed-content issues or the app not recognizing HTTPS after going
live.

### Mail (required for password reset to actually work)

The app ships with `MAIL_MAILER=log` by default, which just writes emails to
a log file instead of sending them — fine for local dev, useless in
production, since the password-reset flow depends on real email delivery.
Hostinger gives you free email hosting on your domain; use it, or swap in
Mailgun/SES/Postmark if you prefer:

```ini
MAIL_MAILER=smtp
MAIL_HOST=smtp.hostinger.com
MAIL_PORT=465
MAIL_USERNAME=noreply@your-domain.com
MAIL_PASSWORD=that-mailbox-password
MAIL_ENCRYPTION=ssl
MAIL_FROM_ADDRESS=noreply@your-domain.com
MAIL_FROM_NAME="${APP_NAME}"
```

(Create the `noreply@your-domain.com` mailbox first, in hPanel → Emails.)

### Payment details (required — the site cannot collect money without them)

There is **no payment gateway integrated**. Orders are settled out of band:
a buyer places an order, is shown your bank/UPI details and a reference
code, transfers the money themselves, and someone on the Accounts side
confirms it by hand in **Purchases & Orders**.

That only works if these are filled in — buyers are shown exactly what you
put here:

```ini
PAYMENT_CURRENCY=INR
PAYMENT_CURRENCY_SYMBOL=₹
PAYMENT_ACCOUNT_NAME="Your Company Ltd"
PAYMENT_BANK_NAME="Your Bank"
PAYMENT_ACCOUNT_NUMBER=50100123456789
PAYMENT_IFSC=ABCD0001234
PAYMENT_UPI_ID=you@yourbank
PAYMENT_NOTES="Payments are confirmed within one business day."
```

**Quote any value containing spaces.** An unquoted space breaks dotenv
parsing and the whole site fails to boot — not just this feature.

Leave a field blank and it's simply omitted from what buyers see, so a
UPI-only or bank-only setup both render cleanly. Leave *all* of them blank
and buyers are told payment instructions are being finalised rather than
being shown an empty box — safe, but nobody can pay you.

`PAYMENT_CURRENCY_SYMBOL` drives every amount displayed across the site, so
set it to match the account you're actually collecting into.

Each pending transaction gets a reference (`AWK-000123`) shown to the buyer
and in the Accounts reconcile queue — that's how an incoming transfer gets
matched to an order. Change the `AWK` part with `PAYMENT_REFERENCE_PREFIX`.

## 5. Run migrations

```bash
php artisan migrate --force
```

This creates every table the app needs: `users`, `courses`, `scripts`,
`approvals`, `audit_logs`, `subscription_plans`, `subscriptions`,
`purchases`, `transactions`, plus Laravel's own `sessions`/`cache`/`jobs`
tables (the app uses the database for sessions and cache — see `.env`'s
`SESSION_DRIVER` / `CACHE_STORE`).

**Do not run `php artisan db:seed` or `migrate --seed` in production.** The
seeder creates one demo account *per role* — including SuperAdmin — all
with the password `password`. That's fine for local development, where the
whole point is to have working logins to click through, but it would leave
a wide-open SuperAdmin account on a live site.

## 6. Create your real SuperAdmin account

Since seeding is off-limits, create the first real account by hand via
`tinker`:

```bash
php artisan tinker
```

```php
\App\Models\User::create([
    'name' => 'Your Name',
    'email' => 'you@your-domain.com',
    'password' => \Illuminate\Support\Facades\Hash::make('a-genuinely-strong-password'),
    'role' => \App\Enums\Role::SuperAdmin,
]);
exit
```

Log in at `https://your-domain.com/login` with that email/password. From the
SuperAdmin dashboard you have full access, including **User Management**
(`/admin/users`) — use that to create the other role accounts (Admin,
Monitoring, Creator, Accounts, User) directly, rather than editing roles by
hand through `tinker`.

## 7. Set up the cron job

hPanel → **Advanced → Cron Jobs** → add a job that runs every minute:

```bash
* * * * * cd /home/u123456789/platform && php artisan schedule:run >> /dev/null 2>&1
```

This is what runs the nightly database backup (see **Backups** below), and
it's what any future scheduled task (expiring subscriptions, cleaning up old
sessions, etc.) will run through too — no additional cron entries needed as
the app grows.

## 8. Enable SSL

hPanel → **Security → SSL** → issue a free Let's Encrypt certificate for
your domain, and make sure "Force HTTPS" is on.

## 9. Verify it's live

- [ ] `https://your-domain.com/` loads the marketing home page
- [ ] `/register` and `/login` work
- [ ] Logging in as the SuperAdmin account from step 6 lands on `/superadmin/dashboard`
- [ ] `/forgot-password` actually sends an email (check the inbox you configured in step 4)
- [ ] No stack traces or debug info show up on a deliberately broken URL (confirms `APP_DEBUG=false` took effect)

---

## Deploying future updates

Once a PR is merged to `main`, ship it with:

```bash
cd ~/platform
git pull origin main
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

The three `*:cache` commands aren't required, but they're a meaningful
speed-up on shared hosting and take a few seconds — worth doing on every
deploy, not just the first one. If something breaks after caching, `php
artisan optimize:clear` clears all of them at once.

## Backups

`php artisan backup:database` dumps the database (gzipped) to
`storage/app/backups/` and prunes anything older than 14 days (`--keep=N`
to change that). It's already scheduled to run nightly at 02:30 through the
cron job from step 7 — no extra setup needed once that cron entry is in
place.

A few things worth knowing:

- **This backs up the database only, not `.env`.** That's deliberate —
  `.env` holds live credentials (DB password, mail password, `APP_KEY`), and
  writing it into an automated backup folder next to the app is one more
  place it could leak from. Back up `.env` yourself, separately, somewhere
  secure (a password manager, an encrypted note) whenever you change it —
  it changes rarely enough that this doesn't need automating.
- **Local disk backups don't protect against losing the whole server.**
  Periodically download the backups directory (SFTP, or hPanel's File
  Manager) to somewhere off-server, or turn on Hostinger's own
  account-level backup feature if your plan includes one (hPanel →
  **Files → Backups**) as a second layer.
- **Test a restore before you need one.** `gunzip -c
  storage/app/backups/db-<timestamp>.sql.gz | mysql -u
  <user> -p <database>` restores a MySQL dump; do this once against a
  throwaway database to confirm it actually works, rather than finding out
  during an actual incident.

## Hosting limits & data retention

Hostinger's shared plans cap **each database at a hard 3 GB**, and the two
tables that only ever grow — `audit_logs` (one row per state-changing staff
action) and `notifications` — are what will eventually hit it. Three
scheduled tasks keep the app inside that ceiling, all running through the
same step-7 cron entry with no extra setup:

- **`model:prune`** (daily 03:00) deletes audit log entries older than the
  retention window — 365 days by default (`AUDIT_LOG_RETENTION_DAYS`).
- **`notifications:prune`** (daily 03:10) deletes read notifications older
  than 90 days (`NOTIFICATION_RETENTION_DAYS`) and unread ones older than
  twice that.
- **`hosting:health`** (weekly, Mondays 03:30) measures the database
  against the cap and emails/notifies every SuperAdmin when it crosses 70%
  (start archiving) or 85% (plan the move to a Cloud/VPS tier). The cap and
  thresholds are configurable via `HOSTING_DATABASE_CAP_GB`,
  `HOSTING_DATABASE_WARN_RATIO`, and `HOSTING_DATABASE_CRITICAL_RATIO` if
  the hosting plan ever changes.

You can run `php artisan hosting:health` by hand any time to see current
usage.

## Security hardening

Most of this is already built in and needs no action — listed here so you
know what's covered and what isn't:

- **Security response headers** (`X-Content-Type-Options`, `X-Frame-Options`,
  `Referrer-Policy`, `Permissions-Policy`, and `Strict-Transport-Security`
  once HTTPS is on) are added to every response automatically. No
  Content-Security-Policy is set — Bootstrap's bundled JS and the app's
  inline `onsubmit` confirms would need a carefully tuned policy to avoid
  breaking pages, and an untested CSP shipped blind is worse than none.
- **CSRF protection, bcrypt password hashing, and rate-limited auth routes**
  (register/login/password-reset each have their own throttle bucket) are
  all in place already.
- **Every database write goes through Eloquent** — no raw SQL anywhere in
  the app, so there's no hand-built-query injection surface to review.
- **`composer audit` runs in CI** on every push/PR to `main`, so a
  dependency with a known vulnerability fails the build rather than going
  unnoticed. Worth re-running (`composer audit`) right before a deploy too,
  since new advisories can appear between CI runs.
- **`SESSION_SECURE_COOKIE=true` and `TRUSTED_PROXIES`** — see step 4 above.

## Go-live checklist

Run through this right before (and right after) pointing the domain at the
live site:

**Environment & access**
- [ ] `APP_ENV=production`, `APP_DEBUG=false`
- [ ] `.env` is **not** inside `public_html`/the web-accessible directory (steps 2–3)
- [ ] `SESSION_SECURE_COOKIE=true` set (and `TRUSTED_PROXIES` if the host proxies requests)
- [ ] Real SuperAdmin account created via `tinker`, not the seeder — and `db:seed` was never run against this database
- [ ] Database user has strong, unique credentials (not reused elsewhere)
- [ ] SSL is on and HTTPS is enforced
- [ ] Mail is configured with real credentials — password reset silently does nothing useful without it
- [ ] Payment details (`PAYMENT_*`) filled in with your real bank/UPI account, values containing spaces quoted — buyers are shown these verbatim and cannot pay you without them
- [ ] `PAYMENT_CURRENCY_SYMBOL` matches the account you're collecting into
- [ ] Placed one test order end to end: buyer sees the reference, Accounts sees the same reference in Purchases & Orders, marking it succeeded grants access

**Backups & monitoring**
- [ ] Cron job from step 7 is active (`hPanel → Advanced → Cron Jobs`)
- [ ] `php artisan backup:database` run manually once to confirm it succeeds, and the resulting file was test-restored
- [ ] A plan exists for getting backups off-server (manual download, or Hostinger's own backup feature)

**Final checks**
- [ ] `composer audit` clean
- [ ] Everything in **9. Verify it's live** above passes
- [ ] No stack traces or debug info show up on a deliberately broken URL
