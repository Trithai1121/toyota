<?php

namespace App\Http\Controllers;

use App\Models\Page;
use App\Services\PageContentPresenter;
use Illuminate\Contracts\View\View;

class StaticPageController extends Controller
{
    public function show(string $slug, PageContentPresenter $presenter): View
    {
        $page = Page::query()->publiclyAvailable()->where('slug', $slug)->firstOrFail();
        $description = $page->seo_description ?? '';
        $pageContent = $presenter->present($page);

        return view('pages.show', [
            ...$pageContent,
            'page' => $page,
            'content' => $page->body,
            'title' => $page->title,
            'description' => $description,
            'slug' => $slug,
        ]);
    }
}
