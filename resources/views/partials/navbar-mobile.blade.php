@php
    $menuItems = is_array($headerTheme['menu'] ?? null) ? $headerTheme['menu'] : [];
@endphp

<div class="navbar-mobile-overlay" x-show="mobileOpen" x-cloak x-transition.opacity @keydown.tab="trapMenuFocus($event)">
    <button type="button" class="navbar-mobile-backdrop" @click="closeMenu()" aria-label="Đóng menu"></button>
    <aside id="navbar-mobile-panel" x-ref="mobilePanel" class="navbar-mobile-panel" role="dialog" aria-modal="true" aria-label="Điều hướng di động" x-show="mobileOpen" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="translate-x-full" x-transition:enter-end="translate-x-0" x-transition:leave="transition ease-in duration-150" x-transition:leave-start="translate-x-0" x-transition:leave-end="translate-x-full">
        <div class="navbar-mobile-heading">
            <a href="{{ route('home') }}" class="navbar-brand navbar-brand--mobile navbar-mobile-link">
                @if(filled($logoPath))
                    <img class="navbar-brand-logo" src="{{ Storage::disk('public')->url($logoPath) }}" alt="{{ $logoAlt }}" width="48" height="40">
                @else
                    <span class="navbar-brand-logo navbar-brand-logo--fallback" aria-hidden="true">T</span>
                @endif
                <span class="navbar-brand-copy"><span class="navbar-brand-kicker">Toyota</span><span class="navbar-brand-name">{{ $dealerName }}</span></span>
            </a>
            <button type="button" class="navbar-mobile-close" x-ref="mobileClose" @click="closeMenu()" aria-label="Đóng menu">
                <svg aria-hidden="true" viewBox="0 0 24 24" fill="none"><path d="m6 6 12 12M18 6 6 18" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
            </button>
        </div>
        <nav class="navbar-mobile-menu" aria-label="Điều hướng chính">
            @foreach($menuItems as $menuItem)
                @if(is_array($menuItem))
                    <x-menu-item :item="$menuItem" :vehicles="$navVehicles" mobile />
                @endif
            @endforeach
        </nav>
    </aside>
</div>

