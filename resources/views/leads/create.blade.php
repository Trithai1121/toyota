@extends('layouts.site')

@section('title', app(\App\Services\UiText::class)->get('lead.seo_title'))
@section('meta_description', app(\App\Services\UiText::class)->get('lead.seo_description'))

@section('content')
    <section class="bg-canvas py-10 sm:py-14">
        <div class="site-container max-w-3xl">
            <nav aria-label="@ui('common.breadcrumb')" class="text-sm text-slate-500"><a href="{{ route('home') }}" class="hover:text-toyota">@ui('common.home')</a><span class="mx-2">/</span><span>@ui('common.request')</span></nav>
            <h1 class="mt-4 text-3xl font-bold sm:text-4xl">@ui('lead.form_title')</h1>
            <p class="mt-3 leading-7 text-slate-600">@ui('lead.form_description')</p>
        </div>
    </section>

    <section class="site-container section-space max-w-5xl">
        <div class="grid items-start gap-6 lg:grid-cols-[minmax(0,1fr)_18rem]">
        <form action="{{ route('leads.store') }}" method="POST" class="surface-card space-y-6 p-5 sm:p-8" x-data="leadTrackingConsent" @submit="sending = true">
            @csrf
            <input type="hidden" name="submission_token" value="{{ (string) \Illuminate\Support\Str::uuid() }}">
            <input type="hidden" name="request_type" value="{{ old('request_type', $requestType) }}">
            <input type="hidden" name="marketing_consent" value="0" :value="marketingConsent ? '1' : '0'">
            @if($campaign)
                <input type="hidden" name="campaign_landing_page_id" value="{{ $campaign->id }}">
            @endif
            @if($selectedVariant && $selectedColor && $selectedPrice !== null)
                <input type="hidden" name="variant_id" value="{{ $selectedVariant->id }}">
                <input type="hidden" name="vehicle_color_id" value="{{ $selectedColor->id }}">
                <div class="rounded-xl border border-slate-200 bg-white p-4">
                    <p class="text-sm font-semibold text-slate-900">Lựa chọn xe</p>
                    <p class="mt-1 text-sm text-slate-700">{{ $selectedVehicle->name }} · {{ $selectedVariant->name }} · {{ $selectedColor->name }}</p>
                    <p class="mt-1 text-sm font-bold text-toyota-ink">{{ number_format($selectedPrice, 0, ',', '.') }} VNĐ</p>
                    <p class="mt-1 text-xs text-slate-500">Giá được tính lại từ phiên bản và màu đã chọn khi gửi yêu cầu.</p>
                </div>
            @endif
            @foreach(['utm_source', 'utm_medium', 'utm_campaign', 'utm_content', 'utm_term'] as $utmKey)
                @if(isset($utm[$utmKey]))
                    <input type="hidden" name="{{ $utmKey }}" value="{{ $utm[$utmKey] }}">
                @endif
            @endforeach

            <div class="rounded-2xl border border-red-100 bg-red-50 p-4 sm:p-5">
                <p class="eyebrow">{{ app(\App\Services\UiText::class)->get($requestType === 'test_drive' ? 'lead.test_drive' : 'lead.quote') }}</p>
                <p class="mt-2 font-semibold text-slate-950">@ui('lead.confirm_product')</p>
            </div>

            @if($errors->any())
                <div class="rounded-xl border border-red-300 bg-red-50 p-4 text-sm text-red-900" role="alert">
                    <p class="font-semibold">@ui('lead.check_errors')</p>
                    <ul class="mt-2 list-inside list-disc">
                        @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
                    </ul>
                </div>
            @endif

            <div class="grid gap-5 sm:grid-cols-2">
                <label class="text-sm font-semibold sm:col-span-2">@ui('lead.full_name') <span class="text-red-700">*</span>
                    <input name="full_name" value="{{ old('full_name') }}" type="text" autocomplete="name" minlength="2" maxlength="150" required aria-invalid="{{ $errors->has('full_name') ? 'true' : 'false' }}" class="form-input mt-2">
                </label>
                <label class="text-sm font-semibold">@ui('lead.phone') <span class="text-red-700">*</span>
                    <input name="phone" value="{{ old('phone') }}" type="tel" inputmode="tel" autocomplete="tel" maxlength="24" required placeholder="@ui('lead.phone_placeholder')" aria-invalid="{{ $errors->has('phone') ? 'true' : 'false' }}" class="form-input mt-2">
                </label>
                <label class="text-sm font-semibold">@ui('lead.vehicle')
                    <select name="vehicle_id" class="form-input mt-2">
                        <option value="">@ui('lead.no_vehicle')</option>
                        @foreach($vehicles as $vehicle)
                            <option value="{{ $vehicle->id }}" @selected((string) old('vehicle_id', $selectedVehicle?->id) === (string) $vehicle->id)>{{ $vehicle->name }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="text-sm font-semibold">@ui('lead.region') <span class="text-red-700">*</span>
                    <select name="region" required class="form-input mt-2">
                        <option value="">@ui('lead.choose_region')</option>
                        @foreach($regions as $value => $label)
                            <option value="{{ $value }}" @selected(old('region') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </label>
            </div>

            <details class="rounded-xl border border-slate-200" @if($requestType === 'test_drive') open @endif>
                <summary class="touch-target cursor-pointer px-4 text-sm font-semibold text-slate-800">@ui('lead.additional_info')</summary>
                <div class="grid gap-5 border-t border-slate-200 p-4 sm:grid-cols-2">
                    <label class="text-sm font-semibold">@ui('lead.desired_date')
                        <input name="desired_date" value="{{ old('desired_date') }}" type="date" min="{{ today()->toDateString() }}" @if($requestType === 'test_drive') required @endif class="form-input mt-2">
                        @error('desired_date')<span class="mt-1 block text-sm text-red-800">{{ $message }}</span>@enderror
                    </label>
                    <label class="text-sm font-semibold sm:col-span-2">@ui('lead.note')
                        <textarea name="customer_note" rows="3" maxlength="2000" class="form-input mt-2 min-h-24">{{ old('customer_note') }}</textarea>
                    </label>
                </div>
            </details>

            <div class="absolute -left-[10000px] top-auto h-px w-px overflow-hidden" aria-hidden="true">
                <label for="website">@ui('lead.honeypot')</label>
                <input id="website" name="website" type="text" tabindex="-1" autocomplete="off">
            </div>

            <label class="flex items-start gap-3 text-sm leading-6 text-slate-700">
                <input type="checkbox" name="consent_given" value="1" @checked(old('consent_given')) required class="mt-1 size-4 rounded border-slate-300 text-toyota">
                <span>@ui('lead.consent') <a href="{{ route('pages.privacy') }}" class="font-semibold text-toyota underline">@ui('common.privacy_policy')</a>. <span class="text-red-700">*</span></span>
            </label>

            <button type="submit" class="button-primary w-full sm:w-auto" :disabled="sending">@ui('lead.submit')</button>
            <p class="text-xs leading-5 text-slate-500">@ui('lead.sensitive_note')</p>
        </form>
        <aside class="surface-card hidden p-5 lg:block">
            <p class="eyebrow">@ui('lead.steps_heading')</p>
            <ol class="mt-5 space-y-5">
                @foreach(['lead.step_one', 'lead.step_two', 'lead.step_three'] as $step)
                    <li class="flex gap-3 text-sm leading-6 text-slate-700">
                        <span class="flex size-7 shrink-0 items-center justify-center rounded-full bg-red-50 font-bold text-toyota-ink">{{ $loop->iteration }}</span>
                        <span>@ui($step)</span>
                    </li>
                @endforeach
            </ol>
            <p class="mt-6 border-t border-slate-200 pt-4 text-xs leading-5 text-slate-600">@ui('lead.sensitive_note')</p>
        </aside>
        </div>
    </section>
@endsection
