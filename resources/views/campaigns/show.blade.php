@extends('layouts.site')

@section('title', $landingPage->seo_title ?: ($landingPage->headline ?: $landingPage->campaign_name) . ' | ' . app(\App\Services\UiText::class)->get('common.site_name'))
@section('meta_description', $landingPage->seo_description ?: ($landingPage->summary ?: app(\App\Services\UiText::class)->get('campaign.fallback_description')))

@section('content')
    @if($blocks->isNotEmpty())
        @php
            $campaignFormId = null;
        @endphp
        @foreach($blocks as $block)
            @php
                $blockFormId = null;
                if ($block->type === 'lead_form') {
                    $blockFormId = $campaignFormId === null ? 'campaign-lead-form' : 'campaign-lead-form-'.$block->id;
                    $campaignFormId = $blockFormId;
                }
            @endphp
            <x-page-block :block="$block" :page="$landingPage->contentPage" :vehicles="$vehicles" :posts="$posts" :promotions="$promotions" :campaign-id="$landingPage->id" :campaign-vehicle-id="$landingPage->vehicle?->id" :form-id="$blockFormId" />
        @endforeach
        @unless($blocks->contains('type', 'lead_form'))
            <section id="campaign-lead-form" class="site-container section-space max-w-3xl">
                <h2 class="mb-5 text-2xl font-bold text-slate-950">@ui('lead.form_title')</h2>
                <x-lead-quick-form :vehicles="$vehicles" :campaign-id="$landingPage->id" :vehicle-id="$landingPage->vehicle?->id" form-id="campaign-lead-form" />
            </section>
        @endunless
    @else
        <section class="bg-slate-950 text-white">
            <section class="site-container grid min-h-[70vh] items-center gap-10 py-12 lg:grid-cols-2 lg:py-20">
                <div>
                    <a href="{{ route('home') }}" class="text-sm font-bold tracking-wide text-red-200">@ui('campaign.brand_link')</a>
                    @if($landingPage->vehicle)
                        <p class="mt-8 text-sm font-semibold uppercase tracking-wider text-red-200">{{ $landingPage->vehicle->line->name }}</p>
                    @endif
                    <h1 class="mt-3 text-4xl font-black leading-tight sm:text-5xl">{{ $landingPage->headline ?: $landingPage->campaign_name }}</h1>
                    @if($landingPage->summary)<p class="mt-5 text-lg leading-8 text-slate-300">{{ $landingPage->summary }}</p>@endif
                    <div class="mt-8 flex flex-wrap gap-3">
                        <a href="#campaign-lead-form" class="button-primary">@ui('campaign.contact')</a>
                    </div>
                    @if($landingPage->vehicle)
                        <p class="mt-4 text-sm text-slate-300">{{ str_replace(':vehicle', $landingPage->vehicle->name, app(\App\Services\UiText::class)->get('campaign.price_contact')) }}</p>
                    @endif
                </div>
                <div class="flex min-h-64 items-center justify-center rounded-3xl border border-white/15 bg-white/5 p-8 text-center text-sm text-slate-400">
                    @ui('campaign.image_pending')
                </div>
            </section>
            @if($landingPage->body)
                <section class="bg-white py-12 text-slate-800">
                    <div class="site-container max-w-4xl whitespace-pre-line leading-8">{{ $landingPage->body }}</div>
                </section>
            @endif
        </section>
        <section id="campaign-lead-form" class="site-container section-space max-w-3xl">
            <h2 class="mb-5 text-2xl font-bold text-slate-950">@ui('lead.form_title')</h2>
            <x-lead-quick-form :vehicles="$vehicles" :campaign-id="$landingPage->id" :vehicle-id="$landingPage->vehicle?->id" form-id="campaign-lead-form" />
        </section>
    @endif
    <section class="border-t border-slate-200 py-8">
        <div class="site-container flex flex-wrap items-center justify-between gap-4 text-sm text-slate-600">
            <p>@ui('campaign.conditions')</p>
            <a href="{{ route('pages.privacy') }}" class="underline">@ui('common.privacy_policy')</a>
        </div>
    </section>
@endsection
