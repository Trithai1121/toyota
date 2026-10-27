@props([
    'vehicles',
    'requestType' => 'quote',
    'campaignId' => null,
    'vehicleId' => null,
    'submitLabel' => null,
    'formId' => null,
    'source' => 'website',
    'compact' => false,
    'variantId' => null,
    'colorId' => null,
    'region' => null,
])

@php
    $requestType = in_array($requestType, ['quote', 'test_drive'], true) ? $requestType : 'quote';
    $regions = config('lead.regions', []);
    $formId ??= 'lead-quick-form-'.\Illuminate\Support\Str::uuid();
    $honeypotId = $formId.'-website';
@endphp

<form id="{{ $formId }}" action="{{ route('leads.store') }}" method="POST" class="surface-card space-y-5 p-5 sm:p-7" x-data="leadTrackingConsent" @submit="sending = true">
    @csrf
    <input type="hidden" name="request_type" value="{{ $requestType }}">
    <input type="hidden" name="source" value="{{ $source }}">
    <input type="hidden" name="submission_token" value="{{ (string) \Illuminate\Support\Str::uuid() }}">
    @if($variantId)<input type="hidden" name="variant_id" value="{{ $variantId }}">@endif
    @if($colorId)<input type="hidden" name="vehicle_color_id" value="{{ $colorId }}">@endif
    <input type="hidden" name="marketing_consent" value="0" :value="marketingConsent ? '1' : '0'">
    @if($campaignId)
        <input type="hidden" name="campaign_landing_page_id" value="{{ $campaignId }}">
    @endif
    @foreach(['utm_source', 'utm_medium', 'utm_campaign', 'utm_content', 'utm_term'] as $utmKey)
        @php($utmValue = request()->query($utmKey))
        @if(is_string($utmValue))
            <input type="hidden" name="{{ $utmKey }}" value="{{ $utmValue }}">
        @endif
    @endforeach

    @if($errors->any())
        <div class="rounded-xl border border-red-300 bg-red-50 p-4 text-sm text-red-900" role="alert">
            <ul class="list-inside list-disc">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="grid gap-4 sm:grid-cols-2">
        <label class="text-sm font-semibold sm:col-span-2">
            @ui('lead.full_name') <span class="text-red-700">*</span>
            <input name="full_name" value="{{ old('full_name') }}" type="text" autocomplete="name" minlength="2" maxlength="150" required class="form-input mt-2" aria-invalid="{{ $errors->has('full_name') ? 'true' : 'false' }}">
            @error('full_name')<span class="mt-1 block text-sm font-medium text-red-800">{{ $message }}</span>@enderror
        </label>
        <label class="text-sm font-semibold">
            @ui('lead.phone') <span class="text-red-700">*</span>
            <input name="phone" value="{{ old('phone') }}" type="tel" inputmode="tel" autocomplete="tel" maxlength="24" required placeholder="@ui('lead.phone_placeholder')" class="form-input mt-2" aria-invalid="{{ $errors->has('phone') ? 'true' : 'false' }}">
            @error('phone')<span class="mt-1 block text-sm font-medium text-red-800">{{ $message }}</span>@enderror
        </label>
        <label class="text-sm font-semibold">
            @ui('lead.vehicle')
            <select name="vehicle_id" class="form-input mt-2">
                <option value="">@ui('lead.no_vehicle')</option>
                @foreach($vehicles as $vehicle)
                    <option value="{{ $vehicle->id }}" @selected((string) old('vehicle_id', $vehicleId) === (string) $vehicle->id)>{{ $vehicle->name }}</option>
                @endforeach
            </select>
        </label>
        @unless($compact)<label class="text-sm font-semibold sm:col-span-2">
            @ui('lead.region') <span class="text-red-700">*</span>
            <select name="region" required class="form-input mt-2" aria-invalid="{{ $errors->has('region') ? 'true' : 'false' }}">
                <option value="">@ui('lead.choose_region')</option>
                @foreach($regions as $value => $label)
                    <option value="{{ $value }}" @selected(old('region') === $value)>{{ $label }}</option>
                @endforeach
            </select>
            @error('region')<span class="mt-1 block text-sm font-medium text-red-800">{{ $message }}</span>@enderror
        </label>@else<input type="hidden" name="region" value="{{ $region ?: array_key_first($regions) }}">@endunless
    </div>

    @unless($compact)<label class="block text-sm font-semibold">Ngày lái thử mong muốn<input type="date" name="desired_date" min="{{ now()->toDateString() }}" value="{{ old('desired_date') }}" class="form-input mt-2" @if($requestType === 'test_drive') required @endif>@error('desired_date')<span class="mt-1 block text-sm text-red-800">{{ $message }}</span>@enderror</label>
    <details class="rounded-xl border border-slate-200">
        <summary class="touch-target cursor-pointer px-4 text-sm font-semibold text-slate-800">@ui('lead.additional_info')</summary>
        <label class="block border-t border-slate-200 p-4 text-sm font-semibold">
            @ui('lead.note')
            <textarea name="customer_note" rows="3" maxlength="2000" class="form-input mt-2 min-h-24">{{ old('customer_note') }}</textarea>
        </label>
    </details>@endunless

    <div class="absolute -left-[10000px] top-auto h-px w-px overflow-hidden" aria-hidden="true">
        <label for="{{ $honeypotId }}">@ui('lead.honeypot')</label>
        <input id="{{ $honeypotId }}" name="website" type="text" tabindex="-1" autocomplete="off">
    </div>

    <label class="flex items-start gap-3 text-sm leading-6 text-slate-700">
        <input type="checkbox" name="consent_given" value="1" @checked(old('consent_given')) required class="mt-1 size-4 rounded border-slate-300 text-toyota">
        <span>@ui('lead.consent') <a href="{{ route('pages.privacy') }}" class="font-semibold text-toyota underline">@ui('common.privacy_policy')</a>. <span class="text-red-700">*</span></span>
    </label>

    <button type="submit" class="button-primary w-full sm:w-auto" :disabled="sending" x-text="sending ? 'Đang gửi…' : @js($submitLabel ?: app(\App\Services\UiText::class)->get('lead.submit'))">{{ $submitLabel ?: app(\App\Services\UiText::class)->get('lead.submit') }}</button>
    @unless($compact)<p class="text-xs leading-5 text-slate-500">@ui('lead.sensitive_note')</p>@endunless
</form>
