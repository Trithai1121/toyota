@props(['vehicle', 'featured' => false])

@php
    $startingPrice = $vehicle->variants
        ->where('is_published', true)
        ->filter(fn ($variant): bool => $variant->hasVerifiedSource())
        ->pluck('list_price')
        ->filter(fn ($price) => $price !== null)
        ->min();
    $image = $vehicle->images->first(fn ($image): bool => $image->isReadyForDisplay());
@endphp

<article class="group flex h-full flex-col overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-[0_10px_36px_-28px_rgba(15,23,42,0.35)] transition duration-200 hover:-translate-y-1 hover:border-slate-300 hover:shadow-lg">
    <a href="{{ route('vehicles.show', $vehicle->slug) }}" @class(['vehicle-card-cover vehicle-card-cover--home' => $featured, 'block' => !$featured]) x-data="{ imageFailed: false }">
        @if($image)
            <img x-show="!imageFailed" x-on:error="imageFailed = true" src="{{ Storage::disk('public')->url($image->path) }}" @if($imageSrcset = app(\App\Services\ResponsiveImageProcessor::class)->srcset($image->path)) srcset="{{ $imageSrcset }}" sizes="(max-width: 768px) 100vw, 33vw" @endif alt="{{ $image->alt_text ?: $vehicle->name }}" class="{{ $featured ? 'vehicle-card-cover-image' : 'aspect-[16/10] w-full object-cover' }}" loading="lazy" decoding="async" width="960" height="600">
            <div x-show="imageFailed" x-cloak role="img" aria-label="Ảnh xe đang chờ cập nhật" class="{{ $featured ? 'vehicle-card-cover-placeholder' : 'flex aspect-[16/10] flex-col items-center justify-center gap-3 bg-slate-100 px-6 text-center text-sm text-slate-600' }}">
                <svg aria-hidden="true" class="size-9 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="3" y="4" width="18" height="16" rx="2"/><circle cx="8.5" cy="9" r="1.5"/><path d="m21 15-5-5L5 20"/></svg>
                @ui('vehicle.image_pending')
            </div>
        @else
            <div class="{{ $featured ? 'vehicle-card-cover-placeholder' : 'flex aspect-[16/10] flex-col items-center justify-center gap-3 bg-slate-100 px-6 text-center text-sm text-slate-600' }}">
                <svg aria-hidden="true" class="size-9 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="3" y="4" width="18" height="16" rx="2"/><circle cx="8.5" cy="9" r="1.5"/><path d="m21 15-5-5L5 20"/></svg>
                @ui('vehicle.image_pending')
            </div>
        @endif
        @if($featured)
            <span class="vehicle-card-cover-badge" aria-hidden="true"><span></span>TOYOTA KON TUM</span>
            <span class="vehicle-card-cover-arrow" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M5 12h14m-6-6 6 6-6 6" stroke-linecap="round" stroke-linejoin="round"/></svg></span>
        @endif
    </a>
    <div class="flex flex-1 flex-col p-5 sm:p-6">
        <p class="eyebrow">{{ $vehicle->line->name }}</p>
        <h3 class="mt-1 text-xl font-bold leading-7"><a href="{{ route('vehicles.show', $vehicle->slug) }}" class="transition-colors duration-200 group-hover:text-toyota focus-visible:underline">{{ $vehicle->name }}</a></h3>
        <p class="mt-3 border-t border-slate-100 pt-3 text-[17px] font-semibold leading-6 text-slate-700">
            @if($startingPrice !== null)
                Chỉ từ {{ number_format($startingPrice, 0, ',', '.') }} VNĐ
            @else
                @ui('vehicle.contact_price')
            @endif
        </p>
        <div class="mt-4 grid grid-cols-2 gap-2">
            <a href="{{ route('vehicles.show', $vehicle->slug) }}" class="button-secondary w-full px-3 text-center">Thông tin xe</a>
            <a href="{{ route('leads.create', ['type' => 'quote', 'vehicle' => $vehicle->slug]) }}" class="button-primary w-full px-3 text-center">Liên hệ tư vấn</a>
        </div>
    </div>
</article>
