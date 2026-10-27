@extends('layouts.site')

@php
    $catalogHasFilters = collect($filters)->contains(fn ($value): bool => filled($value));
    $catalogCanonical = $catalogHasFilters
        ? route('vehicles.index')
        : route('vehicles.index', $vehicles->currentPage() > 1 ? ['page' => $vehicles->currentPage()] : []);
    $catalogItems = $vehicles->getCollection()->values()->map(function ($vehicle, int $index) use ($vehicles): array {
        $image = $vehicle->images->first(fn ($image): bool => $image->isReadyForDisplay());
        $startingPrice = $vehicle->variants
            ->where('is_published', true)
            ->filter(fn ($variant): bool => $variant->hasVerifiedSource())
            ->pluck('list_price')
            ->filter(fn ($price): bool => $price !== null)
            ->min();
        $item = [
            '@type' => 'Product',
            'name' => 'Toyota '.$vehicle->name,
            'brand' => ['@type' => 'Brand', 'name' => 'Toyota'],
            'url' => route('vehicles.show', $vehicle->slug),
        ];

        if ($image !== null) {
            $item['image'] = Storage::disk('public')->url($image->path);
        }
        if ($startingPrice !== null) {
            $item['offers'] = [
                '@type' => 'Offer',
                'priceCurrency' => 'VND',
                'price' => $startingPrice,
                'url' => route('vehicles.show', $vehicle->slug),
            ];
        }

        return [
            '@type' => 'ListItem',
            'position' => (($vehicles->currentPage() - 1) * $vehicles->perPage()) + $index + 1,
            'item' => $item,
        ];
    })->all();
    $catalogSchema = [
        '@context' => 'https://schema.org',
        '@type' => 'ItemList',
        'name' => 'Các dòng xe Toyota tại Toyota Kon Tum',
        'url' => $catalogCanonical,
        'numberOfItems' => count($catalogItems),
        'itemListElement' => $catalogItems,
    ];
    $breadcrumbSchema = [
        '@context' => 'https://schema.org',
        '@type' => 'BreadcrumbList',
        'itemListElement' => [
            ['@type' => 'ListItem', 'position' => 1, 'name' => 'Trang chủ', 'item' => route('home')],
            ['@type' => 'ListItem', 'position' => 2, 'name' => 'Xe Toyota', 'item' => route('vehicles.index')],
        ],
    ];
@endphp

@section('title', 'Xe Toyota và bảng giá tại Kon Tum | '.app(\App\Services\UiText::class)->get('common.site_name'))
@section('meta_description', 'Khám phá các dòng xe Toyota tại Kon Tum với thông tin giá tham khảo, phiên bản, thông số kỹ thuật và hình ảnh. Lọc xe theo phân khúc, khoảng giá, số chỗ và nhiên liệu; liên hệ đại lý để nhận báo giá mới nhất.')
@section('canonical', $catalogCanonical)

@push('meta')
    @if($catalogHasFilters)
        <meta name="robots" content="noindex,follow">
    @endif
    <script type="application/ld+json">{!! json_encode($catalogSchema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) !!}</script>
    <script type="application/ld+json">{!! json_encode($breadcrumbSchema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) !!}</script>
@endpush

@section('content')
    <section class="bg-canvas py-10 sm:py-14">
        <div class="site-container">
            <nav aria-label="@ui('common.breadcrumb')" class="text-sm text-slate-500"><a href="{{ route('home') }}" class="hover:text-toyota">@ui('common.home')</a><span class="mx-2">/</span><span>@ui('vehicle.catalog_title')</span></nav>
            <h1 class="mt-4 max-w-4xl text-3xl font-bold sm:text-4xl">Các dòng xe Toyota và bảng giá tại Kon Tum</h1>
            <p class="mt-3 max-w-3xl leading-7 text-slate-600">Khám phá các mẫu sedan, SUV/Crossover, MPV, bán tải và xe hybrid Toyota. Giá hiển thị là giá niêm yết tham khảo theo phiên bản đã được xác nhận; liên hệ Toyota Kon Tum để cập nhật giá bán và ưu đãi mới nhất.</p>
        </div>
    </section>

    <section class="site-container section-space">
        <div class="grid gap-7 wide:grid-cols-[16rem_minmax(0,1fr)]">
        <form method="GET" action="{{ route('vehicles.index') }}" x-data="{ filtersOpen: false, isDesktop: false, init() { const media = window.matchMedia('(min-width: 1280px)'); this.isDesktop = media.matches; media.addEventListener('change', event => this.isDesktop = event.matches); } }" class="filter-sheet surface-card h-fit p-4 sm:p-5">
            <button type="button" class="button-secondary w-full wide:hidden" @click="filtersOpen = true">
                <svg aria-hidden="true" class="mr-2 size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M4 6h16M7 12h10m-7 6h4" stroke-linecap="round"/><circle cx="8" cy="6" r="1"/><circle cx="15" cy="12" r="1"/><circle cx="12" cy="18" r="1"/></svg>
                @ui('vehicle.filters_open')
            </button>
            <div x-show="filtersOpen && !isDesktop" x-cloak class="fixed inset-0 z-40 bg-slate-950/50" @click="filtersOpen = false" aria-hidden="true"></div>
            <div class="filter-sheet-panel" :class="filtersOpen && !isDesktop ? 'filter-sheet-panel--open' : (isDesktop ? 'filter-sheet-panel--desktop' : 'hidden')">
                <div class="mb-4 flex items-center justify-between wide:hidden">
                    <h2 class="text-lg font-bold">@ui('vehicle.filters_open')</h2>
                    <button type="button" class="touch-target rounded-lg border border-slate-300" @click="filtersOpen = false" aria-label="@ui('common.close')">
                        <svg aria-hidden="true" class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="m6 6 12 12M18 6 6 18" stroke-linecap="round"/></svg>
                    </button>
                </div>
                <div class="grid gap-4 sm:grid-cols-2 wide:grid-cols-1">
                <label class="text-sm font-medium">@ui('vehicle.filter_category')
                    <select name="category" class="mt-2 min-h-11 w-full rounded-lg border-slate-300">
                        <option value="">@ui('vehicle.filter_all')</option>
                        @foreach(['sedan' => 'sedan', 'suv-crossover' => 'suv_crossover', 'mpv' => 'mpv', 'pickup' => 'pickup', 'hatchback' => 'hatchback', 'hybrid' => 'hybrid'] as $value => $labelKey)
                            <option value="{{ $value }}" @selected(($filters['category'] ?? '') === $value)>{{ $labelKey === 'hatchback' ? 'Hatchback' : app(\App\Services\UiText::class)->get('vehicle.category.'.$labelKey, $labelKey) }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="text-sm font-medium">@ui('vehicle.filter_price_min')
                    <input name="min_price" type="number" min="0" step="1000000" value="{{ $filters['min_price'] ?? '' }}" class="mt-2 min-h-11 w-full rounded-lg border-slate-300" inputmode="numeric">
                </label>
                <label class="text-sm font-medium">@ui('vehicle.filter_price_max')
                    <input name="max_price" type="number" min="0" step="1000000" value="{{ $filters['max_price'] ?? '' }}" class="mt-2 min-h-11 w-full rounded-lg border-slate-300" inputmode="numeric">
                </label>
                <label class="text-sm font-medium">@ui('vehicle.filter_seats')
                    <select name="seats" class="mt-2 min-h-11 w-full rounded-lg border-slate-300">
                        <option value="">@ui('vehicle.filter_all')</option>
                        @foreach($seatCounts as $seats)
                            <option value="{{ $seats }}" @selected((string)($filters['seats'] ?? '') === (string)$seats)>{{ $seats }} @ui('vehicle.seats_suffix')</option>
                        @endforeach
                    </select>
                </label>
                <label class="text-sm font-medium">@ui('vehicle.filter_fuel')
                    <select name="fuel" class="mt-2 min-h-11 w-full rounded-lg border-slate-300">
                        <option value="">@ui('vehicle.filter_all')</option>
                        @foreach($fuelTypes as $fuel)
                            <option value="{{ $fuel }}" @selected(($filters['fuel'] ?? '') === $fuel)>{{ $fuel }}</option>
                        @endforeach
                    </select>
                </label>
                </div>
            @if($errors->any())
                <p class="mt-3 text-sm text-red-700" role="alert">@ui('vehicle.filter_error')</p>
            @endif
                <div class="mt-5 flex flex-col gap-2 sm:flex-row wide:flex-col">
                    <button class="button-primary w-full" type="submit" @click="filtersOpen = false">@ui('vehicle.filter_submit')</button>
                    <a class="button-secondary w-full" href="{{ route('vehicles.index') }}">@ui('vehicle.filter_clear')</a>
                </div>
            </div>
        </form>

        <div>
        @if($vehicles->isNotEmpty())
            <div class="mb-5 flex flex-wrap items-center justify-between gap-3">
                <p class="text-sm font-medium text-slate-600">{{ $vehicles->total() }} @ui('vehicle.results_count')</p>
            </div>
        @endif
        @if($vehicles->isNotEmpty())
            <div class="grid gap-5 sm:grid-cols-2 ultra:grid-cols-3">
                @foreach($vehicles as $vehicle)
                    <x-vehicle-card :vehicle="$vehicle" />
                @endforeach
            </div>
            <div class="mt-8">{{ $vehicles->links() }}</div>
        @else
            <div class="surface-card border-dashed bg-canvas px-5 py-12 text-center sm:px-10">
                <svg aria-hidden="true" class="mx-auto size-10 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><circle cx="10.5" cy="10.5" r="6.5"/><path d="m16 16 4 4M8 10.5h5" stroke-linecap="round"/></svg>
                <h2 class="mt-4 text-xl font-bold">@ui('vehicle.empty_title')</h2>
                <p class="mt-2 text-slate-600">@ui('vehicle.empty_body')</p>
                <a href="/dang-ky?type=quote" class="button-primary mt-5">@ui('campaign.contact')</a>
            </div>
        @endif
        </div>
        </div>
        <section class="mt-12 max-w-4xl border-t border-slate-200 pt-8" aria-labelledby="vehicle-catalog-guide">
            <h2 id="vehicle-catalog-guide" class="text-2xl font-bold text-slate-950">Chọn xe Toyota phù hợp với nhu cầu</h2>
            <p class="mt-3 leading-7 text-slate-600">Danh mục giúp bạn tra cứu các dòng xe Toyota tại Kon Tum theo phân khúc, khoảng giá, số chỗ và nhiên liệu. Mở trang chi tiết từng xe để xem phiên bản, màu sắc, thông số kỹ thuật và hình ảnh trước khi đăng ký tư vấn.</p>
            <p class="mt-3 leading-7 text-slate-600">Giá xe Toyota có thể thay đổi theo phiên bản, màu sắc và chương trình tại từng thời điểm. Để nhận báo giá mới nhất, hãy <a class="font-semibold text-toyota underline underline-offset-4" href="{{ route('leads.create', ['type' => 'quote']) }}">gửi yêu cầu tư vấn</a> hoặc <a class="font-semibold text-toyota underline underline-offset-4" href="{{ route('calculators.on-road') }}">tham khảo công cụ tính chi phí lăn bánh</a>.</p>
        </section>
    </section>
@endsection
