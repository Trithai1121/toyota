<?php

namespace App\Models;

use App\Services\PagePublicationChecklist;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\DB;

#[Fillable(['title', 'slug', 'body', 'seo_title', 'seo_description', 'is_published', 'status', 'published_at'])]
class Page extends Model
{
    protected function casts(): array
    {
        return [
            'is_published' => 'boolean',
            'published_at' => 'datetime',
        ];
    }

    public function blocks(): HasMany
    {
        return $this->hasMany(PageBlock::class)->orderBy('sort_order');
    }

    public function revisions(): HasMany
    {
        return $this->hasMany(PageRevision::class);
    }

    public function draft(): HasOne
    {
        return $this->hasOne(PageDraft::class);
    }

    public function createRevision(?int $userId = null, ?string $summary = null): PageRevision
    {
        return $this->revisions()->create([
            'user_id' => $userId,
            'summary' => $summary,
            'snapshot' => $this->revisionSnapshot(),
        ]);
    }

    public function revisionSnapshot(): array
    {
        $attributes = $this->fresh()?->only([
            'title', 'slug', 'body', 'seo_title', 'seo_description',
            'is_published', 'status', 'published_at',
        ]) ?? $this->only([
            'title', 'slug', 'body', 'seo_title', 'seo_description',
            'is_published', 'status', 'published_at',
        ]);

        return [
            'page' => $attributes,
            'blocks' => $this->blocks()->get()->map(fn (PageBlock $block): array => $block->only([
                'type', 'title', 'description', 'body', 'image_path', 'image_alt',
                'vehicle_line_id', 'vehicle_id',
                'background_color', 'alignment', 'spacing_top', 'spacing_bottom',
                'cta_label', 'cta_url', 'cta_style', 'show_desktop', 'show_mobile',
                'is_enabled', 'settings', 'sort_order',
            ]))->all(),
        ];
    }

    public function restoreRevision(PageRevision $revision): void
    {
        abort_unless($revision->page_id === $this->id, 404);
        $snapshot = $revision->snapshot;
        app(PagePublicationChecklist::class)->validate([
            ...$snapshot['page'],
            'blocks' => $snapshot['blocks'],
        ]);

        DB::transaction(function () use ($snapshot): void {
            $this->fill($snapshot['page']);
            $this->save();
            $this->blocks()->delete();
            $this->blocks()->createMany($snapshot['blocks']);
            $this->draft()->delete();
            $this->createRevision(auth()->id(), 'Khôi phục phiên bản');
        });
    }

    public function isPubliclyAvailable(): bool
    {
        return $this->status === 'published'
            || ($this->status === 'scheduled' && $this->published_at?->isPast());
    }

    public function scopePubliclyAvailable(Builder $query): Builder
    {
        return $query->where(function (Builder $query): void {
            $query->where('status', 'published')
                ->orWhere(fn (Builder $query): Builder => $query->where('status', 'scheduled')->whereNotNull('published_at')->where('published_at', '<=', now()));
        });
    }
}
