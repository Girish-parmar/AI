<?php

/*
|--------------------------------------------------------------------------
| Hosting platform limits
|--------------------------------------------------------------------------
|
| The production target (Hostinger Business/"Unlimited" shared hosting)
| has fixed structural ceilings — most critically a hard 3 GB cap per
| database with no point-in-time recovery past it. These values drive
| the scheduled hosting:health check and the data-retention pruning
| that keeps the monotonic tables (audit_logs, notifications) from
| growing into that cap. All overridable per environment via .env.
|
*/

return [

    // Hard per-database size cap imposed by the hosting plan, in bytes.
    // 3 GB on Hostinger shared plans. hosting:health measures against it.
    'database_cap_bytes' => env('HOSTING_DATABASE_CAP_GB', 3) * 1024 * 1024 * 1024,

    // Fractions of the cap at which hosting:health starts warning and
    // (per the audit's escalation triggers) migration becomes mandatory.
    'database_warn_ratio' => (float) env('HOSTING_DATABASE_WARN_RATIO', 0.70),
    'database_critical_ratio' => (float) env('HOSTING_DATABASE_CRITICAL_RATIO', 0.85),

    // How long audit log entries are retained before model:prune deletes
    // them. The audit middleware writes a row for every state-changing
    // staff action, making audit_logs the fastest-growing table.
    'audit_log_retention_days' => (int) env('AUDIT_LOG_RETENTION_DAYS', 365),

    // Read notifications are pruned after this many days; unread ones
    // are kept twice as long before being pruned regardless.
    'notification_retention_days' => (int) env('NOTIFICATION_RETENTION_DAYS', 90),

];
