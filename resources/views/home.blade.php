@extends('layouts.site')

{{-- The SEO title already includes the intended brand; avoid appending Laravel's default APP_NAME. --}}
@section('title', $page->seo_title ?: $page->title)
@if(filled($page->seo_description))
    @section('meta_description', $page->seo_description)
@endif

@section('content')
    @if(!empty($preview))
        <div class="bg-amber-100 px-4 py-3 text-center font-semibold text-amber-950">@ui('preview.banner')</div>
    @endif
    @php($heroBlocks = $blocks->where('type', 'hero')->take(4))

    @if($heroBlocks->isNotEmpty())
        <section
            class="home-hero-carousel"
            x-data="heroCarousel({{ $heroBlocks->count() }})"
            role="region"
            aria-label="Nội dung nổi bật"
            aria-roledescription="carousel"
        >
            @foreach($heroBlocks as $heroBlock)
                <div
                    class="home-hero-slide"
                    x-show="active === {{ $loop->index }}"
                    @if(!$loop->first) x-cloak @endif
                    :aria-hidden="active !== {{ $loop->index }}"
                    role="group"
                    aria-roledescription="slide"
                    aria-label="{{ $loop->iteration }} / {{ $heroBlocks->count() }}"
                >
                    <x-page-block :block="$heroBlock" :page="$page" :vehicles="$vehicles" :posts="$posts" :promotions="$promotions" />
                </div>
            @endforeach

            @if($heroBlocks->count() > 1)
                <div class="home-hero-controls site-container">
                    <div class="flex items-center gap-2" role="group" aria-label="Điều khiển trình chiếu">
                        <button type="button" class="home-hero-control" @click="previous()" aria-label="Slide trước">
                            <svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="m15 18-6-6 6-6" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        </button>
                        <div class="flex items-center gap-2" aria-label="Chọn slide">
                            @foreach($heroBlocks as $heroBlock)
                                <button
                                    type="button"
                                    class="home-hero-dot"
                                    :class="active === {{ $loop->index }} ? 'home-hero-dot--active' : ''"
                                    @click="goTo({{ $loop->index }})"
                                    :aria-pressed="active === {{ $loop->index }}"
                                    aria-label="Đi đến slide {{ $loop->iteration }}"
                                ></button>
                            @endforeach
                        </div>
                        <button type="button" class="home-hero-control" @click="next()" aria-label="Slide tiếp theo">
                            <svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="m9 18 6-6-6-6" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        </button>
                    </div>
                </div>
            @endif
        </section>
    @endif

    @foreach($blocks->where('type', '!=', 'hero') as $block)
        <x-page-block :block="$block" :page="$page" :vehicles="$vehicles" :posts="$posts" :promotions="$promotions" />
    @endforeach
@endsection
