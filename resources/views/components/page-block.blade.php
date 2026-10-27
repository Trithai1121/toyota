@props(['block', 'page' => null, 'vehicles', 'posts', 'promotions', 'campaignId' => null, 'campaignVehicleId' => null, 'formId' => null])

@php
    $settings = is_array($block->settings) ? $block->settings : [];
    $spacingTop = min(24, max(0, (int) $block->spacing_top));
    $spacingBottom = min(24, max(0, (int) $block->spacing_bottom));
    $style = 'padding-top: '.($block->type === 'hero' ? '0' : 'calc('.$spacingTop.' * 0.25rem)').'; padding-bottom: calc('.$spacingBottom.' * 0.25rem);';
    if ($block->background_color && preg_match('/^#[0-9a-fA-F]{6}$/', $block->background_color)) {
        $style .= 'background-color: '.$block->background_color.';';
    }
    $alignment = in_array($block->alignment, ['left', 'center', 'right'], true) ? $block->alignment : 'left';
    $buttonClass = match ($block->cta_style) {
        'secondary' => 'button-secondary',
        'link' => 'font-semibold underline underline-offset-4',
        default => 'button-primary',
    };
    $vehicleIds = array_map('intval', (array) ($settings['vehicle_ids'] ?? []));
    $visibleVehicles = $vehicleIds ? $vehicles->whereIn('id', $vehicleIds) : $vehicles;
    if ($block->vehicle_line_id) {
        $visibleVehicles = $visibleVehicles->where('vehicle_line_id', $block->vehicle_line_id);
    }
    $vehicleCategories = $visibleVehicles
        ->map(fn ($vehicle) => $vehicle->line?->category)
        ->filter()
        ->unique()
        ->values();
    $siteTheme = app(\App\Services\SiteTheme::class)->all();
    $dealerTheme = is_array($siteTheme['dealer'] ?? null) ? $siteTheme['dealer'] : [];
    $mapsUrl = $settings['maps_url'] ?? $dealerTheme['maps_url'] ?? config('dealer.maps_url');
    $mapsEmbedUrl = $dealerTheme['maps_embed_url'] ?? '';
    $mapsEmbedParts = is_string($mapsEmbedUrl) ? parse_url($mapsEmbedUrl) : false;
    $validMapsEmbedUrl = is_array($mapsEmbedParts)
        && ($mapsEmbedParts['scheme'] ?? null) === 'https'
        && ($mapsEmbedParts['host'] ?? null) === 'www.google.com'
        && ($mapsEmbedParts['path'] ?? null) === '/maps/embed'
        && filled($mapsEmbedParts['query'] ?? null);
    $heroImages = array_values(array_filter(
        (array) ($settings['hero_images'] ?? []),
        fn ($image): bool => is_array($image) && filled($image['path'] ?? null) && filled($image['alt'] ?? null),
    ));
    $isHomepageHero = $block->type === 'hero' && ($page?->slug ?? null) === 'home';
    if ($heroImages === [] && filled($block->image_path)) {
        $heroImages[] = ['path' => $block->image_path, 'alt' => $block->image_alt];
    }
@endphp

<section
    class="page-block page-block--{{ $block->type }} {{ $isHomepageHero ? 'page-block--hero-home' : '' }} text-{{ $alignment }} {{ $block->show_desktop ? '' : 'hidden md:hidden' }} {{ $block->show_mobile ? '' : 'md:block hidden' }}"
    @if($block->type === 'promotion_strip') id="promotions" @endif
    style="{{ $style }}"
    data-page-block="{{ $block->id }}"
>
    <div class="site-container">
        @if($page && auth()->check() && \Illuminate\Support\Facades\Gate::allows('update', $page))
            <a
                href="{{ \App\Filament\Resources\Pages\PageResource::getUrl('edit', ['record' => $page, 'block' => $block->id]) }}"
                class="mb-3 inline-flex rounded border border-slate-300 bg-white px-3 py-2 text-sm font-semibold text-slate-800 shadow-sm"
            >@ui('page.edit_block')</a>
        @endif
        @if($block->title && $block->type !== 'hero')
            <h2 class="text-2xl font-bold text-slate-950 sm:text-3xl">{{ $block->title }}</h2>
        @endif
        @foreach((array) ($settings['images'] ?? []) as $image)
            @if(filled($image['path'] ?? null) && filled($image['alt'] ?? null))
                <img class="mt-4 inline-block max-h-80 max-w-full rounded-lg object-cover" src="{{ Storage::disk('public')->url($image['path']) }}" @if($imageSrcset = app(\App\Services\ResponsiveImageProcessor::class)->srcset($image['path'])) srcset="{{ $imageSrcset }}" sizes="(max-width: 768px) 100vw, 1280px" @endif alt="{{ $image['alt'] }}" loading="lazy">
            @endif
        @endforeach
        @if($block->description && $block->type !== 'hero')
            <p class="mt-3 text-slate-600">{{ $block->description }}</p>
        @endif

        @switch($block->type)
            @case('hero')
                <div class="{{ $isHomepageHero ? 'hero-banner' : '' }}">
                @if($heroImages !== [])
                    <div class="hero-image-carousel {{ $isHomepageHero ? 'hero-image-carousel--full-bleed' : '' }}" x-data="heroImageCarousel({{ count($heroImages) }}, 6000)">
                        <div
                            class="hero-image-track"
                            x-ref="heroImageTrack"
                            role="group"
                            aria-label="Danh sách ảnh nổi bật"
                            tabindex="0"
                            @scroll.passive="updateActive()"
                        >
                            @foreach($heroImages as $heroImage)
                                <figure
                                    class="hero-image-slide {{ $isHomepageHero ? 'hero-image-slide--full-bleed' : '' }}"
                                    role="group"
                                    aria-roledescription="slide"
                                    aria-label="{{ $loop->iteration }} / {{ count($heroImages) }}"
                                >
                                    <img
                                        src="{{ Storage::disk('public')->url($heroImage['path']) }}"
                                        @if($imageSrcset = app(\App\Services\ResponsiveImageProcessor::class)->srcset($heroImage['path'])) srcset="{{ $imageSrcset }}" sizes="100vw" @endif
                                        alt="{{ $heroImage['alt'] }}"
                                        @if($loop->first) fetchpriority="high" @else loading="lazy" @endif
                                        decoding="async"
                                    >
                                </figure>
                            @endforeach
                        </div>
                        @if(count($heroImages) > 1)
                            <div class="hero-image-controls hero-image-controls--arrows" role="group" aria-label="Điều khiển ảnh nổi bật">
                                <button type="button" class="hero-image-control" @click="previous()" aria-label="Ảnh trước">
                                    <svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="m15 18-6-6 6-6" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                </button>
                                <button type="button" class="hero-image-control" @click="next()" aria-label="Ảnh tiếp theo">
                                    <svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="m9 18 6-6-6-6" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                </button>
                            </div>
                            <div class="hero-image-pagination" role="group" aria-label="Chọn ảnh banner">
                                @foreach($heroImages as $heroImage)
                                    <button
                                        type="button"
                                        class="hero-image-dot"
                                        :class="active === {{ $loop->index }} ? 'hero-image-dot--active' : ''"
                                        @click="goTo({{ $loop->index }})"
                                        :aria-pressed="active === {{ $loop->index }}"
                                        aria-label="Đi đến ảnh {{ $loop->iteration }}"
                                    ></button>
                                @endforeach
                            </div>
                        @endif
                    </div>
                @else
                    <div class="hero-image-placeholder">
                        <svg aria-hidden="true" class="size-10 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="3" y="4" width="18" height="16" rx="2"/><circle cx="8.5" cy="9" r="1.5"/><path d="m21 15-5-5L5 20"/></svg>
                        <span>@ui('vehicle.image_pending')</span>
                    </div>
                @endif
                <div class="hero-layout {{ $isHomepageHero ? 'hero-banner-content site-container' : 'mt-6' }}">
                    <div class="hero-copy">
                        @if($block->title)
                            <p class="eyebrow {{ $isHomepageHero ? '!text-red-200' : '' }}">@ui('common.site_name')</p>
                            <h1 class="mt-4 max-w-3xl text-4xl font-extrabold leading-tight {{ $isHomepageHero ? '!text-white' : 'text-slate-950' }} sm:text-5xl lg:text-6xl">{{ $block->title }}</h1>
                        @endif
                        @if($block->description)
                            <p class="mt-5 max-w-2xl text-base leading-7 {{ $isHomepageHero ? '!text-white' : 'text-slate-700' }} sm:text-lg sm:leading-8">{{ $block->description }}</p>
                        @endif
                        @if($block->body)
                            <div class="prose mt-5 max-w-none text-slate-700">{!! $block->body !!}</div>
                        @endif
                        @if($block->cta_label && $block->cta_url && preg_match('/^(https:\/\/|tel:|mailto:|\/)(?!\/)/i', $block->cta_url))
                            <a class="{{ $buttonClass }} mt-7 w-full sm:w-auto" href="{{ $block->cta_url }}">{{ $block->cta_label }}</a>
                        @endif
                    </div>
                </div>
                </div>
                @break
            @case('text_image')
                @if($block->image_path)
                    <img class="mt-6 max-h-[34rem] w-full rounded-3xl object-cover" src="{{ Storage::disk('public')->url($block->image_path) }}" @if($imageSrcset = app(\App\Services\ResponsiveImageProcessor::class)->srcset($block->image_path)) srcset="{{ $imageSrcset }}" sizes="(max-width: 768px) 100vw, 1280px" @endif alt="{{ $block->image_alt }}" loading="lazy">
                @endif
                @if($block->body)
                    <div class="prose mt-6 max-w-none text-slate-700">{!! $block->body !!}</div>
                @endif
                @break
            @case('vehicle_grid')
                <div class="mt-7" x-data="{ category: 'all' }">
                    @if($vehicleCategories->isNotEmpty())
                        <div class="mb-5 flex gap-2 overflow-x-auto pb-2" role="group" aria-label="Lọc theo kiểu dáng">
                            <button type="button" class="vehicle-category-tab" :class="category === 'all' ? 'vehicle-category-tab--active' : ''" @click="category = 'all'">@ui('vehicle.filter_all')</button>
                            @foreach($vehicleCategories as $category)
                                @php($categoryLabel = $category === 'suv-crossover' ? 'suv_crossover' : $category)
                                <button type="button" class="vehicle-category-tab" :class="category === @js($category) ? 'vehicle-category-tab--active' : ''" @click="category = @js($category)">@ui('vehicle.category.'.$categoryLabel)</button>
                            @endforeach
                        </div>
                    @endif
                    @if($visibleVehicles->isNotEmpty())
                        <div class="vehicle-carousel-controls" aria-label="Điều khiển danh sách xe">
                            <button type="button" class="vehicle-carousel-control" aria-label="Xem các xe trước" @click="$refs.vehicleTrack.scrollBy({ left: -$refs.vehicleTrack.clientWidth * 0.8, behavior: 'smooth' })">
                                <svg aria-hidden="true" viewBox="0 0 24 24" fill="none"><path d="m15 18-6-6 6-6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                            </button>
                            <button type="button" class="vehicle-carousel-control" aria-label="Xem các xe tiếp theo" @click="$refs.vehicleTrack.scrollBy({ left: $refs.vehicleTrack.clientWidth * 0.8, behavior: 'smooth' })">
                                <svg aria-hidden="true" viewBox="0 0 24 24" fill="none"><path d="m9 18 6-6-6-6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                            </button>
                        </div>
                        <div x-ref="vehicleTrack" class="vehicle-carousel-track" tabindex="0" aria-label="Các mẫu xe Toyota">
                            @foreach($visibleVehicles as $vehicle)
                                <div x-show="category === 'all' || category === @js($vehicle->line?->category)" class="vehicle-carousel-item">
                                    <x-vehicle-card :vehicle="$vehicle" :featured="true" />
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="state-empty">
                            <p class="font-semibold text-slate-900">@ui('vehicle.empty_title')</p>
                            <p class="mt-2 text-sm">@ui('vehicle.empty_body')</p>
                        </div>
                    @endif
                </div>
                @break
            @case('promotion_strip')
                @foreach($promotions as $promotion)
                    <article class="mt-5 rounded-xl border border-amber-200 bg-amber-50 p-5 text-left">
                        <h3 class="font-bold">{{ $promotion->title }}</h3>
                        @if($promotion->conditions)<p class="mt-2">{{ $promotion->conditions }}</p>@endif
                        @if($promotion->ends_at)
                            <time datetime="{{ $promotion->ends_at->toIso8601String() }}" data-countdown="{{ $promotion->ends_at->toIso8601String() }}" data-expired-label="@ui('promotion.expired')" data-days-label="@ui('countdown.days')" data-hours-label="@ui('countdown.hours')" data-minutes-label="@ui('countdown.minutes')" data-seconds-label="@ui('countdown.seconds')" class="mt-2 block text-sm font-semibold"></time>
                        @endif
                    </article>
                @endforeach
                @if($block->body)<div class="prose mt-4 max-w-none">{!! $block->body !!}</div>@endif
                @if(! empty($settings['conditions']))
                    <p class="mt-3 text-sm">{{ $settings['conditions'] }}</p>
                @endif
                @if(! empty($settings['ends_at']) && strtotime($settings['ends_at']) !== false)
                    <time class="mt-2 block text-sm font-semibold" data-countdown="{{ date(DATE_ATOM, strtotime($settings['ends_at'])) }}" data-expired-label="@ui('promotion.expired')" data-days-label="@ui('countdown.days')" data-hours-label="@ui('countdown.hours')" data-minutes-label="@ui('countdown.minutes')" data-seconds-label="@ui('countdown.seconds')"></time>
                @endif
                @break
            @case('specifications')
                @if($block->vehicle?->specs?->isNotEmpty())
                    <div class="mt-5 overflow-x-auto">
                        <table class="w-full border-collapse text-left">
                            <tbody>
                                @foreach($block->vehicle->specs as $spec)
                                    <tr class="border-b border-slate-200">
                                        <th class="px-4 py-3 font-semibold">{{ $spec->name }}</th>
                                        <td class="px-4 py-3">{{ $spec->value }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
                @if($block->body)<div class="prose mt-4 max-w-none">{!! $block->body !!}</div>@endif
                @break
            @case('gallery')
                @if($block->image_path)
                    <img class="mt-6 max-h-[34rem] w-full rounded-xl object-cover" src="{{ Storage::disk('public')->url($block->image_path) }}" @if($imageSrcset = app(\App\Services\ResponsiveImageProcessor::class)->srcset($block->image_path)) srcset="{{ $imageSrcset }}" sizes="(max-width: 768px) 100vw, 1280px" @endif alt="{{ $block->image_alt }}" loading="lazy">
                @endif
                @if(! empty($settings['video_url']) && preg_match('/^https:\/\/(www\.)?(youtube\.com|youtu\.be|vimeo\.com)\//i', $settings['video_url']))
                    <a class="mt-4 inline-flex font-semibold underline" href="{{ $settings['video_url'] }}" rel="noopener noreferrer" target="_blank">{{ $block->cta_label ?: $block->title }}</a>
                @endif
                @break
            @case('lead_form')
                <x-lead-quick-form
                    :vehicles="$vehicles"
                    :request-type="$settings['request_type'] ?? 'quote'"
                    :campaign-id="$campaignId"
                    :vehicle-id="$campaignVehicleId"
                    :submit-label="$block->cta_label"
                    :form-id="$formId"
                />
                @break
            @case('calculator')
                <div class="purchase-tools mt-6 grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                    <a class="purchase-tool" href="{{ route('vehicles.index') }}">
                        <span class="purchase-tool-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><path d="M3 12h18M5 12l1.4-5h11.2l1.4 5M5 12v5m14-5v5M7 17h10M7 15h.01M17 15h.01" stroke-linecap="round" stroke-linejoin="round"/></svg></span>
                        <span><strong>@ui('vehicle.catalog_link')</strong><small>@ui('vehicle.catalog_description')</small></span>
                        <span class="purchase-tool-arrow" aria-hidden="true">→</span>
                    </a>
                    <a class="purchase-tool" href="{{ route('calculators.on-road') }}">
                        <span class="purchase-tool-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><path d="M4 5h16v14H4zM8 9h8M8 13h5M8 17h3" stroke-linecap="round" stroke-linejoin="round"/></svg></span>
                        <span><strong>@ui('calculator.on_road')</strong><small>@ui('calculator.on_road_description')</small></span>
                        <span class="purchase-tool-arrow" aria-hidden="true">→</span>
                    </a>
                    <a class="purchase-tool" href="{{ route('calculators.installment') }}">
                        <span class="purchase-tool-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><circle cx="12" cy="12" r="9"/><path d="M8 9h8M8 12h8M8 15h5" stroke-linecap="round"/></svg></span>
                        <span><strong>@ui('calculator.installment')</strong><small>@ui('calculator.installment_description')</small></span>
                        <span class="purchase-tool-arrow" aria-hidden="true">→</span>
                    </a>
                    <a class="purchase-tool" href="{{ route('leads.create', ['type' => 'test_drive']) }}">
                        <span class="purchase-tool-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><path d="M4 14v-3l2-5h12l2 5v3M4 14h16v5H4zM7 19v1m10-1v1M7 14h.01M17 14h.01" stroke-linecap="round" stroke-linejoin="round"/></svg></span>
                        <span><strong>@ui('lead.test_drive')</strong><small>@ui('lead.form_description')</small></span>
                        <span class="purchase-tool-arrow" aria-hidden="true">→</span>
                    </a>
                    <a class="purchase-tool" href="{{ route('leads.create', ['type' => 'quote']) }}">
                        <span class="purchase-tool-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><path d="M7 4h10a2 2 0 0 1 2 2v14H5V6a2 2 0 0 1 2-2Z" stroke-linejoin="round"/><path d="M9 4V3h6v1M8 9h8M8 13h8M8 17h4" stroke-linecap="round"/></svg></span>
                        <span><strong>@ui('lead.quote')</strong><small>@ui('lead.form_description')</small></span>
                        <span class="purchase-tool-arrow" aria-hidden="true">→</span>
                    </a>
                </div>
                @break
            @case('faq')
                @foreach((array) ($settings['items'] ?? []) as $item)
                    @if(filled($item['question'] ?? null))
                        <details class="mt-4 rounded-lg border border-slate-200 p-4 text-left">
                            <summary class="cursor-pointer font-semibold">{{ $item['question'] }}</summary>
                            <p class="mt-3 text-slate-700">{{ $item['answer'] ?? '' }}</p>
                        </details>
                    @endif
                @endforeach
                @break
            @case('recent_posts')
                <div class="mt-6 grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach($posts as $post)
                        <article class="min-w-0 overflow-hidden rounded-xl border border-slate-200 bg-white text-left">
                            <a href="{{ route('posts.show', $post->slug) }}" class="post-cover-link">
                                @if(filled($post->cover_image_path) && Storage::disk('public')->exists($post->cover_image_path))
                                    <img src="{{ Storage::disk('public')->url($post->cover_image_path) }}" alt="{{ $post->cover_image_alt ?: $post->title }}" loading="lazy" decoding="async" width="1200" height="675" class="post-cover-image">
                                @else
                                    <div class="flex aspect-video items-center justify-center bg-slate-100 px-4 text-sm text-slate-600" role="img" aria-label="Ảnh tin tức đang được cập nhật">Ảnh đang được cập nhật</div>
                                @endif
                            </a>
                            <h3 class="px-4 py-4 text-lg font-bold leading-snug text-slate-950">
                                <a class="hover:text-toyota" href="{{ route('posts.show', $post->slug) }}">{{ $post->title }}</a>
                            </h3>
                        </article>
                    @endforeach
                </div>
                @break
            @case('showroom_map')
                <div class="showroom-card mt-6">
                    @if($validMapsEmbedUrl)
                        <iframe
                            class="showroom-map-embed"
                            src="{{ $mapsEmbedUrl }}"
                            title="Bản đồ vị trí showroom"
                            loading="lazy"
                            referrerpolicy="strict-origin-when-cross-origin"
                            allowfullscreen
                        ></iframe>
                    @else
                        <div class="showroom-map-placeholder" role="img" aria-label="Khung bản đồ showroom đang chờ liên kết được xác nhận">
                            <svg aria-hidden="true" viewBox="0 0 48 48" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M24 42s14-13.2 14-25a14 14 0 1 0-28 0c0 11.8 14 25 14 25Z"/><circle cx="24" cy="17" r="5"/></svg>
                            <span>Bản đồ showroom</span>
                        </div>
                    @endif
                    <div class="showroom-details">
                        <p class="eyebrow">{{ $dealerTheme['name'] ?? config('dealer.name') }}</p>
                        <h3 class="mt-2 text-xl font-bold text-slate-950">Đến showroom</h3>
                        <p class="mt-2 leading-7 text-slate-600">{{ $dealerTheme['address'] ?? config('dealer.address') }}</p>
                        @if(is_string($mapsUrl) && filter_var($mapsUrl, FILTER_VALIDATE_URL) && str_starts_with($mapsUrl, 'https://') && ! str_contains($mapsUrl, '['))
                            <a class="button-secondary mt-5" href="{{ $mapsUrl }}" rel="noopener noreferrer" target="_blank">{{ $block->cta_label ?: app(\App\Services\UiText::class)->get('map.open') }}</a>
                        @else
                            <p class="mt-5 text-sm text-slate-600">Đường dẫn bản đồ sẽ được cập nhật sau khi đại lý xác nhận.</p>
                        @endif
                    </div>
                </div>
                @break
            @case('cta')
                @if($block->body)<div class="prose mt-4 max-w-none">{!! $block->body !!}</div>@endif
                @break
            @case('customer_reviews')
                @foreach((array) ($settings['reviews'] ?? []) as $review)
                    @if(($review['confirmed_authentic'] ?? false) && filled($review['customer_name'] ?? null) && filled($review['review'] ?? null) && filled($review['review_date'] ?? null))
                        <figure class="mt-5 rounded-xl border border-slate-200 p-5 text-left">
                            <blockquote class="text-slate-700">{{ $review['review'] }}</blockquote>
                            <figcaption class="mt-3 text-sm font-semibold">{{ $review['customer_name'] }} · {{ $review['review_date'] }}</figcaption>
                        </figure>
                    @endif
                @endforeach
                @break
        @endswitch

        @if($block->type !== 'hero' && $block->cta_label && $block->cta_url && preg_match('/^(https:\/\/|tel:|mailto:|\/)(?!\/)/i', $block->cta_url))
            <a class="{{ $buttonClass }} mt-5 inline-flex" href="{{ $block->cta_url }}">{{ $block->cta_label }}</a>
        @endif
    </div>
</section>
