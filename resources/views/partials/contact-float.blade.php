@php
    $contactCallVisible = ($floatingTheme['contact_call_enabled'] ?? true) && filled($phoneUrl);
    $contactZaloVisible = ($floatingTheme['contact_zalo_enabled'] ?? true) && $validZaloUrl;
    $contactQuoteVisible = $floatingTheme['contact_quote_enabled'] ?? true;
@endphp

@if($contactCallVisible || $contactZaloVisible || $contactQuoteVisible)
    <div class="contact-widget" x-ref="contactWidget">
        <div class="contact-widget__menu" id="contact-float-menu" x-ref="contactMenu" role="menu" aria-label="Các cách liên hệ" aria-hidden="true" hidden>
            @if($contactCallVisible)
                <a class="contact-widget__item" role="menuitem" href="{{ $phoneUrl }}" data-contact-action="call" @click="trackContact('call'); closeContactMenu()">
                    <svg class="contact-widget__icon" aria-hidden="true" viewBox="0 0 24 24" fill="none"><path d="M7.2 3.5h2.7l1.4 4.1-1.8 1.5a15 15 0 0 0 5.4 5.4l1.5-1.8 4.1 1.4v2.7a2 2 0 0 1-2.1 2C10.2 18.5 5.5 13.8 5.2 5.6a2 2 0 0 1 2-2.1Z" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    <span class="contact-widget__copy"><span>G&#7885;i hotline</span><strong>{{ $dealerHotline }}</strong></span>
                </a>
            @endif
            @if($contactZaloVisible)
                <a class="contact-widget__item" role="menuitem" href="{{ $dealerZalo }}" target="_blank" rel="noopener noreferrer" data-contact-action="zalo" @click="trackContact('zalo'); closeContactMenu()">
                    <img class="contact-widget__zalo" src="{{ asset('images/zalo-logo.svg') }}" alt="" width="34" height="24" loading="eager">
                    <span class="contact-widget__copy"><span>Li&#234;n h&#7879; Zalo</span></span>
                </a>
            @endif
            @if($contactQuoteVisible)
                <button class="contact-widget__item" role="menuitem" type="button" data-contact-action="quote" @click="trackContact('quote'); closeContactMenu(false); openQuoteModal($refs.contactTrigger)">
                    <svg class="contact-widget__icon" aria-hidden="true" viewBox="0 0 24 24" fill="none"><path d="M7 4h10a2 2 0 0 1 2 2v15H5V6a2 2 0 0 1 2-2Z" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round"/><path d="M9 4v-1h6v1M8 10h8M8 14h8M8 18h5" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/></svg>
                    <span class="contact-widget__copy"><span>Nh&#7853;n t&#432; v&#7845;n / b&#225;o gi&#225;</span></span>
                </button>
            @endif
        </div>

        <button class="contact-widget__trigger" type="button" x-ref="contactTrigger" @click.stop="toggleContactMenu()" :aria-expanded="contactOpen.toString()" aria-controls="contact-float-menu" aria-label="Li&#234;n h&#7879;">
            <span>Li&#234;n h&#7879;</span>
            <svg aria-hidden="true" viewBox="0 0 24 24" fill="none"><path d="M4.5 13.5v-3a7.5 7.5 0 0 1 15 0v3M4.5 12H7v6H5.5a2 2 0 0 1-2-2v-2a2 2 0 0 1 1-2Zm15 0H17v6h1.5a2 2 0 0 0 2-2v-2a2 2 0 0 0-1-2ZM17 18a5 5 0 0 1-5 3h-1" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
        </button>
    </div>
@endif
