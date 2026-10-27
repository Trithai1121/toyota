@php
    $modalVehicle = collect($navVehicles)->firstWhere('slug', (string) request()->route('slug'));
    $modalVariant = $modalVehicle?->variants->firstWhere('slug', (string) request()->query('variant'));
    $modalColor = $modalVariant && request()->filled('color')
        ? $modalVariant->colors()->where('slug', request()->query('color'))->where('is_active', true)->first()
        : null;
    $popupQuoteSuccess = session('popup_quote_success');
@endphp

<div class="quote-modal" x-show="quoteOpen" x-cloak x-transition.opacity
     @keydown.tab="trapQuoteFocus($event)" @click.self="closeQuoteModal()"
     @if($errors->hasAny(['full_name', 'phone', 'region', 'consent_given', 'vehicle_id', 'website', 'form', 'variant_id']) || $popupQuoteSuccess) x-init="$nextTick(() => openQuoteModal())" @endif>
    <button type="button" class="quote-modal-backdrop" @click="closeQuoteModal()" aria-label="Đóng biểu mẫu"></button>
    <section x-ref="quoteDialog" class="quote-modal-panel" role="dialog" aria-modal="true" aria-labelledby="quote-modal-title" tabindex="-1">
        <header class="quote-modal-header">
            <h2 id="quote-modal-title">{{ $popupQuoteSuccess ? 'Đã nhận yêu cầu báo giá' : 'Nhận báo giá' }}</h2>
            <button type="button" class="quote-modal-close" @click="closeQuoteModal()" aria-label="Đóng">&times;</button>
        </header>
        <div class="quote-modal-content">
            @if($popupQuoteSuccess)
                <div class="space-y-4 p-5 text-center" role="status">
                    <p>Cảm ơn bạn. Tư vấn viên sẽ sớm liên hệ.</p>
                    @if($popupQuoteSuccess['zalo_url'] ?? false)
                        <a class="button-primary inline-flex justify-center" href="{{ $popupQuoteSuccess['zalo_url'] }}" target="_blank" rel="noopener">Chat Zalo với tư vấn</a>
                    @endif
                </div>
            @else
                <x-lead-quick-form :vehicles="$navVehicles" :vehicle-id="$modalVehicle?->id"
                    :variant-id="$modalVariant?->id" :color-id="$modalColor?->id"
                    source="floating-contact" :compact="true" region="kon-tum" request-type="quote"
                    submit-label="Nhận báo giá qua Zalo" form-id="navbar-quote-form" />
            @endif
        </div>
    </section>
</div>
