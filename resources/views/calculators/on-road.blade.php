@extends('layouts.site')

@section('title', app(\App\Services\UiText::class)->get('calculator.on_road_seo_title'))
@section('meta_description', app(\App\Services\UiText::class)->get('calculator.on_road_seo_description'))

@section('content')
    <section class="site-container section-space max-w-6xl">
        <p class="eyebrow">@ui('calculator.on_road')</p>
        <h1 class="mt-3 text-3xl font-extrabold text-slate-950">@ui('calculator.on_road_title')</h1>
        <p class="mt-3 max-w-3xl leading-7 text-slate-600">@ui('calculator.on_road_description')</p>

        <div class="mt-8 grid items-start gap-6 lg:grid-cols-[minmax(0,1fr)_22rem]">
        <form method="GET" class="surface-card grid gap-5 p-5 sm:grid-cols-2 sm:p-8">
            <div class="sm:col-span-2">
                <h2 class="text-xl font-bold">@ui('calculator.inputs_heading')</h2>
                <p class="mt-1 text-sm leading-6 text-slate-600">@ui('calculator.inputs_description')</p>
            </div>
            <label class="text-sm font-semibold text-slate-700 sm:col-span-2">
                @ui('calculator.region')
                <select name="region" required class="form-input mt-2">
                    <option value="">@ui('lead.choose_region')</option>
                    @foreach($regions as $key => $label)
                        <option value="{{ $key }}" @selected(($input['region'] ?? '') === $key)>{{ $label }}</option>
                    @endforeach
                </select>
            </label>
            <label class="text-sm font-semibold text-slate-700 sm:col-span-2">
                @ui('calculator.variant_optional')
                <select name="variant_id" required class="form-input mt-2">
                    <option value="">@ui('calculator.choose_variant')</option>
                    @foreach($variants as $variant)
                        <option value="{{ $variant->id }}" @selected((string) ($input['variant_id'] ?? '') === (string) $variant->id)>
                            {{ $variant->vehicle->name }} - {{ $variant->name }}
                        </option>
                    @endforeach
                </select>
            </label>
            <label class="text-sm font-semibold text-slate-700 sm:col-span-2">
                Giá niêm yết theo phiên bản
                <input type="text" value="{{ $selectedVariant?->list_price !== null ? number_format($selectedVariant->list_price, 0, ',', '.').' VNĐ' : 'Chọn phiên bản để xem giá' }}" readonly class="form-input mt-2 bg-slate-50">
            </label>
            @if(($input['region'] ?? null) && ! $feesConfigured)
                <p class="text-sm text-amber-800 sm:col-span-2">@ui('calculator.fees_missing')</p>
            @endif
            @error('price') <p class="text-sm text-red-700 sm:col-span-2">{{ $message }}</p> @enderror
            <button type="submit" class="button-primary w-full sm:col-span-2 sm:w-auto">@ui('calculator.submit_on_road')</button>
        </form>

        <section class="surface-card p-5 sm:p-7" aria-live="polite">
            @if($calculation)
                <h2 class="text-xl font-bold">@ui('calculator.result_region') {{ $regions[$input['region']] }}</h2>
                <dl class="mt-4 divide-y divide-slate-200">
                    <div class="flex items-start justify-between gap-3 py-3"><dt class="text-sm text-slate-600">@ui('calculator.price')</dt><dd class="text-right font-semibold">{{ number_format($calculation['price'], 0, ',', '.') }} @ui('calculator.currency_suffix')</dd></div>
                    <div class="flex items-start justify-between gap-3 py-3"><dt class="text-sm text-slate-600">Lệ phí trước bạ ({{ number_format($calculation['registration_rate_percent'], 0, ',', '.') }}%)</dt><dd class="text-right font-semibold">{{ number_format($calculation['registration'], 0, ',', '.') }} @ui('calculator.currency_suffix')</dd></div>
                    <div class="flex items-start justify-between gap-3 py-3"><dt class="text-sm text-slate-600">Bảo hiểm thân vỏ @if(! $calculation['body_insurance_configured'])<span class="block text-xs font-normal text-amber-700">Chưa có tỷ lệ trong Excel; chưa cộng</span>@endif</dt><dd class="text-right font-semibold">{{ number_format($calculation['body_insurance'], 0, ',', '.') }} @ui('calculator.currency_suffix')</dd></div>
                    <div class="flex items-start justify-between gap-3 py-3"><dt class="text-sm text-slate-600">@ui('calculator.license_plate')</dt><dd class="text-right font-semibold">{{ number_format($calculation['license_plate'], 0, ',', '.') }} @ui('calculator.currency_suffix')</dd></div>
                    <div class="flex items-start justify-between gap-3 py-3"><dt class="text-sm text-slate-600">Phí đăng kiểm</dt><dd class="text-right font-semibold">{{ number_format($calculation['inspection'], 0, ',', '.') }} @ui('calculator.currency_suffix')</dd></div>
                    <div class="flex items-start justify-between gap-3 py-3"><dt class="text-sm text-slate-600">@ui('calculator.road_use')</dt><dd class="text-right font-semibold">{{ number_format($calculation['road_use'], 0, ',', '.') }} @ui('calculator.currency_suffix')</dd></div>
                    <div class="flex items-start justify-between gap-3 py-3"><dt class="text-sm text-slate-600">@ui('calculator.compulsory_insurance')</dt><dd class="text-right font-semibold">{{ number_format($calculation['compulsory_insurance'], 0, ',', '.') }} @ui('calculator.currency_suffix')</dd></div>
                    <div class="flex items-start justify-between gap-3 py-3"><dt class="text-sm text-slate-600">Phí dịch vụ</dt><dd class="text-right font-semibold">{{ number_format($calculation['service_fee'], 0, ',', '.') }} @ui('calculator.currency_suffix')</dd></div>
                    <div class="flex items-start justify-between gap-3 py-3"><dt class="text-sm text-slate-600">@ui('calculator.other_fees')</dt><dd class="text-right font-semibold">{{ number_format($calculation['other'], 0, ',', '.') }} @ui('calculator.currency_suffix')</dd></div>
                </dl>
                <div class="mt-3 rounded-2xl bg-slate-50 p-4">
                    <p class="flex justify-between gap-3 text-sm"><span>@ui('calculator.fees_total')</span><strong>{{ number_format($calculation['total_fees'], 0, ',', '.') }} @ui('calculator.currency_suffix')</strong></p>
                    <p class="mt-3 flex justify-between gap-3 border-t border-slate-200 pt-3 text-base"><span class="font-bold">@ui('calculator.on_road_total')</span><strong class="text-toyota-ink">{{ number_format($calculation['total_on_road'], 0, ',', '.') }} @ui('calculator.currency_suffix')</strong></p>
                </div>
                <p class="mt-5 text-sm leading-6 text-slate-600">@ui('calculator.on_road_disclaimer')</p>
                @if($calculation['source_url'])
                    <p class="mt-2 text-xs text-slate-500">Nguồn phí khu vực: <a class="underline underline-offset-4" href="{{ $calculation['source_url'] }}" target="_blank" rel="noopener noreferrer">Cơ quan nhà nước</a> · Kiểm chứng {{ \Illuminate\Support\Carbon::parse($calculation['verified_at'])->format('d/m/Y') }}.</p>
                @elseif($calculation['source_note'])
                    <p class="mt-2 text-xs leading-5 text-slate-500">Nguồn tham khảo: {{ $calculation['source_note'] }}</p>
                @endif
                <a href="{{ route('leads.create', ['type' => 'quote', 'vehicle' => $selectedVariant?->vehicle?->slug]) }}" class="button-primary mt-5 w-full">@ui('campaign.contact')</a>
            @else
                <div class="flex min-h-64 flex-col items-center justify-center text-center">
                    <svg aria-hidden="true" class="size-10 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><path d="M4 19V5m0 14h16M8 15l3-4 3 2 5-7" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    <h2 class="mt-4 text-lg font-bold">@ui('calculator.result_pending')</h2>
                    <p class="mt-2 max-w-xs text-sm leading-6 text-slate-600">@ui('calculator.result_pending_description')</p>
                </div>
            @endif
        </section>
        </div>
    </section>
@endsection
