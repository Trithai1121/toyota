@extends('layouts.site')

@section('title', app(\App\Services\UiText::class)->get('calculator.installment_seo_title'))
@section('meta_description', app(\App\Services\UiText::class)->get('calculator.installment_seo_description'))

@section('content')
    <section class="site-container section-space max-w-6xl">
        <p class="eyebrow">@ui('calculator.installment')</p>
        <h1 class="mt-3 text-3xl font-extrabold text-slate-950">@ui('calculator.installment_title')</h1>
        <p class="mt-3 max-w-3xl leading-7 text-slate-600">@ui('calculator.installment_description')</p>

        <div class="mt-8 grid items-start gap-6 lg:grid-cols-[minmax(0,1fr)_22rem]">
        <form method="GET" class="surface-card grid gap-5 p-5 sm:grid-cols-2 sm:p-8">
            <div class="sm:col-span-2">
                <h2 class="text-xl font-bold">@ui('calculator.inputs_heading')</h2>
                <p class="mt-1 text-sm leading-6 text-slate-600">@ui('calculator.inputs_description')</p>
            </div>
            <label class="text-sm font-semibold text-slate-700 sm:col-span-2">
                @ui('calculator.variant_optional')
                <select name="variant_id" class="form-input mt-2">
                    <option value="">@ui('calculator.choose_variant')</option>
                    @foreach($variants as $variant)
                        <option value="{{ $variant->id }}" @selected((string) ($input['variant_id'] ?? '') === (string) $variant->id)>
                            {{ $variant->vehicle->name }} - {{ $variant->name }}
                        </option>
                    @endforeach
                </select>
            </label>
            <label class="text-sm font-semibold text-slate-700">
                @ui('calculator.price')
                <input name="price" type="number" inputmode="numeric" min="1" max="10000000000" value="{{ $input['price'] ?? $selectedVariant?->list_price }}" required class="form-input mt-2" placeholder="@ui('calculator.price_placeholder')">
            </label>
            <label class="text-sm font-semibold text-slate-700">
                @ui('calculator.down_payment')
                <input name="down_payment_percent" type="number" inputmode="decimal" min="0" max="100" step="0.1" value="{{ $input['down_payment_percent'] ?? '' }}" required class="form-input mt-2">
            </label>
            <label class="text-sm font-semibold text-slate-700 sm:col-span-2">
                @ui('calculator.term')
                <select name="term_months" required class="form-input mt-2">
                    <option value="">@ui('calculator.choose_term')</option>
                    @foreach($rates as $months => $rate)
                        <option value="{{ $months }}" @selected((string) ($input['term_months'] ?? '') === (string) $months)>
                            {{ $months }} @ui('calculator.months_suffix') - @ui('calculator.rate_label') {{ number_format($rate, 2, ',', '.') }}@ui('calculator.annual_suffix')
                        </option>
                    @endforeach
                </select>
            </label>
            @if($rates === [])
                <div class="text-sm text-amber-800 sm:col-span-2">
                    <p>@ui('calculator.rate_missing')</p>
                    <a href="{{ route('pages.contact') }}" class="mt-2 inline-block font-semibold underline">@ui('calculator.contact')</a>
                </div>
            @endif
            @if(session('calculator_error'))
                <p class="text-sm text-red-700 sm:col-span-2">{{ session('calculator_error') }}</p>
            @endif
            @error('price') <p class="text-sm text-red-700 sm:col-span-2">{{ $message }}</p> @enderror
            <button type="submit" class="button-primary w-full sm:col-span-2 sm:w-auto">@ui('calculator.submit_installment')</button>
        </form>

        <section class="surface-card p-5 sm:p-7" aria-live="polite">
            @if($calculation)
                <h2 class="text-xl font-bold">@ui('calculator.result')</h2>
                <dl class="mt-5 divide-y divide-slate-200">
                    <div class="flex items-start justify-between gap-3 py-3"><dt class="text-sm text-slate-600">@ui('calculator.price')</dt><dd class="text-right font-semibold">{{ number_format($calculation['price'], 0, ',', '.') }} @ui('calculator.currency_suffix')</dd></div>
                    <div class="flex items-start justify-between gap-3 py-3"><dt class="text-sm text-slate-600">@ui('calculator.down_payment')</dt><dd class="text-right font-semibold">{{ number_format($calculation['down_payment'], 0, ',', '.') }} @ui('calculator.currency_suffix')</dd></div>
                    <div class="flex items-start justify-between gap-3 py-3"><dt class="text-sm text-slate-600">@ui('calculator.loan')</dt><dd class="text-right font-semibold">{{ number_format($calculation['principal'], 0, ',', '.') }} @ui('calculator.currency_suffix')</dd></div>
                    <div class="flex items-start justify-between gap-3 py-3"><dt class="text-sm text-slate-600">@ui('calculator.configured_rate')</dt><dd class="text-right font-semibold">{{ number_format($calculation['annual_rate'], 2, ',', '.') }}@ui('calculator.annual_suffix')</dd></div>
                    <div class="flex items-start justify-between gap-3 py-3"><dt class="text-sm text-slate-600">@ui('calculator.interest_total')</dt><dd class="text-right font-semibold">{{ number_format($calculation['total_interest'], 0, ',', '.') }} @ui('calculator.currency_suffix')</dd></div>
                </dl>
                <div class="mt-3 rounded-2xl bg-red-50 p-4">
                    <p class="text-sm font-semibold text-slate-800">@ui('calculator.monthly')</p>
                    <p class="mt-1 text-2xl font-extrabold text-toyota-ink">{{ number_format($calculation['monthly_payment'], 0, ',', '.') }} @ui('calculator.currency_suffix')</p>
                </div>
                <p class="mt-5 text-sm leading-6 text-slate-600">@ui('calculator.installment_disclaimer')</p>
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
