<?php

namespace App\Models;

use App\Enums\LegalDocumentType;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['type', 'title', 'content', 'version', 'created_by', 'published_at'])]
class LegalDocument extends Model
{
    use HasFactory;

    /**
     * Cache key for the footer's published-documents link list, which
     * would otherwise be queried on every page render. Invalidated on
     * any write so publish/unpublish/edit reflect immediately.
     */
    public const FOOTER_CACHE_KEY = 'legal-documents.footer-links';

    protected static function booted(): void
    {
        $forget = fn () => cache()->forget(self::FOOTER_CACHE_KEY);

        static::saved($forget);
        static::deleted($forget);
    }

    /**
     * The most recently published document per type, as plain arrays —
     * what the footer renders. Cached for an hour; the booted() hooks
     * above clear it on any change.
     *
     * @return array<int, array{type: string, title: string}>
     */
    public static function footerLinks(): array
    {
        return cache()->remember(self::FOOTER_CACHE_KEY, now()->addHour(), function () {
            return static::published()
                ->orderByDesc('published_at')
                ->get(['type', 'title'])
                ->unique('type')
                ->map(fn (self $document) => [
                    'type' => $document->type->value,
                    'title' => $document->title,
                ])
                ->values()
                ->all();
        });
    }

    protected function casts(): array
    {
        return [
            'type' => LegalDocumentType::class,
            'published_at' => 'datetime',
        ];
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->whereNotNull('published_at');
    }
}
