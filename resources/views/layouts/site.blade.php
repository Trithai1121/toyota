<!doctype html>
<html lang="vi">
<head>
    @php
        $siteTheme = is_array($siteTheme ?? null) ? $siteTheme : [];
        $readColorSetting = static function (string $key, string $default): string {
            $value = setting($key, $default);

            return is_string($value) && preg_match('/^#[A-Fa-f0-9]{6}$/', $value) ? $value : $default;
        };
        $pageBgColor = $readColorSetting('color_page_bg', '#FFFFFF');
        $pageGradientEnd = $readColorSetting('page_bg_gradient_end', '#F3E8EA');
        $pageGradientEnabled = setting('page_bg_gradient_enabled', '1') === '1';
        $pageGradientDirection = in_array(setting('page_bg_gradient_direction', '135deg'), ['90deg', '135deg', '180deg'], true)
            ? setting('page_bg_gradient_direction', '135deg')
            : '135deg';
        $mainTextColor = $readColorSetting('color_text', '#1F2328');
        $footerBgColor = $readColorSetting('color_footer_bg', '#2B2F33');
        $navbarFollowsFooter = setting('navbar_follow_footer', '1') === '1';
        $navbarBgColor = $navbarFollowsFooter ? $footerBgColor : $readColorSetting('color_navbar_bg', '#2B2F33');
        $navBorderColor = $readColorSetting('color_nav_button_border', '#8A9096');
        $navHoverColor = $readColorSetting('color_nav_button_hover', '#EB0A1E');
        $brandTheme = is_array($siteTheme['brand'] ?? null) ? $siteTheme['brand'] : [];
        $dealerTheme = is_array($siteTheme['dealer'] ?? null) ? $siteTheme['dealer'] : [];
        $siteName = $brandTheme['site_name'] ?? app(\App\Services\UiText::class)->get('common.site_name');
        $dealerName = $dealerTheme['name'] ?? $siteName;
        $dealerAddress = $dealerTheme['address'] ?? '';
        $dealerHotline = $dealerTheme['hotline'] ?? '';
        $dealerZalo = $dealerTheme['zalo'] ?? '';
        $dealerEmail = $dealerTheme['email'] ?? '';
        $dealerHours = $dealerTheme['hours'] ?? '';
        $dealerMapsUrl = $dealerTheme['maps_url'] ?? '';
        $serviceRegions = array_values(array_filter(
            $dealerTheme['service_regions'] ?? [],
            fn ($region): bool => is_string($region) && trim($region) !== '',
        ));
        $faviconPath = $brandTheme['favicon_path'] ?? '';
        $defaultDescription = app(\App\Services\UiText::class)->get('seo.default_description');
        $validHotline = preg_match('/^\+?[0-9(). -]{9,20}$/', (string) $dealerHotline) && preg_match('/[0-9]{9,}/', (string) $dealerHotline);
        $phoneUrl = $validHotline ? 'tel:'.preg_replace('/[^0-9+]/', '', $dealerHotline) : null;
        $validZaloUrl = filter_var($dealerZalo, FILTER_VALIDATE_URL) && str_starts_with((string) $dealerZalo, 'https://');
    @endphp
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <style>
        :root {
            --page-bg: {{ $pageBgColor }};
            --page-bg-gradient: {{ $pageGradientEnabled ? "linear-gradient({$pageGradientDirection}, {$pageBgColor}, {$pageGradientEnd})" : $pageBgColor }};
            --text: {{ $mainTextColor }};
            --footer-bg: {{ $footerBgColor }};
            --navbar-bg: {{ $navbarBgColor }};
            --navbar-text: {{ contrastColor($navbarBgColor) }};
            --footer-text: {{ contrastColor($footerBgColor) }};
            --nav-border: {{ $navBorderColor }};
            --nav-hover: {{ $navHoverColor }};
            --nav-hover-text: {{ contrastColor($navHoverColor) }};
        }
    </style>
    <title>@yield('title', $siteName . ' | ' . $dealerName)</title>
    <meta name="description" content="@yield('meta_description', $defaultDescription)">
    <link rel="canonical" href="@yield('canonical', url()->current())">
    <meta property="og:title" content="@yield('title', $siteName . ' | ' . $dealerName)">
    <meta property="og:description" content="@yield('meta_description', $defaultDescription)">
    <meta property="og:type" content="@yield('og_type', 'website')">
    <meta property="og:url" content="@yield('canonical', url()->current())">
    @hasSection('og_image')
        <meta property="og:image" content="@yield('og_image')">
    @endif
    @if(filled($faviconPath))
        <link rel="icon" href="{{ Storage::disk('public')->url($faviconPath) }}">
    @endif
    @php
        $businessSchema = [
            '@context' => 'https://schema.org',
            '@type' => 'AutoDealer',
            'url' => url('/'),
            'areaServed' => $serviceRegions,
        ];
        foreach (['name' => $dealerName, 'telephone' => $dealerHotline, 'email' => $dealerEmail, 'address' => $dealerAddress] as $property => $value) {
            if (is_string($value) && $value !== '' && ! str_contains($value, '[')) {
                $businessSchema[$property] = $value;
            }
        }
        $mapUrl = $dealerMapsUrl;
        if (is_string($mapUrl) && filter_var($mapUrl, FILTER_VALIDATE_URL)) {
            $businessSchema['hasMap'] = $mapUrl;
        }
    @endphp
    @if(isset($businessSchema['name']))
        <script type="application/ld+json">{!! json_encode($businessSchema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) !!}</script>
    @endif
    @stack('meta')
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
@php
    $savedCookieConsent = session('cookie_consent', []);
    $headerTheme = is_array($siteTheme['header'] ?? null) ? $siteTheme['header'] : [];
    $footerTheme = is_array($siteTheme['footer'] ?? null) ? $siteTheme['footer'] : [];
    $floatingTheme = is_array($siteTheme['floating_contact'] ?? null) ? $siteTheme['floating_contact'] : [];
    $primaryColor = preg_match('/^#[0-9a-fA-F]{6}$/', (string) ($brandTheme['primary_color'] ?? '')) ? $brandTheme['primary_color'] : '#b71c1c';
    $primaryChannels = array_map('hexdec', str_split(ltrim($primaryColor, '#'), 2));
    $contrastWithWhite = static function (array $channels): float {
        $luminance = array_map(
            static function (int $channel): float {
                $value = $channel / 255;

                return $value <= 0.04045 ? $value / 12.92 : (($value + 0.055) / 1.055) ** 2.4;
            },
            $channels,
        );

        return 1.05 / (0.2126 * $luminance[0] + 0.7152 * $luminance[1] + 0.0722 * $luminance[2] + 0.05);
    };
    while ($contrastWithWhite($primaryChannels) < 4.5) {
        $primaryChannels = array_map(static fn (int $channel): int => (int) round($channel * 0.9), $primaryChannels);
    }
    $primaryColor = sprintf('#%02x%02x%02x', ...$primaryChannels);
    $secondaryColor = preg_match('/^#[0-9a-fA-F]{6}$/', (string) ($brandTheme['secondary_color'] ?? '')) ? $brandTheme['secondary_color'] : '#1f2937';
    $contentWidth = min(1920, max(960, (int) ($brandTheme['content_width'] ?? 1280)));
    $buttonRadius = match ($brandTheme['button_style'] ?? 'rounded') {
    'pill' => 999,
    'square' => 0,
    default => min(48, max(0, (int) ($brandTheme['border_radius'] ?? 12))),
    };
    $fontStack = match ($brandTheme['font'] ?? 'system') {
        'arial' => 'Arial, sans-serif',
        'georgia' => 'Georgia, serif',
        default => "'Be Vietnam Pro', ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif",
    };
    $announcementStart = empty($headerTheme['announcement_starts_at']) ? null : strtotime($headerTheme['announcement_starts_at']);
    $announcementEnd = empty($headerTheme['announcement_ends_at']) ? null : strtotime($headerTheme['announcement_ends_at']);
    $announcementVisible = ($headerTheme['announcement_enabled'] ?? false)
        && filled($headerTheme['announcement_text'] ?? null)
        && ($announcementStart === null || ($announcementStart !== false && $announcementStart <= time()))
        && ($announcementEnd === null || ($announcementEnd !== false && $announcementEnd >= time()));
    $logoPath = $brandTheme['logo_path'] ?? '';
    $logoAlt = $brandTheme['logo_alt'] ?? $dealerName;
    $trackingTheme = $siteTheme['tracking'] ?? [];
    $ga4Id = filled($trackingTheme['ga4_id'] ?? null) ? $trackingTheme['ga4_id'] : config('tracking.ga4_id');
    $gtmId = filled($trackingTheme['gtm_id'] ?? null) ? $trackingTheme['gtm_id'] : config('tracking.gtm_id');
    $metaPixelId = filled($trackingTheme['meta_pixel_id'] ?? null) ? $trackingTheme['meta_pixel_id'] : config('tracking.meta.pixel_id');
@endphp
<body x-data="cookieConsent"
    style="--site-brand: {{ $primaryColor }}; --site-secondary: {{ $secondaryColor }}; --site-radius: {{ $buttonRadius }}px; --site-width: {{ $contentWidth }}px; --site-font: {{ $fontStack }};"
    data-ga4-id="{{ $ga4Id }}"
    data-gtm-id="{{ $gtmId }}"
    data-meta-pixel-id="{{ $metaPixelId }}"
    data-lead-event-id="{{ session('lead_event_id', '') }}"
    data-cookie-consent-saved="{{ ($savedCookieConsent['version'] ?? null) === 1 ? '1' : '0' }}"
    data-cookie-consent-analytics="{{ ($savedCookieConsent['analytics'] ?? false) ? '1' : '0' }}"
    data-cookie-consent-marketing="{{ ($savedCookieConsent['marketing'] ?? false) ? '1' : '0' }}"
    data-cookie-consent-url="{{ route('cookie-consent.store') }}"
    data-cookie-save-error="@ui('cookie.save_error')"
    data-cookie-confirm-error="@ui('cookie.confirm_error')"
    data-cookie-session-only="@ui('cookie.session_only')"
>
    @if($announcementVisible)
        <aside class="bg-toyota-ink px-4 py-2 text-center text-sm font-semibold text-white">{{ $headerTheme['announcement_text'] }}</aside>
    @endif
    <a href="#main-content" class="sr-only focus:not-sr-only focus:fixed focus:left-3 focus:top-3 focus:z-50 focus:bg-white focus:p-3">@ui('common.skip_link')</a>
    @include('partials.navbar')

    @if(!empty($preview) && isset($page))
        <div class="sticky top-0 z-40 flex flex-wrap items-center justify-center gap-2 border-b border-amber-300 bg-amber-50 p-3 text-sm">
            <span class="font-semibold">@ui('preview.banner')</span>
            @foreach(['desktop', 'tablet', 'mobile'] as $viewportOption)
                <a class="button-secondary min-h-9 px-3 py-1" href="{{ route('pages.preview', ['page' => $page, 'viewport' => $viewportOption]) }}">@ui('preview.'.$viewportOption)</a>
            @endforeach
        </div>
    @endif

    <main id="main-content" class="@if(!empty($previewViewport)) preview-frame preview-frame--{{ $previewViewport }} @endif">
        @yield('content')
    </main>

    <footer class="site-footer mt-16 pb-12 pt-12 min-[1100px]:py-12">
        <div class="site-container grid gap-10 sm:grid-cols-2 lg:grid-cols-4">
            <div>
                <p class="text-lg font-bold">{{ $siteName }}</p>
                <p class="mt-3 text-sm leading-6 opacity-80">{{ $footerTheme['dealer_name'] ?? $dealerName }}</p>
                <p class="mt-2 text-sm leading-6 opacity-80">{{ $footerTheme['dealer_address'] ?? $dealerAddress }}</p>
            </div>
            @foreach(($footerTheme['columns'] ?? []) as $footerColumn)
                @if(is_array($footerColumn))
                    <div>
                        <h2 class="font-semibold">{{ $footerColumn['title'] ?? '' }}</h2>
                        <ul class="mt-3 space-y-2 text-sm">
                            @foreach(($footerColumn['links'] ?? []) as $footerLink)
                                @if(is_array($footerLink) && filled($footerLink['label'] ?? null) && preg_match('/^(https:\/\/|\/)(?!\/)/i', (string) ($footerLink['url'] ?? '')))
                                    <li><a href="{{ $footerLink['url'] }}">{{ $footerLink['label'] }}</a></li>
                                @endif
                            @endforeach
                            @if($loop->first)
                                <li><button type="button" class="touch-target hover:text-white" @click="visible = true">@ui('common.cookie_settings')</button></li>
                            @endif
                        </ul>
                    </div>
                @endif
            @endforeach
            @if(empty($footerTheme['columns']))
                <div>
                        <h2 class="font-semibold">@ui('common.footer_support')</h2>
                    <ul class="mt-3 space-y-2 text-sm">
                        @foreach(($footerTheme['links'] ?? []) as $footerLink)
                            @if(is_array($footerLink) && filled($footerLink['label'] ?? null) && preg_match('/^(https:\/\/|\/)(?!\/)/i', (string) ($footerLink['url'] ?? '')))
                                <li><a href="{{ $footerLink['url'] }}">{{ $footerLink['label'] }}</a></li>
                            @endif
                        @endforeach
                        <li><button type="button" class="touch-target hover:text-white" @click="visible = true">@ui('common.cookie_settings')</button></li>
                    </ul>
                </div>
            @endif
            <p x-show="storageError" x-text="storageError" role="status" class="mt-3 text-xs text-amber-300"></p>
            <div>
                <h2 class="font-semibold">@ui('common.service_area')</h2>
                <p class="mt-3 text-sm leading-6 opacity-80">{{ $footerTheme['service_area'] ?? $dealerTheme['service_area'] ?? '' }}</p>
                <p class="mt-2 text-sm leading-6 opacity-80">@ui('common.dealer_hours'): {{ $dealerHours }}</p>
            </div>
            <div>
                <h2 class="font-semibold">@ui('common.contact')</h2>
                @if($phoneUrl)<p class="mt-3 text-sm">@ui('common.hotline'): <a href="{{ $phoneUrl }}">{{ $dealerHotline }}</a></p>@endif
                @if($validZaloUrl)<p class="mt-2 text-sm">@ui('common.zalo'): <a href="{{ $dealerZalo }}" rel="noopener noreferrer">{{ $dealerZalo }}</a></p>@endif
                @if(filled($dealerEmail) && filter_var($dealerEmail, FILTER_VALIDATE_EMAIL))<p class="mt-2 text-sm">@ui('common.email'): <a href="mailto:{{ $dealerEmail }}">{{ $dealerEmail }}</a></p>@endif
            </div>
        </div>
        <div class="site-container mt-10 border-t border-current/20 pt-5 text-xs opacity-80">
            <p>{{ $footerTheme['legal_notice'] ?? '' }}</p>
            <p class="mt-2">{{ $footerTheme['copyright'] ?? '' }} {{ date('Y') }} {{ $dealerName }}</p>
        </div>
    </footer>

    <section
        x-show="visible"
        x-cloak
        class="fixed inset-x-3 bottom-3 z-[80] mx-auto max-w-3xl rounded-xl border border-slate-200 bg-white p-5 shadow-2xl sm:inset-x-6"
        aria-label="@ui('cookie.title')"
        aria-live="polite"
    >
        <h2 class="text-base font-bold text-slate-950">@ui('cookie.title')</h2>
        <p class="mt-2 text-sm leading-6 text-slate-600">
            @ui('cookie.description')
            <a class="font-semibold text-toyota underline" href="{{ route('pages.privacy') }}">@ui('common.privacy_policy')</a>.
        </p>
        <p x-show="error" x-text="error" role="alert" class="mt-3 text-sm text-red-700"></p>
        <div class="mt-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div class="space-y-2 text-sm">
                <label class="flex items-center gap-2">
                    <input type="checkbox" checked disabled class="rounded border-slate-300">
                    @ui('cookie.essential')
                </label>
                <label class="flex items-center gap-2">
                    <input type="checkbox" x-model="analytics" class="rounded border-slate-300">
                    @ui('cookie.analytics')
                </label>
                <label class="flex items-center gap-2">
                    <input type="checkbox" x-model="marketing" class="rounded border-slate-300">
                    @ui('cookie.marketing')
                </label>
            </div>
            <div class="flex flex-wrap gap-2">
                <button type="button" class="button-secondary" :disabled="saving" @click="save(false, false)">@ui('cookie.reject')</button>
                <button type="button" class="button-primary" :disabled="saving" @click="save(analytics, marketing)">@ui('cookie.save')</button>
                <button type="button" class="button-primary" :disabled="saving" @click="save(true, true)">@ui('cookie.accept_all')</button>
            </div>
        </div>
    </section>
</body>
</html>



