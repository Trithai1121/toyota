<?php

namespace App\Http\Controllers;

use App\Models\CampaignLandingPage;
use App\Services\PageContentPresenter;
use Illuminate\Contracts\View\View;

class CampaignLandingPageController extends Controller
{
    public function show(string $slug, PageContentPresenter $presenter): View
    {
        $landingPage = CampaignLandingPage::query()
            ->where('slug', $slug)
            ->where('is_published', true)
            ->where(fn ($query) => $query->whereNull('starts_at')->orWhere('starts_at', '<=', now()))
            ->where(fn ($query) => $query->whereNull('ends_at')->orWhere('ends_at', '>=', now()))
            ->with(['vehicle.line', 'vehicle.images', 'vehicle.variants' => fn ($query) => $query->where('is_published', true), 'contentPage'])
            ->firstOrFail();

        $content = $landingPage->contentPage
            ? $presenter->present($landingPage->contentPage)
            : ['blocks' => collect(), 'vehicles' => collect(), 'posts' => collect(), 'promotions' => collect()];

        $campaignVehicle = $landingPage->vehicle;
        if (
            $campaignVehicle?->is_published
            && $campaignVehicle->line?->is_published
            && (! $campaignVehicle->published_at || $campaignVehicle->published_at->isPast())
            && ! $content['vehicles']->contains('id', $campaignVehicle->id)
        ) {
            $content['vehicles']->push($campaignVehicle);
        }

        return view('campaigns.show', ['landingPage' => $landingPage, ...$content]);
    }
}
