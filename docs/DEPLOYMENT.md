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
```

**Don't skip `APP_ENV=production` and `APP_DEBUG=false`.** Leaving debug mode
on in production leaks stack traces (including config values) to anyone who
hits an error page.

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
SuperAdmin dashboard you have full access; other roles (Admin, Monitoring,
Creator, Accounts) can register normally at `/register` and then have their
`role` changed via the same `tinker` approach until an admin-accounts UI
exists (that's still on the roadmap — see `docs/PROJECT_PLAN.md`).

## 7. Set up the cron job

hPanel → **Advanced → Cron Jobs** → add a job that runs every minute:

```bash
* * * * * cd /home/u123456789/platform && php artisan schedule:run >> /dev/null 2>&1
```

Nothing is scheduled yet (`routes/console.php` is empty), so this is a
no-op today — but it's what future scheduled tasks (expiring subscriptions,
cleaning up old sessions, etc.) will run through, and it costs nothing to
have in place now rather than remembering to add it later.

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

## Security checklist before going live

- [ ] `APP_ENV=production`, `APP_DEBUG=false`
- [ ] `.env` is **not** inside `public_html`/the web-accessible directory (steps 2–3 above)
- [ ] Real SuperAdmin account created via `tinker`, not the seeder
- [ ] Database user has strong, unique credentials (not reused elsewhere)
- [ ] SSL is on and HTTPS is enforced
- [ ] Mail is configured with real credentials — password reset silently does nothing useful without it
