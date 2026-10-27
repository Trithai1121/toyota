<?php

namespace App\Http\Controllers;

use App\Models\CampaignLandingPage;
use App\Models\Post;
use App\Models\Vehicle;
use Illuminate\Http\Response;

class SitemapController extends Controller
{
    public function __invoke(): Response
    {
        $urls = [
            route('home'),
            route('vehicles.index'),
            route('posts.index'),
            route('calculators.installment'),
            route('calculators.on-road'),
            route('pages.maintenance'),
            route('pages.parts'),
            route('pages.about'),
            route('pages.contact'),
            route('pages.privacy'),
        ];

        foreach (Vehicle::query()
            ->where('is_published', true)
            ->where(fn ($query) => $query->whereNull('published_at')->orWhere('published_at', '<=', now()))
            ->whereHas('line', fn ($query) => $query->where('is_published', true))
            ->pluck('slug') as $slug) {
            $urls[] = route('vehicles.show', $slug);
        }

        foreach (Post::query()->readyToPublish()
            ->where('status', 'published')
            ->where(fn ($query) => $query->whereNull('published_at')->orWhere('published_at', '<=', now()))
            ->pluck('slug') as $slug) {
            $urls[] = route('posts.show', $slug);
        }

        foreach (CampaignLandingPage::query()
            ->where('is_published', true)
            ->where(fn ($query) => $query->whereNull('starts_at')->orWhere('starts_at', '<=', now()))
            ->where(fn ($query) => $query->whereNull('ends_at')->orWhere('ends_at', '>=', now()))
            ->pluck('slug') as $slug) {
            $urls[] = route('campaigns.show', $slug);
        }

        $entries = collect(array_unique($urls))
            ->map(fn (string $url): string => '<url><loc>'.htmlspecialchars($url, ENT_XML1 | ENT_COMPAT, 'UTF-8').'</loc></url>')
            ->implode('');

        return response(
            '<?xml version="1.0" encoding="UTF-8"?>'.
            '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'.$entries.'</urlset>',
            200,
            ['Content-Type' => 'application/xml; charset=UTF-8'],
        );
    }
}
