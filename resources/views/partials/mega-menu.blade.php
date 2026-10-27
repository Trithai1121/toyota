@php
    $menuTabs = [
        'all' => 'Tất cả',
        'hybrid' => 'Hybrid EV',
        'suv-crossover' => 'SUV',
        'sedan' => 'Sedan',
        'mpv' => 'Đa dụng',
        'pickup' => 'Bán tải',
        'hatchback' => 'Hatchback',
    ];
    $vehicles = collect($vehicles);
    $categoriesFor = static function ($vehicle): array {
        $categories = $vehicle->relationLoaded('categories')
            ? $vehicle->categories->pluck('slug')->all()
            : [];
        $category = $vehicle->line?->category;
        if ($category) {
            $categories[] = $category;
        }
        $isHybrid = $vehicle->variants->contains(static fn ($variant): bool =>
            preg_match('/\b(hev|hybrid)\b/i', (string) $variant->name) === 1
            || str_contains(mb_strtolower((string) $variant->fuel_type), 'điện')
        );

        if ($isHybrid) {
            $categories[] = 'hybrid';
        }

        return array_values(array_unique($categories));
    };
    $newCategoryTabs = $vehicles
        ->filter(fn ($vehicle) => (bool) ($vehicle->is_new ?? false))
        ->flatMap($categoriesFor)
        ->unique()
        ->values();
@endphp

<section class="vehicle-mega {{ $mobile ? 'vehicle-mega--mobile' : '' }}" data-vehicle-mega aria-label="Danh mục xe Toyota">
    <nav class="vehicle-mega-tabs" aria-label="Danh mục xe Toyota">
        @foreach($menuTabs as $category => $label)
            @if($category === 'all')
                <a class="vehicle-mega-tab" href="{{ route('vehicles.index') }}" aria-label="Xem tất cả xe Toyota">
                    <span>{{ mb_strtoupper($label) }}</span>
                </a>
            @else
                <button
                    type="button"
                    class="vehicle-mega-tab {{ $category === 'hybrid' ? 'is-active' : '' }}"
                    aria-pressed="{{ $category === 'hybrid' ? 'true' : 'false' }}"
                    data-vehicle-tab="{{ $category }}"
                >
                    @if($category === 'hybrid')<span class="vehicle-mega-hybrid-dot" aria-hidden="true"></span>@endif
                    <span>{{ mb_strtoupper($label) }}</span>
                    @if($newCategoryTabs->contains($category))<sup>[MỚI]</sup>@endif
                </button>
            @endif
        @endforeach
    </nav>

    <div class="vehicle-mega-grid" data-vehicle-grid role="tabpanel" aria-live="polite">
        @foreach($vehicles as $vehicle)
            @php
                $image = $vehicle->images->first(fn ($candidate) => $candidate->isReadyForDisplay());
                $priceVariant = $vehicle->variants
                    ->filter(fn ($variant) => $variant->list_price !== null && $variant->hasVerifiedSource())
                    ->sortBy('list_price')
                    ->first();
                $hybridPriceVariant = $vehicle->variants
                    ->filter(fn ($variant) => $variant->list_price !== null
                        && $variant->hasVerifiedSource()
                        && (preg_match('/\b(hev|hybrid)\b/i', (string) $variant->name) === 1
                            || str_contains(mb_strtolower((string) $variant->fuel_type), 'điện')))
                    ->sortBy('list_price')
                    ->first();
                $hasHybridVariant = $hybridPriceVariant !== null;
                $categories = $categoriesFor($vehicle);
                $initialHybrid = in_array('hybrid', $categories, true);
                $hasPromotion = $vehicle->promotions->contains(fn ($promotion) => $promotion->hasVerifiedSource());
                $isNew = (bool) ($vehicle->is_new ?? false);
                $catalogueUrl = $vehicle->hasVerifiedDocumentUrl($vehicle->catalogue_url) ? $vehicle->catalogue_url : null;
            @endphp
            <article class="vehicle-mega-item" data-vehicle-card data-categories="{{ implode(' ', $categories) }}" data-has-hybrid="{{ $hasHybridVariant ? 'true' : 'false' }}" @if(!$initialHybrid) hidden aria-hidden="true" @endif>
                <a
                    href="{{ route('vehicles.show', $vehicle->slug) }}"
                    class="vehicle-mega-card-link {{ $mobile ? 'navbar-mobile-link' : '' }}"
                    aria-label="{{ $vehicle->name }}"
                >
                    <span class="vehicle-mega-promotion {{ $hasPromotion ? 'is-visible' : '' }}">{{ $hasPromotion ? 'ĐANG KHUYẾN MÃI' : '' }}</span>
                    <span class="vehicle-mega-hybrid-badge" data-hybrid-badge hidden><span aria-hidden="true"></span> HYBRID EV</span>
                    <span class="vehicle-mega-image">
                        @if($image)
                            <img src="{{ Storage::disk('public')->url($image->path) }}" alt="{{ $image->alt_text ?: $vehicle->name }}" width="320" height="150" loading="lazy">
                        @else
                            <span class="vehicle-mega-placeholder" role="img" aria-label="{{ $vehicle->name }}">
                                <svg viewBox="0 0 320 150" aria-hidden="true" focusable="false">
                                    <path d="M58 96 72 67q6-12 20-16l31-9q14-4 31-4h54q19 0 32 13l23 23 19 7q9 3 11 12l3 17H49l3-10q1-3 6-4Z" fill="#e5e7eb" stroke="#9ca3af" stroke-width="3" stroke-linejoin="round"/>
                                    <path d="m96 63 29-10q11-4 27-4h29v30H83l13-16Zm65-14h47q13 0 22 10l17 20h-86V49Z" fill="#fff" stroke="#c5cbd1" stroke-width="2" stroke-linejoin="round"/>
                                    <path d="M65 94h27m137 0h39" stroke="#6b7280" stroke-width="4" stroke-linecap="round"/>
                                    <circle cx="99" cy="108" r="15" fill="#374151"/><circle cx="99" cy="108" r="7" fill="#d1d5db"/>
                                    <circle cx="238" cy="108" r="15" fill="#374151"/><circle cx="238" cy="108" r="7" fill="#d1d5db"/>
                                </svg>
                            </span>
                        @endif
                    </span>
                    <span class="vehicle-mega-name">{{ mb_strtoupper($vehicle->name) }}@if($isNew)<sup>[MỚI]</sup>@endif</span>
                    <span
                        class="vehicle-mega-price"
                        data-vehicle-price
                        data-price-standard="{{ $priceVariant ? 'Chỉ từ '.number_format($priceVariant->list_price, 0, ',', '.').' VNĐ' : '' }}"
                        data-price-hybrid="{{ $hybridPriceVariant ? 'Chỉ từ '.number_format($hybridPriceVariant->list_price, 0, ',', '.').' VNĐ' : '' }}"
                    >{{ $initialHybrid ? ($hybridPriceVariant ? 'Chỉ từ '.number_format($hybridPriceVariant->list_price, 0, ',', '.').' VNĐ' : '') : ($priceVariant ? 'Chỉ từ '.number_format($priceVariant->list_price, 0, ',', '.').' VNĐ' : '') }}</span>
                </a>
                <div class="vehicle-mega-card-footer">
                    @if($catalogueUrl)
                        <a class="vehicle-mega-catalogue" href="{{ $catalogueUrl }}" target="_blank" rel="noopener noreferrer">Tải Catalogue</a>
                    @else
                        <span class="vehicle-mega-catalogue is-unavailable" aria-hidden="true">&nbsp;</span>
                    @endif
                </div>
            </article>
        @endforeach
    </div>

    @unless($mobile)
        <nav class="vehicle-mega-shortcuts" aria-label="Lối tắt">
            <a href="{{ route('leads.create') }}">
                <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="1.4"/><circle cx="12" cy="12" r="5" stroke="currentColor" stroke-width="1.4"/><path d="M12 7v10M7 12h10" stroke="currentColor" stroke-width="1.2"/></svg>
                <span>Đăng ký lái thử</span>
            </a>
            <a href="{{ route('calculators.installment') }}">
                <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><rect x="4" y="3" width="16" height="18" rx="1" stroke="currentColor" stroke-width="1.4"/><path d="M8 7h8M8 11h2m4 0h2m-8 4h2m4 0h2" stroke="currentColor" stroke-width="1.4" stroke-linecap="round"/></svg>
                <span>Dự toán tài chính</span>
            </a>
            <a href="{{ route('calculators.on-road') }}">
                <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="1.4"/><path d="m8 12 2.5 2.5L16 9" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round"/></svg>
                <span>Dự toán chi phí</span>
            </a>
            <a href="{{ route('pages.contact') }}">
                <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M12 21s7-6.3 7-12a7 7 0 1 0-14 0c0 5.7 7 12 7 12Z" stroke="currentColor" stroke-width="1.4"/><circle cx="12" cy="9" r="2.2" stroke="currentColor" stroke-width="1.4"/></svg>
                <span>Liên hệ Đại lý</span>
            </a>
        </nav>
    @endunless
</section>
