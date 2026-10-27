<?php

namespace App\Http\Controllers;

use App\Models\Page;
use App\Models\PageBlock;
use App\Services\HtmlContentSanitizer;
use App\Services\PageContentPresenter;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Arr;

class PagePreviewController extends Controller
{
    public function __invoke(Page $page, PageContentPresenter $presenter, HtmlContentSanitizer $sanitizer): View
    {
        $draft = $page->draft;
        $draftBlocks = null;

        if ($draft) {
            $payload = $draft->payload;
            $page->forceFill(Arr::only($payload, [
                'title', 'slug', 'body', 'seo_title', 'seo_description', 'status', 'published_at',
            ]));
            $draftBlocks = new Collection(collect((array) ($payload['blocks'] ?? []))
                ->filter(fn (mixed $attributes): bool => is_array($attributes))
                ->map(function (array $attributes) use ($page, $sanitizer): PageBlock {
                    $block = new PageBlock;
                    $block->forceFill(Arr::except($attributes, ['page_id', 'created_at', 'updated_at']));
                    $block->setAttribute('page_id', $page->getKey());
                    $block->body = is_string($block->body) ? $sanitizer->sanitize($block->body) : null;

                    if (is_string($block->cta_url) && filled($block->cta_url) && ! preg_match('/^(https:\/\/|tel:|mailto:|\/)(?!\/)[^\s]*$/i', $block->cta_url)) {
                        $block->cta_url = null;
                    }

                    return $block;
                })
                ->filter(fn (PageBlock $block): bool => $block->is_enabled)
                ->sortBy('sort_order')
                ->values()
                ->all());
        }

        $content = $presenter->present($page, $draftBlocks);
        $title = $page->title;
        $description = $page->seo_description ?? '';
        $slug = $page->slug;
        $viewport = in_array(request('viewport'), ['desktop', 'tablet', 'mobile'], true) ? request('viewport') : 'desktop';

        return view($slug === 'home' ? 'home' : 'pages.show', [
            ...$content,
            'page' => $page,
            'title' => $title,
            'description' => $description,
            'content' => $page->body,
            'slug' => $slug,
            'preview' => true,
            'previewViewport' => $viewport,
            'vehicleLines' => collect(),
        ]);
    }
}
