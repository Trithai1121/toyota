<?php

namespace App\Services;

use App\Models\Page;
use App\Models\Post;
use App\Models\Promotion;
use App\Models\Vehicle;
use Illuminate\Database\Eloquent\Collection;

class PageContentPresenter
{
    /**
     * @return array{page: Page, blocks: Collection, vehicles: Collection, posts: Collection, promotions: Collection}
     */
    public function present(Page $page, ?Collection $draftBlocks = null): array
    {
        $blocks = $draftBlocks ?? $page->blocks()
            ->with(['vehicle.specs', 'vehicleLine'])
            ->where('is_enabled', true)
            ->orderBy('sort_order')
            ->get();
        $blocks->loadMissing(['vehicle.specs', 'vehicleLine']);

        $types = $blocks->pluck('type')->unique();
        $vehicles = $types->contains(fn (string $type): bool => in_array($type, ['vehicle_grid', 'lead_form'], true))
            ? Vehicle::query()
                ->where('is_published', true)
                ->where(fn ($query) => $query->whereNull('published_at')->orWhere('published_at', '<=', now()))
                ->whereHas('line', fn ($query) => $query->where('is_published', true))
                ->with(['line', 'images' => fn ($query) => $query->orderBy('sort_order'), 'variants' => fn ($query) => $query->where('is_published', true)->orderBy('sort_order')])
                ->orderBy('name')
                ->limit(50)
                ->get()
            : new Collection;

        $posts = $types->contains('recent_posts')
            ? Post::query()->readyToPublish()
                ->where('status', 'published')
                ->where(fn ($query) => $query->whereNull('published_at')->orWhere('published_at', '<=', now()))
                ->latest('published_at')
                ->limit(6)
                ->get()
            : new Collection;

        $promotions = $types->contains('promotion_strip')
            ? Promotion::query()
                ->where('is_published', true)
                ->where(fn ($query) => $query->whereNull('starts_at')->orWhereDate('starts_at', '<=', today()))
                ->where(fn ($query) => $query->whereNull('ends_at')->orWhereDate('ends_at', '>=', today()))
                ->latest('starts_at')
                ->limit(6)
                ->get()
            : new Collection;

        return compact('page', 'blocks', 'vehicles', 'posts', 'promotions');
    }
}
