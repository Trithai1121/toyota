@props(['item', 'mobile' => false, 'vehicles' => []])

@php
    $href = (string) ($item['url'] ?? '');
    $validHref = preg_match('/^(https:\/\/|\/)(?!\/)/i', $href) === 1;
    $hrefPath = parse_url($href, PHP_URL_PATH) ?: '';
    $label = (string) ($item['label'] ?? '');
    $children = is_array($item['children'] ?? null) ? $item['children'] : [];
    $isVehicleMenu = $hrefPath === '/xe-toyota' || mb_strtolower($label) === 'xe toyota';
    $active = $validHref && str_starts_with($href, '/') && (
        $hrefPath === '/'.trim(request()->path(), '/')
        || ($isVehicleMenu && request()->is('xe-toyota/*'))
    );
    $linkClass = $mobile
        ? 'nav-link'
        : 'nav-link';
    $vehicleMenuId = $mobile ? 'mobile-vehicle-mega-menu' : 'desktop-vehicle-mega-menu';
@endphp

@if(filled($label))
    @if($isVehicleMenu)
        @if($mobile)
            <div class="navbar-mobile-vehicle" x-data="{ open: false }">
                <button type="button" class="{{ $linkClass }}" @click="open = !open" :aria-expanded="open.toString()" aria-controls="{{ $vehicleMenuId }}">
                    <span>{{ $label }}</span>
                    <svg aria-hidden="true" viewBox="0 0 20 20" fill="none" :class="{ 'is-rotated': open }"><path d="m5 7.5 5 5 5-5" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/></svg>
                </button>
                <div id="{{ $vehicleMenuId }}" class="navbar-mobile-vehicle-list" x-show="open" x-transition>
                    @include('partials.mega-menu', ['vehicles' => $vehicles, 'mobile' => true])
                    <a href="{{ route('vehicles.index') }}" class="navbar-mobile-link navbar-mobile-all-vehicles">Xem tất cả xe <span aria-hidden="true">→</span></a>
                </div>
            </div>
        @else
            <div class="navbar-mega-wrap" x-data="{ open: false }" @mouseenter="open = true" @mouseleave="open = false" @focusin="open = true" @keydown.escape.window="open = false" @click.outside="open = false">
                    <button type="button" class="nav-link navbar-mega-trigger {{ $active ? 'is-active' : '' }}" @click="open = true" :aria-expanded="open.toString()" aria-haspopup="true" aria-controls="{{ $vehicleMenuId }}">
                    <span>{{ $label }}</span>
                    <svg aria-hidden="true" viewBox="0 0 20 20" fill="none" :class="{ 'is-rotated': open }"><path d="m5 7.5 5 5 5-5" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/></svg>
                </button>
                <div id="{{ $vehicleMenuId }}" class="navbar-mega-menu" x-show="open" x-cloak role="region" aria-label="Danh sách xe Toyota">
                    @include('partials.mega-menu', ['vehicles' => $vehicles, 'mobile' => false])
                </div>
            </div>
        @endif
    @elseif($children)
        <details class="{{ $mobile ? 'navbar-mobile-group' : 'navbar-menu-group' }}">
            <summary class="{{ $linkClass }} {{ $active ? 'is-active' : '' }}">{{ $label }}<svg aria-hidden="true" viewBox="0 0 20 20" fill="none"><path d="m5 7.5 5 5 5-5" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/></svg></summary>
            <div class="{{ $mobile ? 'navbar-mobile-submenu' : 'navbar-submenu' }}">
                @foreach($children as $child)
                    <x-menu-item :item="$child" :mobile="$mobile" :vehicles="$vehicles" />
                @endforeach
            </div>
        </details>
    @elseif($validHref)
        <a class="{{ $linkClass }} {{ $mobile ? 'navbar-mobile-link' : '' }} {{ $active ? 'is-active' : '' }}" href="{{ $href }}" @if($active) aria-current="page" @endif>{{ $label }}</a>
    @endif
@endif
