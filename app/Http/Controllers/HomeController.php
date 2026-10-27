<?php

namespace App\Http\Controllers;

use App\Models\Page;
use App\Services\PageContentPresenter;
use Illuminate\Contracts\View\View;

class HomeController extends Controller
{
    public function __invoke(PageContentPresenter $presenter): View
    {
        $page = Page::query()->publiclyAvailable()->where('slug', 'home')
            ->first();

        $page ??= new Page(['title' => config('app.name')]);
        $pageContent = $presenter->present($page);

        return view('home', $pageContent);
    }
}
