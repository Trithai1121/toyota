@extends('layouts.site')

@php
    $verifiedDescription = $vehicle->hasVerifiedOfficialContent() ? $vehicle->seo_description : null;
@endphp

@section('title', 'Thông số kỹ thuật '.$vehicle->name.' | '.app(\App\Services\UiText::class)->get('common.site_name'))
@section('meta_description', $verifiedDescription ?: 'Thông số kỹ thuật, phiên bản và trang bị của '.$vehicle->name.'.')
@section('canonical', route('vehicles.specifications', $vehicle->slug))

@section('content')
    <div class="site-container py-8 sm:py-12">
        <nav aria-label="Đường dẫn" class="text-sm text-slate-500">
            <a href="{{ route('home') }}" class="hover:text-toyota">Trang chủ</a><span class="mx-2">/</span>
            <a href="{{ route('vehicles.index') }}" class="hover:text-toyota">Xe Toyota</a><span class="mx-2">/</span>
            <a href="{{ route('vehicles.show', $vehicle->slug) }}" class="hover:text-toyota">{{ $vehicle->name }}</a><span class="mx-2">/</span>
            <span>Thông số kỹ thuật</span>
        </nav>

        <header class="mt-6 flex flex-col gap-5 border-b border-slate-200 pb-6 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <p class="eyebrow">Thông tin sản phẩm</p>
                <h1 class="mt-2 text-3xl font-bold sm:text-4xl">Thông số kỹ thuật {{ $vehicle->name }}</h1>
                <p class="mt-2 max-w-3xl text-slate-600">Chọn phiên bản để xem thông tin đang có. Trang bị có thể khác nhau giữa các phiên bản.</p>
            </div>
            <a href="{{ route('vehicles.show', $vehicle->slug) }}" class="button-secondary shrink-0">Quay lại trang xe</a>
        </header>

        @if($variants->isNotEmpty())
            <form method="GET" action="{{ route('vehicles.specifications', $vehicle->slug) }}" class="mt-6 max-w-xl">
                <label for="variant" class="mb-2 block text-sm font-semibold">Phiên bản</label>
                <select id="variant" name="variant" class="form-input w-full" onchange="this.form.submit()">
                    @foreach($variants as $variant)
                        <option value="{{ $variant->slug }}" @selected($selectedVariant?->id === $variant->id)>{{ $variant->name }}</option>
                    @endforeach
                </select>
            </form>
        @endif

        @if($selectedVariant)
            <section class="mt-8" aria-labelledby="selected-variant-heading">
                <h2 id="selected-variant-heading" class="text-xl font-bold">{{ $selectedVariant->name }}</h2>
                <p class="mt-1 text-sm text-slate-500">Nguồn phiên bản: Toyota Việt Nam · Xác minh {{ $selectedVariant->verified_at?->format('d/m/Y') }}</p>
                <dl class="mt-4 grid gap-px overflow-hidden rounded-xl border border-slate-200 bg-slate-200 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach([
                        'Giá niêm yết' => $selectedVariant->list_price ? number_format($selectedVariant->list_price, 0, ',', '.').' VNĐ' : null,
                        'Số chỗ' => $selectedVariant->seats ? $selectedVariant->seats.' chỗ' : null,
                        'Kiểu dáng' => $selectedVariant->body_type ?: $vehicle->line->category,
                        'Nhiên liệu' => $selectedVariant->fuel_type,
                        'Hộp số' => $selectedVariant->transmission,
                        'Động cơ' => $selectedVariant->engine ?: ($selectedVariant->engine_displacement_cc ? number_format($selectedVariant->engine_displacement_cc, 0, ',', '.').' cc' : null),
                        'Xuất xứ' => $selectedVariant->origin,
                    ] as $label => $value)
                        @if(filled($value))
                            <div class="bg-white p-4"><dt class="text-sm text-slate-500">{{ $label }}</dt><dd class="mt-1 font-semibold">{{ $value }}</dd></div>
                        @endif
                    @endforeach
                </dl>
                @if(is_array($selectedVariant->specs) && count($selectedVariant->specs))
                    <div class="mt-5 divide-y divide-slate-200 border-y border-slate-200 bg-white px-4 sm:px-6">
                        @foreach($selectedVariant->specs as $group => $values)
                            <details class="vehicle-spec-group py-4" @if($loop->first) open @endif>
                                <summary class="cursor-pointer font-semibold">{{ is_string($group) ? $group : 'Thông số phiên bản' }}</summary>
                                <dl class="mt-3 divide-y divide-slate-200">
                                    @foreach((array) $values as $label => $value)
                                        <div class="grid gap-1 py-3 sm:grid-cols-[minmax(12rem,.7fr)_1fr]"><dt class="font-semibold">{{ is_string($label) ? $label : 'Thông tin' }}</dt><dd class="text-slate-700">{{ is_scalar($value) ? $value : 'Đang cập nhật' }}</dd></div>
                                    @endforeach
                                </dl>
                            </details>
                        @endforeach
                    </div>
                @endif
            </section>
        @endif

        @php($labels = ['general' => 'Thông số chung', 'dimensions' => 'Kích thước', 'exterior' => 'Ngoại thất', 'interior' => 'Nội thất', 'performance' => 'Vận hành', 'safety' => 'An toàn'])
        @if($specGroups->isNotEmpty())
            <section class="mt-8" aria-label="Thông số kỹ thuật theo mẫu xe">
                <div class="divide-y divide-slate-200 border-y border-slate-200 bg-white px-4 sm:px-6">
                    @foreach($labels as $category => $title)
                        @if($specGroups->has($category))
                            @php($categoryImages = $sectionImages->get($category, collect()))
                            <details class="vehicle-spec-group py-4" @if($loop->first) open @endif>
                                <summary class="cursor-pointer font-semibold">{{ $title }}</summary>
                                <div class="mt-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                                    @foreach($specGroups[$category] as $spec)
                                        @php($featureImage = $categoryImages->first(fn ($image): bool => str_contains(mb_strtolower($image->alt_text), mb_strtolower($spec->name))))
                                        <article class="overflow-hidden rounded-xl border border-slate-200 bg-white">
                                            @if($featureImage)
                                                <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($featureImage->path) }}" @if($imageSrcset = app(\App\Services\ResponsiveImageProcessor::class)->srcset($featureImage->path)) srcset="{{ $imageSrcset }}" sizes="(max-width: 768px) 100vw, 33vw" @endif alt="{{ $featureImage->alt_text }}" class="aspect-[16/10] w-full bg-white object-contain p-3" width="960" height="600" loading="lazy" decoding="async">
                                            @endif
                                            <div class="p-4">
                                                <h3 class="font-semibold">{{ $spec->name }}</h3>
                                                <p class="mt-2 leading-6 text-slate-600">{{ $spec->value ?: 'Đang cập nhật' }}</p>
                                                <p class="mt-3 text-xs text-slate-500">Toyota Việt Nam · {{ $spec->verified_at->format('d/m/Y') }}</p>
                                            </div>
                                        </article>
                                    @endforeach
                                </div>
                            </details>
                        @endif
                    @endforeach
                </div>
            </section>
        @else
            <p role="status" class="mt-8 border-y border-slate-200 bg-white px-5 py-6 leading-7 text-slate-600">Thông số chi tiết đang được cập nhật theo tài liệu Toyota Việt Nam.</p>
        @endif

        @if($vehicle->hasVerifiedDocumentUrl($vehicle->catalogue_url) || $vehicle->hasVerifiedDocumentUrl($vehicle->manual_url))
            <div class="mt-6 flex flex-wrap gap-3">
                @if($vehicle->hasVerifiedDocumentUrl($vehicle->catalogue_url))<a class="button-secondary" href="{{ $vehicle->catalogue_url }}" target="_blank" rel="noopener noreferrer">Tải catalogue</a>@endif
                @if($vehicle->hasVerifiedDocumentUrl($vehicle->manual_url))<a class="button-secondary" href="{{ $vehicle->manual_url }}" target="_blank" rel="noopener noreferrer">Hướng dẫn sử dụng</a>@endif
            </div>
        @endif
    </div>
@endsection
