<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\MassPrunable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

#[Fillable(['user_id', 'action', 'entity_type', 'entity_id', 'ip_address', 'user_agent', 'meta'])]
class AuditLog extends Model
{
    use MassPrunable;

    const UPDATED_AT = null;

    /**
     * Entries past the retention window, deleted by the scheduled
     * model:prune run — audit_logs grows monotonically and the hosting
     * plan caps each database at a hard 3 GB (see config/hosting.php).
     */
    public function prunable(): Builder
    {
        return static::where('created_at', '<', now()->subDays(config('hosting.audit_log_retention_days')));
    }

    protected function casts(): array
    {
        return [
            'meta' => 'array',
            'created_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function entity(): MorphTo
    {
        return $this->morphTo();
    }
}
