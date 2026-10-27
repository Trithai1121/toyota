@extends('layouts.site')

@php
    $verifiedImages = $vehicle->images->filter(fn ($image): bool => $image->isReadyForDisplay())->values();
    $verifiedVariants = $vehicle->variants->filter(fn ($variant): bool => $variant->hasVerifiedSource())->values();
    $verifiedSpecs = $vehicle->specs->filter(fn ($spec): bool => $spec->hasVerifiedSource())->values();
    $verifiedPromotions = $vehicle->promotions->filter(fn ($promotion): bool => $promotion->hasVerifiedSource())->values();
    $verifiedDescription = $vehicle->hasVerifiedOfficialContent() ? $vehicle->seo_description : null;
    $pageDescription = filled($verifiedDescription)
        ? $verifiedDescription
        : 'Xem '.$vehicle->name.' và liên hệ đại lý Toyota để xác nhận phiên bản, giá cùng thông tin mới nhất.';
    $canonicalUrl = route('vehicles.show', $vehicle->slug);
@endphp

@section('title', $vehicle->hasVerifiedOfficialContent() && $vehicle->seo_title
    ? $vehicle->seo_title
    : $vehicle->name . ' | ' . app(\App\Services\UiText::class)->get('common.site_name'))
@section('meta_description', $pageDescription)
@section('canonical', $canonicalUrl)
@section('og_type', 'product')
@if($verifiedImages->isNotEmpty())
    @section('og_image', \Illuminate\Support\Facades\Storage::disk('public')->url($verifiedImages->first()->path))
@endif

@if($vehicle->hasVerifiedOfficialContent() || $verifiedImages->isNotEmpty() || $verifiedVariants->isNotEmpty())
@push('meta')
    @php
        $productSchema = [
            '@context' => 'https://schema.org',
            '@type' => 'Product',
            'name' => $vehicle->name,
            'brand' => ['@type' => 'Brand', 'name' => app(\App\Services\UiText::class)->get('common.manufacturer')],
            'url' => $canonicalUrl,
        ];
        if ($vehicle->hasVerifiedOfficialContent() && $vehicle->summary) {
            $productSchema['description'] = $vehicle->summary;
        }
        if ($verifiedImages->isNotEmpty()) {
            $productSchema['image'] = $verifiedImages->take(4)->map(fn ($image) => \Illuminate\Support\Facades\Storage::disk('public')->url($image->path))->all();
        }
        $pricedVariants = $verifiedVariants->whereNotNull('list_price');
        if ($pricedVariants->isNotEmpty()) {
            $productSchema['offers'] = [
                '@type' => 'Offer',
                'priceCurrency' => 'VND',
                'price' => $defaultVariantPrice ?? $pricedVariants->first()->list_price,
                'availability' => 'https://schema.org/InStock',
                'url' => $canonicalUrl,
            ];
        }
    @endphp
    <script type="application/ld+json">{!! json_encode($productSchema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) !!}</script>
@endpush
@endif

@section('content')
    @if($blocks->isNotEmpty())
        @foreach($blocks as $block)
            <x-page-block :block="$block" :page="$vehicle->contentPage" :vehicles="$vehicles" :posts="$posts" :promotions="$promotions" />
        @endforeach
    @else
    @php
        $specGroups = $verifiedSpecs->groupBy(fn ($spec): string => in_array($spec->category, ['dimensions', 'exterior', 'interior', 'performance', 'safety'], true) ? $spec->category : 'general');
        $colorImageIds = $verifiedImages
            ->filter(fn ($image): bool => filled($image->color_name) && preg_match('/^#[0-9a-fA-F]{6}$/', (string) $image->color_hex) === 1)
            ->pluck('id');
        $sectionImages = $verifiedImages
            ->reject(fn ($image): bool => $colorImageIds->contains($image->id))
            ->groupBy(fn ($image): string => in_array($image->category, ['exterior', 'interior', 'performance', 'safety'], true) ? $image->category : 'gallery');
        $priceVariants = $verifiedVariants->filter(fn ($variant): bool => $variant->list_price !== null);
        $heroImage = $verifiedImages->first(fn ($image): bool => $image->category === 'overview') ?? $verifiedImages->first();

        $sectionLabels = [
            'exterior' => 'Ngoại thất',
            'interior' => 'Nội thất',
            'performance' => 'Vận hành',
            'safety' => 'An toàn',
        ];
    @endphp
    <section id="overview" class="site-container pb-8 pt-5 sm:pb-10 sm:pt-6 lg:pb-12 lg:pt-8"
        x-data="vehicleConfigurator(JSON.parse($el.dataset.variants), $el.dataset.initialVariant || null, $el.dataset.initialColor || null, JSON.parse($el.dataset.urls))"
        data-variants="{{ $variantOptions->toJson(JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) }}"
        data-initial-variant="{{ $selectedVariant['slug'] ?? '' }}"
        data-initial-color="{{ $selectedColor['slug'] ?? '' }}"
        data-urls="{{ json_encode([
            'vehicleSlug' => $vehicle->slug,
            'lead' => route('leads.create'),
            'installment' => route('calculators.installment'),
            'onRoad' => route('calculators.on-road'),
            'fallbackImage' => $heroImage ? \Illuminate\Support\Facades\Storage::disk('public')->url($heroImage->path) : null,
        ], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_UNESCAPED_SLASHES) }}">
        <nav aria-label="@ui('common.breadcrumb')" class="text-sm text-slate-500">
            <a href="{{ route('home') }}" class="hover:text-toyota">@ui('common.home')</a><span class="mx-2">/</span>
            <a href="{{ route('vehicles.index') }}" class="hover:text-toyota">@ui('vehicle.catalog_link')</a><span class="mx-2">/</span>
            <span>{{ $vehicle->name }}</span>
        </nav>
        <div class="vehicle-detail-hero mt-4 grid items-start gap-5 lg:grid-cols-[1.2fr_.8fr] lg:gap-8">
            <div class="min-w-0">
                <div x-show="color?.image || fallbackImage" x-cloak class="overflow-hidden rounded-3xl border border-slate-200 bg-white">
                    <img
                        @if($heroImage) src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($heroImage->path) }}" @endif
                        :src="color?.image || fallbackImage || ''"
                        :alt="color?.imageAlt || $el.dataset.defaultAlt"
                        data-default-alt="{{ $heroImage?->alt_text ?: ($vehicle->name.' Toyota') }}"
                        :class="imageLoading ? 'opacity-70' : 'opacity-100'"
                        @load="imageLoaded()"
                        @@error="imageLoaded()"
                        class="vehicle-config-image h-auto max-h-[72vh] w-full object-contain"
                        width="1200"
                        height="900"
                        fetchpriority="high"
                    >
                    <p class="border-t border-slate-100 px-4 py-3 text-sm text-slate-600" aria-live="polite">
                        Đang chọn <span class="font-semibold" x-text="color?.name || 'màu xe'">{{ $selectedColor['name'] ?? 'màu xe' }}</span>
                    </p>
                </div>
                <div x-show="!(color?.image || fallbackImage)" x-cloak class="flex min-h-80 flex-col items-center justify-center gap-3 rounded-3xl border border-dashed border-slate-300 bg-slate-50 p-8 text-center text-slate-700">
                    <svg aria-hidden="true" class="size-10 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="3" y="4" width="18" height="16" rx="2"/><circle cx="8.5" cy="9" r="1.5"/><path d="m21 15-5-5L5 20"/></svg>
                    <p class="font-semibold">Ảnh {{ $vehicle->name }} đang chờ xác minh quyền sử dụng</p>
                    <p class="max-w-md text-sm leading-6 text-slate-600">Thêm ảnh được cấp quyền trong quản trị ở mục “Ảnh và phụ phí theo phiên bản” để mở lựa chọn màu.</p>
                </div>
                <section class="vehicle-configurator-panel mt-4 rounded-2xl border border-slate-200 bg-white p-4 sm:p-5" aria-labelledby="choose-variant-title">
                    <h2 id="choose-variant-title" class="text-sm font-bold uppercase tracking-wide text-slate-900">Chọn phiên bản</h2>
                    <div class="mt-3 flex gap-2 overflow-x-auto pb-2" role="group" aria-label="Chọn phiên bản {{ $vehicle->name }}">
                        <template x-for="(item, index) in variants" :key="item.id">
                            <button type="button" @click="selectVariant(index)" :aria-pressed="selectedIndex === index" :class="selectedIndex === index ? 'border-[#eb0a1e] bg-red-50 text-toyota-ink ring-1 ring-[#eb0a1e]' : 'border-slate-300 bg-white text-slate-700 hover:border-slate-500'" class="min-h-14 min-w-36 shrink-0 rounded-xl border px-4 py-2 text-left transition focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#eb0a1e]">
                                <span class="block text-sm font-bold" x-text="item.name">{{ $selectedVariant['name'] ?? '' }}</span>
                            </button>
                        </template>
                    </div>

                    <div class="mt-5">
                        <p class="text-sm font-semibold text-slate-900">Chọn màu</p>
                        <div class="vehicle-color-options mt-3 flex gap-4 overflow-x-auto pb-3" role="group" aria-label="Chọn màu xe">
                            <template x-for="item in (variant?.colors || [])" :key="item.id">
                                <button type="button" @click="selectColor(item.slug)" :aria-pressed="selectedColorSlug === item.slug" :aria-label="item.name + (item.surcharge > 0 ? ', cộng thêm ' + formatMoney(item.surcharge) + ' VNĐ' : ', không phụ phí')" :title="item.name + (item.surcharge > 0 ? ' (+' + formatMoney(item.surcharge) + ' VNĐ)' : '')" :class="[
                                    selectedColorSlug === item.slug ? 'text-slate-950' : 'text-slate-600',
                                    'hover:text-toyota-ink',
                                ]" class="flex w-24 shrink-0 flex-col items-center gap-2 rounded-lg p-1 text-center text-xs focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#eb0a1e]">
                                    <span class="size-9 rounded-full border-2 border-white shadow-[0_0_0_1px_#94a3b8]" :class="selectedColorSlug === item.slug ? 'ring-2 ring-[#eb0a1e] ring-offset-2' : ''" :style="'background-color:' + item.hex"></span>
                                    <span class="font-semibold" x-text="item.name">{{ $selectedColor['name'] ?? '' }}</span>
                                    <span class="text-[11px] text-slate-500" x-text="Number(item.surcharge) > 0 ? '+' + formatMoney(item.surcharge) + ' VNĐ' : 'Không phụ phí'">Không phụ phí</span>
                                </button>
                            </template>
                            <p x-show="!variant?.colors?.length" class="text-sm text-slate-500">Màu xe đang được cập nhật.</p>
                        </div>
                        <p x-show="variant?.colors?.some((item) => !item.image)" class="text-xs text-slate-500">Màu chưa có ảnh riêng vẫn chọn được; ảnh đại diện của dòng xe sẽ được dùng thay thế.</p>
                    </div>

                </section>
            </div>
            <div class="min-w-0 py-0">
                <p class="eyebrow">{{ $vehicle->line->name }}</p>
                <h1 class="mt-3 text-4xl font-black sm:text-5xl">{{ $vehicle->name }}</h1>
                @if($vehicle->hasVerifiedOfficialContent() && $vehicle->subtitle)<p class="mt-4 text-xl text-slate-700">{{ $vehicle->subtitle }}</p>@endif
                @if($vehicle->hasVerifiedOfficialContent() && $vehicle->summary)
                    <p class="mt-4 max-w-xl leading-7 text-slate-600">{{ $vehicle->summary }}</p>
                @else
                    <p class="mt-4 max-w-xl leading-7 text-slate-600">Thông tin giá và trang bị được hiển thị sau khi đối chiếu nguồn Toyota Việt Nam.</p>
                @endif
                <div class="mt-5 flex flex-wrap gap-2 text-sm">
                    <span class="rounded-full border border-slate-200 px-3 py-1.5 text-slate-700">{{ $vehicle->variants->count() }} phiên bản trong danh mục</span>
                    <span class="rounded-full border border-slate-200 px-3 py-1.5 text-slate-700" x-text="variant?.seats ? variant.seats + ' chỗ' : 'Đang cập nhật'">{{ $selectedVariant['seats'] ?? 'Đang cập nhật' }} chỗ</span>
                    <span class="rounded-full border border-slate-200 px-3 py-1.5 text-slate-700" x-text="variant?.fuel || 'Đang cập nhật'">{{ $selectedVariant['fuel'] ?? 'Đang cập nhật' }}</span>
                </div>
                <div class="mt-6 border-y border-slate-200 py-4" aria-live="polite">
                    <p class="text-sm font-medium text-slate-600">Giá niêm yết</p>
                    <p class="mt-1 text-xl font-semibold text-slate-700"><span x-text="variant ? formatMoney(variant.listPrice) : 'Liên hệ'">{{ $selectedVariant ? number_format($selectedVariant['listPrice'], 0, ',', '.') : 'Liên hệ' }}</span> VNĐ</p>
                    <p x-show="Number(color?.surcharge) > 0" class="mt-1 text-sm text-slate-600">Phụ phí màu: <span class="font-semibold text-slate-900" x-text="'+' + formatMoney(color.surcharge) + ' VNĐ'">{{ $selectedColor && $selectedColor['surcharge'] > 0 ? '+'.number_format($selectedColor['surcharge'], 0, ',', '.').' VNĐ' : '' }}</span></p>
                    <p class="mt-2 text-sm font-semibold text-toyota-ink">Tổng giá</p>
                    <p class="text-3xl font-black text-toyota-ink"><span x-text="totalPrice !== null ? formatMoney(totalPrice) : 'Liên hệ'">{{ $selectedPrice !== null ? number_format($selectedPrice, 0, ',', '.') : 'Liên hệ' }}</span> VNĐ</p>
                    @if($selectedVariant)
                        <p class="mt-1 text-xs text-slate-500">Toyota Việt Nam · Kiểm chứng {{ $selectedVariant['verifiedAt'] ?? 'đang cập nhật' }}.</p>
                    @endif
                </div>
                @if($vehicle->hasVerifiedOfficialContent())
                    <p class="mt-3 text-xs text-slate-500">Nguồn nội dung: <a class="underline underline-offset-4" href="{{ $vehicle->official_source_url }}" target="_blank" rel="noopener noreferrer">Toyota Việt Nam</a> · Kiểm chứng {{ $vehicle->content_verified_at->format('d/m/Y') }}.</p>
                @endif
                <section class="mt-5" aria-label="Thông tin phiên bản và yêu cầu tư vấn">
                    <dl class="mt-4 grid grid-cols-2 gap-3 rounded-xl bg-slate-50 p-4 text-sm sm:grid-cols-3">
                        <div><dt class="text-xs text-slate-500">Số chỗ</dt><dd class="mt-1 font-semibold" x-text="variant?.seats ? variant.seats + ' chỗ' : 'Đang cập nhật'">{{ $selectedVariant['seats'] ?? 'Đang cập nhật' }}</dd></div>
                        <div><dt class="text-xs text-slate-500">Kiểu dáng</dt><dd class="mt-1 font-semibold" x-text="variant?.bodyType || 'Đang cập nhật'">{{ $selectedVariant['bodyType'] ?? 'Đang cập nhật' }}</dd></div>
                        <div><dt class="text-xs text-slate-500">Nhiên liệu</dt><dd class="mt-1 font-semibold" x-text="variant?.fuel || 'Đang cập nhật'">{{ $selectedVariant['fuel'] ?? 'Đang cập nhật' }}</dd></div>
                        <div><dt class="text-xs text-slate-500">Hộp số</dt><dd class="mt-1 font-semibold" x-text="variant?.transmission || 'Đang cập nhật'">{{ $selectedVariant['transmission'] ?? 'Đang cập nhật' }}</dd></div>
                        <div><dt class="text-xs text-slate-500">Động cơ</dt><dd class="mt-1 font-semibold" x-text="variant?.engine || 'Đang cập nhật'">{{ $selectedVariant['engine'] ?? 'Đang cập nhật' }}</dd></div>
                        <div><dt class="text-xs text-slate-500">Xuất xứ</dt><dd class="mt-1 font-semibold" x-text="variant?.origin || 'Đang cập nhật'">{{ $selectedVariant['origin'] ?? 'Đang cập nhật' }}</dd></div>
                    </dl>

                    <div class="mt-5 grid gap-3 sm:grid-cols-2">
                        <a href="{{ route('vehicles.specifications', ['slug' => $vehicle->slug, 'variant' => $selectedVariant['slug'] ?? null]) }}" class="button-secondary w-full">Xem thông số kỹ thuật</a>
                        <a :href="quoteUrl('quote')" href="{{ route('leads.create', ['type' => 'quote', 'vehicle' => $vehicle->slug, 'variant' => $selectedVariant['slug'] ?? null, 'color' => $selectedColor['slug'] ?? null]) }}" class="button-primary w-full">@ui('vehicle.detail_quote')</a>
                        <a :href="quoteUrl('test_drive')" href="{{ route('leads.create', ['type' => 'test_drive', 'vehicle' => $vehicle->slug, 'variant' => $selectedVariant['slug'] ?? null, 'color' => $selectedColor['slug'] ?? null]) }}" class="button-secondary w-full">@ui('vehicle.detail_test_drive')</a>
                    </div>
                    <p class="mt-4 text-xs leading-5 text-slate-500">@ui('vehicle.confirmation_note')</p>
                    <p class="mt-3 text-xs leading-5 text-slate-500">Giá niêm yết đã bao gồm VAT, chưa gồm chi phí lăn bánh. Giá có thể thay đổi theo chương trình khuyến mãi của hãng.</p>
                </section>
                <h2 id="vehicle-versions" class="mt-6 scroll-mt-32 text-xl font-bold">Phiên bản và thông tin niêm yết</h2>
                @if($selectedVariant)
                    <div class="mt-4 overflow-x-auto rounded-2xl border border-slate-200">
                        <table class="data-table min-w-full">
                            <thead>
                                <tr>
                                    <th class="sticky left-0 z-10 min-w-40 bg-slate-50">Thông tin</th>
                                    <th class="min-w-56 text-slate-900" x-text="variant?.name || 'Đang cập nhật'">{{ $selectedVariant['name'] }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr><th class="sticky left-0 bg-white font-semibold">Giá niêm yết (VNĐ)</th><td class="font-semibold" x-text="variant ? formatMoney(variant.listPrice) + ' VNĐ' : 'Cần xác nhận'">{{ number_format($selectedVariant['listPrice'], 0, ',', '.') }} VNĐ</td></tr>
                                <tr><th class="sticky left-0 bg-white font-semibold">Số chỗ</th><td x-text="variant?.seats ? variant.seats + ' chỗ' : 'Cần kiểm tra nguồn'">{{ $selectedVariant['seats'] ? $selectedVariant['seats'].' chỗ' : 'Cần kiểm tra nguồn' }}</td></tr>
                                <tr><th class="sticky left-0 bg-white font-semibold">Nhiên liệu</th><td x-text="variant?.fuel || 'Cần kiểm tra nguồn'">{{ $selectedVariant['fuel'] ?: 'Cần kiểm tra nguồn' }}</td></tr>
                                <tr><th class="sticky left-0 bg-white font-semibold">Hộp số</th><td x-text="variant?.transmission || 'Cần kiểm tra nguồn'">{{ $selectedVariant['transmission'] ?: 'Cần kiểm tra nguồn' }}</td></tr>
                                <tr><th class="sticky left-0 bg-white font-semibold">Động cơ</th><td x-text="variant?.engine || 'Cần kiểm tra nguồn'">{{ $selectedVariant['engine'] ?: 'Cần kiểm tra nguồn' }}</td></tr>
                                <tr><th class="sticky left-0 bg-white font-semibold">Xuất xứ</th><td x-text="variant?.origin || 'Cần kiểm tra nguồn'">{{ $selectedVariant['origin'] ?: 'Cần kiểm tra nguồn' }}</td></tr>
                                <tr>
                                    <th class="sticky left-0 bg-white font-semibold">Màu ngoại thất</th>
                                    <td>
                                        <template x-if="variant?.colors?.length">
                                            <ul class="flex flex-wrap gap-x-2 gap-y-1">
                                                <template x-for="color in variant.colors" :key="color.id">
                                                    <li><span x-text="color.name"></span><span x-show="color.surcharge > 0" class="text-slate-500" x-text="' (+' + formatMoney(color.surcharge) + ' VNĐ)'"></span></li>
                                                </template>
                                            </ul>
                                        </template>
                                        <span x-show="!variant?.colors?.length">Liên hệ đại lý</span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                @else
                    <p class="mt-3 rounded-xl bg-slate-50 p-4 text-slate-600">@ui('vehicle.contact_price_note')</p>
                @endif
            </div>
        </div>
    </section>
    <nav class="vehicle-detail-nav sticky top-[4.5rem] z-20 flex gap-1 overflow-x-auto border-b border-slate-200 bg-white/95 px-4 shadow-sm backdrop-blur sm:px-6 lg:top-[5.25rem]" aria-label="@ui('vehicle.detail_sections')">
            <a class="touch-target shrink-0 px-3 text-sm font-semibold" href="#overview">Tổng quan</a>
            <a class="touch-target shrink-0 px-3 text-sm font-semibold" href="#vehicle-versions">Phiên bản</a>
            <a class="touch-target shrink-0 px-3 text-sm font-semibold" href="#vehicle-gallery">Thư viện</a>
            <a class="touch-target shrink-0 px-3 text-sm font-semibold" href="#vehicle-exterior">Ngoại thất</a>
            <a class="touch-target shrink-0 px-3 text-sm font-semibold" href="#vehicle-interior">Nội thất</a>
            <a class="touch-target shrink-0 px-3 text-sm font-semibold" href="#vehicle-performance">Vận hành</a>
            <a class="touch-target shrink-0 px-3 text-sm font-semibold" href="#vehicle-safety">An toàn</a>
            <a class="touch-target shrink-0 px-3 text-sm font-semibold" href="{{ route('vehicles.specifications', ['slug' => $vehicle->slug, 'variant' => $selectedVariant['slug'] ?? null]) }}">Thông số kỹ thuật</a>
            <a class="touch-target shrink-0 px-3 text-sm font-semibold" href="#vehicle-accessories">Phụ kiện</a>
            <a class="touch-target shrink-0 px-3 text-sm font-semibold" href="#vehicle-purchase-tools">Công cụ mua xe</a>
    </nav>

    @if($vehicle->hasVerifiedOfficialContent() && $vehicle->description)
        <section id="vehicle-info" class="bg-canvas py-8 sm:py-10">
            <div class="site-container">
                <h2 class="text-2xl font-bold">@ui('vehicle.info')</h2>
                <div class="mt-4 whitespace-pre-line leading-7 text-slate-700">{{ $vehicle->description }}</div>
            </div>
        </section>
    @endif

    @foreach($sectionLabels as $category => $label)
        @php
            $categorySpecs = $specGroups->get($category, collect());
            $categoryImages = $sectionImages->get($category, collect());
        @endphp
            <section id="vehicle-{{ $category }}" class="scroll-mt-32 {{ in_array($category, ['interior', 'safety'], true) ? 'bg-canvas' : '' }} py-8 sm:py-10">
                <div class="site-container">
                    <p class="eyebrow">{{ $vehicle->name }}</p>
                    <h2 class="mt-2 text-2xl font-bold">{{ $label }}</h2>
                    @if($category === 'exterior')
                        <figure x-data="variantGallery(JSON.parse($el.dataset.image))" data-image="{{ json_encode($selectedColor['image'] ?? null, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) }}" x-show="image" x-cloak class="mt-5 max-w-3xl overflow-hidden rounded-2xl border border-slate-200 bg-white">
                            <img :src="image" alt="Ảnh ngoại thất theo màu xe đang chọn" class="aspect-[16/10] w-full object-cover" width="1200" height="750" loading="lazy">
                            <figcaption class="px-4 py-3 text-sm text-slate-600">Ảnh theo màu và phiên bản đang chọn</figcaption>
                        </figure>
                    @endif
                    <div class="mt-4 grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                        @if($categorySpecs->isNotEmpty())
                            @foreach($categorySpecs as $spec)
                                @php($featureImage = $categoryImages->first(fn ($image): bool => str_contains(mb_strtolower($image->alt_text), mb_strtolower($spec->name))))
                                <article class="overflow-hidden rounded-xl border border-slate-200 bg-white">
                                    @if($featureImage)
                                        <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($featureImage->path) }}" @if($imageSrcset = app(\App\Services\ResponsiveImageProcessor::class)->srcset($featureImage->path)) srcset="{{ $imageSrcset }}" sizes="(max-width: 768px) 100vw, 33vw" @endif alt="{{ $featureImage->alt_text }}" class="aspect-[16/10] w-full bg-white object-contain p-3" loading="lazy" decoding="async" width="960" height="600">
                                    @endif
                                    <div class="p-5">
                                        <h3 class="font-bold">{{ $spec->name }}</h3>
                                        <p class="mt-2 leading-6 text-slate-600">{{ $spec->value ?: 'Đang cập nhật' }}</p>
                                        <p class="mt-3 text-xs text-slate-500">Toyota Việt Nam · {{ $spec->verified_at->format('d/m/Y') }}</p>
                                    </div>
                                </article>
                            @endforeach
                        @endif
                        @if($categoryImages->isNotEmpty() && $categorySpecs->isEmpty())
                            @foreach($categoryImages as $image)
                                <figure x-data="{ imageFailed: false }" class="overflow-hidden rounded-xl border border-slate-200 bg-white">
                                    <img x-show="!imageFailed" x-on:error="imageFailed = true" src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($image->path) }}" @if($imageSrcset = app(\App\Services\ResponsiveImageProcessor::class)->srcset($image->path)) srcset="{{ $imageSrcset }}" sizes="(max-width: 768px) 100vw, 33vw" @endif alt="{{ $image->alt_text }}" class="aspect-[4/3] w-full bg-slate-100 object-cover" loading="lazy" decoding="async" width="960" height="720">
                                    <p x-show="imageFailed" x-cloak role="status" class="flex aspect-[4/3] items-center justify-center bg-slate-100 p-5 text-center text-sm text-slate-600">Ảnh hiện không tải được.</p>
                                    <figcaption class="p-4 text-sm font-medium text-slate-700">{{ $image->alt_text }}</figcaption>
                                </figure>
                            @endforeach
                        @endif
                        @if($categorySpecs->isEmpty() && $categoryImages->isEmpty())
                            <article class="rounded-xl border border-dashed border-slate-300 bg-white p-5 sm:col-span-2 lg:col-span-3">
                                <h3 class="font-semibold">Thông tin {{ mb_strtolower($label) }} đang chờ xác minh</h3>
                                <p class="mt-2 max-w-3xl leading-6 text-slate-600">Chưa có nội dung và hình ảnh đủ nguồn Toyota Việt Nam để công bố. Đại lý sẽ cập nhật sau khi đối chiếu tài liệu và quyền sử dụng.</p>
                                <a href="{{ route('vehicles.specifications', $vehicle->slug) }}" class="mt-3 inline-flex min-h-11 items-center font-semibold text-toyota-ink underline underline-offset-4">Xem thông số kỹ thuật</a>
                            </article>
                        @endif
                    </div>
                </div>
            </section>
    @endforeach

        <section id="vehicle-gallery" class="site-container scroll-mt-32 py-8 sm:py-10" x-data="vehicleGalleryFilter">
            <p class="eyebrow">{{ $vehicle->name }}</p>
            <h2 class="mt-2 text-2xl font-bold">Thư viện hình ảnh & video</h2>
            <div class="mt-4 flex gap-2 overflow-x-auto pb-2" role="group" aria-label="Lọc thư viện ảnh theo nội dung">
                @foreach(['all' => 'Tất cả', 'exterior' => 'Ngoại thất', 'interior' => 'Nội thất', 'performance' => 'Vận hành', 'safety' => 'An toàn'] as $filter => $filterLabel)
                    <button type="button" class="min-h-11 shrink-0 rounded-lg border px-4 text-sm font-semibold transition" :class="category === '{{ $filter }}' ? 'border-toyota-ink bg-red-50 text-toyota-ink' : 'border-slate-300 bg-white text-slate-700'" :aria-pressed="(category === '{{ $filter }}').toString()" @click="category = '{{ $filter }}'">{{ $filterLabel }}</button>
                @endforeach
            </div>
            @if($verifiedImages->isNotEmpty())
                <div class="mt-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach($verifiedImages as $image)
                        @php($galleryCategory = in_array($image->category, ['exterior', 'interior', 'performance', 'safety'], true) ? $image->category : 'gallery')
                        <figure data-gallery-category="{{ $galleryCategory }}" x-show="category === 'all' || category === '{{ $galleryCategory }}'" x-data="{ imageFailed: false }" class="overflow-hidden rounded-xl border border-slate-200 bg-white">
                            <img x-show="!imageFailed" x-on:error="imageFailed = true" src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($image->path) }}" @if($imageSrcset = app(\App\Services\ResponsiveImageProcessor::class)->srcset($image->path)) srcset="{{ $imageSrcset }}" sizes="(max-width: 768px) 100vw, 33vw" @endif alt="{{ $image->alt_text }}" class="aspect-[4/3] w-full bg-slate-100 object-cover" loading="lazy" decoding="async" width="960" height="720">
                            <p x-show="imageFailed" x-cloak role="status" class="flex aspect-[4/3] items-center justify-center bg-slate-100 p-5 text-center text-sm text-slate-600">Ảnh hiện không tải được.</p>
                            <figcaption class="p-4 text-sm font-medium text-slate-700">{{ $image->alt_text }}</figcaption>
                        </figure>
                    @endforeach
                </div>
                <p x-show="!hasVisibleImages()" x-cloak class="mt-4 text-sm text-slate-600">Chưa có ảnh đã xác minh trong nhóm này.</p>
            @else
                <div class="mt-4 border-y border-slate-200 py-8" role="status">
                    <h3 class="font-semibold">Chưa có media đủ điều kiện công bố</h3>
                    <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-600">Hình ảnh và video sẽ xuất hiện khi nguồn và quyền sử dụng được kiểm tra.</p>
                </div>
            @endif
            @if($vehicle->hasVerifiedOfficialContent() && $vehicle->video_url)
                <a href="{{ $vehicle->video_url }}" target="_blank" rel="noopener noreferrer" class="button-secondary mt-5 inline-flex">@ui('vehicle.open_video')</a>
            @endif
        </section>

        <section id="vehicle-accessories" class="scroll-mt-32 border-y border-slate-200 py-8 sm:py-10">
            <div class="site-container">
                <p class="eyebrow">Toyota chính hãng</p>
                <h2 class="mt-2 text-2xl font-bold">Phụ kiện</h2>
                <div class="mt-5 flex flex-col items-start gap-4 border-y border-slate-200 py-5 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h3 class="font-semibold">Phụ kiện và dịch vụ dành cho xe</h3>
                        <p class="mt-1 max-w-2xl text-sm leading-6 text-slate-600">Danh mục phụ kiện cụ thể sẽ được bổ sung khi có tài liệu và nguồn phù hợp. Liên hệ đại lý để kiểm tra tương thích theo phiên bản.</p>
                    </div>
                    <a href="{{ route('pages.parts') }}" class="button-secondary shrink-0">Xem phụ tùng, phụ kiện</a>
                </div>
            </div>
        </section>

        <section id="vehicle-purchase-tools" class="scroll-mt-32 bg-slate-950 py-12 text-white sm:py-16">
            <div class="site-container">
                <p class="text-sm font-bold uppercase tracking-[0.16em] text-red-200">Hỗ trợ lựa chọn xe</p>
                <h2 class="mt-2 text-2xl font-bold">Công cụ mua xe</h2>
                <p class="mt-2 max-w-2xl leading-7 text-slate-300">Công cụ chỉ mở khi giá xe và dữ liệu tính phí đã được xác minh, cấu hình đầy đủ.</p>
                @if($priceVariants->isNotEmpty())
                    @php($firstPricedVariant = $priceVariants->first())
                    <div class="mt-6 grid gap-4 sm:grid-cols-2">
                        <a data-vehicle-config-tool="installment" href="{{ route('calculators.installment', ['variant_id' => $firstPricedVariant->id]) }}" class="flex min-h-28 items-center justify-between gap-4 rounded-xl border border-white/20 bg-white/5 p-5 font-semibold transition hover:bg-white/10 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-white">
                            <span>Tính khoản trả góp</span><span aria-hidden="true" class="text-2xl">→</span>
                        </a>
                        @if($onRoadFeeRegions !== [])
                            <a data-vehicle-config-tool="on-road" data-region="{{ $onRoadFeeRegions[0] }}" href="{{ route('calculators.on-road', ['variant_id' => $firstPricedVariant->id, 'region' => $onRoadFeeRegions[0]]) }}" class="flex min-h-28 items-center justify-between gap-4 rounded-xl border border-white/20 bg-white/5 p-5 font-semibold transition hover:bg-white/10 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-white">
                                <span>Dự toán chi phí lăn bánh</span><span aria-hidden="true" class="text-2xl">→</span>
                            </a>
                        @endif
                    </div>
                    @if($onRoadFeeRegions === [])
                        <div class="mt-4 rounded-xl border border-white/15 bg-white/5 p-4">
                            <p class="text-sm leading-6 text-slate-300">Phí lăn bánh đang chờ đại lý xác minh. Gửi yêu cầu để nhận tư vấn theo khu vực đăng ký.</p>
                            <a href="{{ route('leads.create', ['type' => 'quote', 'vehicle' => $vehicle->slug, 'variant' => $selectedVariant['slug'] ?? null, 'color' => $selectedColor['slug'] ?? null]) }}" class="mt-3 inline-flex min-h-11 items-center font-semibold text-white underline underline-offset-4">Nhận dự toán lăn bánh</a>
                        </div>
                    @endif
                @else
                    <p class="mt-5 max-w-2xl border-y border-white/20 py-5 text-slate-300">Chưa có giá phiên bản đủ nguồn để tính dự toán. Nhận báo giá để nhân viên đại lý xác nhận thông tin hiện hành.</p>
                    <a href="{{ route('leads.create', ['type' => 'quote', 'vehicle' => $vehicle->slug]) }}" class="button-primary mt-4">Nhận báo giá</a>
                @endif
            </div>
        </section>

    @if($verifiedPromotions->isNotEmpty())
        <section id="vehicle-promotions" class="bg-red-50 py-8 sm:py-10">
            <div class="site-container">
                <h2 class="text-2xl font-bold">@ui('vehicle.promotions')</h2>
                <div class="mt-5 grid gap-4 md:grid-cols-2">
                    @foreach($verifiedPromotions as $promotion)
                        <article class="surface-card p-5">
                            <h3 class="font-bold">{{ $promotion->title }}</h3>
                            @if($promotion->description)<p class="mt-2 text-sm text-slate-600">{{ $promotion->description }}</p>@endif
                            @if($promotion->conditions)<p class="mt-2 text-sm">@ui('vehicle.promotion_conditions'): {{ $promotion->conditions }}</p>@endif
                            <p class="mt-3 text-xs text-slate-500">@ui('vehicle.promotion_period'): {{ $promotion->starts_at?->format('d/m/Y') ?? app(\App\Services\UiText::class)->get('vehicle.confirmed_period') }} – {{ $promotion->ends_at?->format('d/m/Y') ?? app(\App\Services\UiText::class)->get('vehicle.confirmed_period') }}</p>
                            <p class="mt-2 text-xs text-slate-500">Nguồn: <a href="{{ $promotion->source_url }}" target="_blank" rel="noopener noreferrer" class="underline underline-offset-4">{{ parse_url($promotion->source_url, PHP_URL_HOST) }}</a> · Kiểm chứng {{ $promotion->verified_at->format('d/m/Y') }}</p>
                        </article>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

        <section id="vehicle-faq" class="scroll-mt-32 bg-slate-50 py-8 sm:py-10">
            <div class="site-container max-w-4xl">
                <p class="eyebrow">{{ $vehicle->name }}</p>
                <h2 class="mt-2 text-2xl font-bold">Câu hỏi thường gặp</h2>
                <div class="mt-5 divide-y divide-slate-200 border-y border-slate-200 bg-white px-5">
                    <details class="py-4"><summary class="cursor-pointer font-semibold">Giá và trang bị của từng phiên bản được xác nhận ở đâu?</summary><p class="mt-3 leading-6 text-slate-600">Trang chỉ công bố giá hoặc thông số khi có nguồn Toyota Việt Nam và ngày kiểm chứng. Hiện tại, hãy gửi yêu cầu báo giá để đại lý xác nhận dữ liệu mới nhất.</p></details>
                    <details class="py-4"><summary class="cursor-pointer font-semibold">Tôi có thể đăng ký lái thử {{ $vehicle->name }} không?</summary><p class="mt-3 leading-6 text-slate-600">Có. Dùng biểu mẫu đăng ký lái thử; nhân viên đại lý sẽ liên hệ xác nhận thời gian và tình trạng xe.</p></details>
                    <details class="py-4"><summary class="cursor-pointer font-semibold">Vì sao một số ảnh, tài liệu hoặc ưu đãi chưa hiển thị?</summary><p class="mt-3 leading-6 text-slate-600">Các nội dung này chỉ được đăng sau khi nguồn, ngày cập nhật và quyền sử dụng cần thiết được kiểm tra.</p></details>
                </div>
                <div class="mt-5 flex flex-wrap gap-3">
                    <a href="{{ route('leads.create', ['type' => 'quote', 'vehicle' => $vehicle->slug]) }}" class="button-primary">Nhận báo giá</a>
                    <a href="{{ route('leads.create', ['type' => 'test_drive', 'vehicle' => $vehicle->slug]) }}" class="button-secondary">Đăng ký lái thử</a>
                </div>
            </div>
        </section>

    @if($relatedVehicles->isNotEmpty())
        <section class="site-container py-8 sm:py-10">
            <h2 class="text-2xl font-bold">@ui('vehicle.related')</h2>
            <div class="mt-6 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                @foreach($relatedVehicles as $relatedVehicle)
                    <x-vehicle-card :vehicle="$relatedVehicle" />
                @endforeach
            </div>
        </section>
    @endif
    @endif
@endsection
