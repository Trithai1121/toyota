@php
    $menuItems = is_array($headerTheme['menu'] ?? null) ? $headerTheme['menu'] : [];
@endphp

<div class="navbar-controller" x-data="siteNavbar" @click="if ($event.target.closest('.navbar-mobile-link')) closeMenu()">
    <header class="site-navbar" :class="{ 'is-scrolled': scrolled }">
        <div class="navbar-shell">
            <a href="{{ route('home') }}" class="navbar-brand" aria-label="{{ $siteName }} – trang chủ">
                @if(filled($logoPath))
                    <img class="navbar-brand-logo" src="{{ Storage::disk('public')->url($logoPath) }}" @if($logoSrcset = app(\App\Services\ResponsiveImageProcessor::class)->srcset($logoPath)) srcset="{{ $logoSrcset }}" sizes="60px" @endif alt="{{ $logoAlt }}" width="60" height="48">
                @else
                    <span class="navbar-brand-logo navbar-brand-logo--fallback" aria-hidden="true">T</span>
                @endif
                <span class="navbar-brand-copy">
                    <span class="navbar-brand-kicker">Toyota</span>
                    <span class="navbar-brand-name">{{ $dealerName }}</span>
                </span>
            </a>

            <nav class="navbar-desktop-menu" aria-label="&#272;i&#7873;u h&#432;&#7899;ng ch&#237;nh">
                @foreach($menuItems as $menuItem)
                    @if(is_array($menuItem))
                        <x-menu-item :item="$menuItem" :vehicles="$navVehicles" />
                    @endif
                @endforeach
            </nav>

            <button type="button" class="navbar-mobile-trigger" x-ref="mobileTrigger" @click="toggleMenu()" :aria-expanded="mobileOpen.toString()" aria-controls="navbar-mobile-panel" aria-label="Mở menu">
                <svg x-show="!mobileOpen" aria-hidden="true" viewBox="0 0 24 24" fill="none"><path d="M4 7h16M4 12h16M4 17h16" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
                <svg x-show="mobileOpen" x-cloak aria-hidden="true" viewBox="0 0 24 24" fill="none"><path d="m6 6 12 12M18 6 6 18" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
            </button>
        </div>
    </header>

    @include('partials.navbar-mobile')
    @include('partials.quote-modal')
    @if(($floatingTheme['contact_widget_enabled'] ?? true))
        @include('partials.contact-float')
    @endif
</div>
