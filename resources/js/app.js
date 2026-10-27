import Alpine from 'alpinejs';
import { registerNavbar } from './navbar.js';

window.Alpine = Alpine;
registerNavbar(Alpine);

const consentKey = 'toyota-kontum-cookie-consent-v1';

function readConsent() {
    let localConsent = null;

    try {
        const value = JSON.parse(window.localStorage.getItem(consentKey));

        if (value && value.version === 1) {
            localConsent = { analytics: value.analytics === true, marketing: value.marketing === true };
        }
    } catch {
        localConsent = null;
    }

    const body = document.body;
    if (body.dataset.cookieConsentSaved === '1') {
        const sessionConsent = {
            analytics: body.dataset.cookieConsentAnalytics === '1',
            marketing: body.dataset.cookieConsentMarketing === '1',
        };

        if (!localConsent) {
            return { ...sessionConsent, confirmed: true };
        }

        return {
            ...localConsent,
            confirmed: localConsent.analytics === sessionConsent.analytics
                && localConsent.marketing === sessionConsent.marketing,
        };
    }

    return localConsent ? { ...localConsent, confirmed: false } : null;
}

async function saveConsent(consent) {
    const response = await fetch(document.body.dataset.cookieConsentUrl, {
        method: 'POST',
        credentials: 'same-origin',
        headers: {
            Accept: 'application/json',
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
        },
        body: JSON.stringify({ version: 1, ...consent }),
    });

    if (!response.ok) {
        throw new Error(document.body.dataset.cookieSaveError);
    }
}

function applyTracking(consent) {
    window.toyotaConsentState = consent;
    const body = document.body;
    const gtmId = body.dataset.gtmId;
    const ga4Id = body.dataset.ga4Id;
    const pixelId = body.dataset.metaPixelId;
    const consentMode = {
        analytics_storage: consent.analytics ? 'granted' : 'denied',
        ad_storage: consent.marketing ? 'granted' : 'denied',
        ad_user_data: consent.marketing ? 'granted' : 'denied',
        ad_personalization: consent.marketing ? 'granted' : 'denied',
    };

    if ((consent.analytics || consent.marketing) && !window.toyotaGoogleInitialized) {
        window.dataLayer = window.dataLayer || [];
        window.gtag = window.gtag || function () { window.dataLayer.push(arguments); };
        window.gtag('consent', 'default', { ...consentMode, wait_for_update: 500 });
        window.toyotaGoogleInitialized = true;

        if (/^GTM-[A-Z0-9]+$/.test(gtmId || '')) {
            window.dataLayer.push({ 'gtm.start': new Date().getTime(), event: 'gtm.js' });
            const script = document.createElement('script');
            script.async = true;
            script.src = `https://www.googletagmanager.com/gtm.js?id=${encodeURIComponent(gtmId)}`;
            document.head.appendChild(script);
        } else if (consent.analytics && /^G-[A-Z0-9]+$/.test(ga4Id || '')) {
            const script = document.createElement('script');
            script.async = true;
            script.src = `https://www.googletagmanager.com/gtag/js?id=${encodeURIComponent(ga4Id)}`;
            document.head.appendChild(script);
            window.gtag('js', new Date());
            window.gtag('config', ga4Id);
        }
    } else if (window.toyotaGoogleInitialized) {
        window.gtag('consent', 'update', consentMode);
    }

    if (consent.marketing && /^\d+$/.test(pixelId || '') && !window.toyotaMetaInitialized) {
        (function (f, b, e, v, n, t, s) {
            if (f.fbq) return;
            n = f.fbq = function () {
                n.callMethod ? n.callMethod.apply(n, arguments) : n.queue.push(arguments);
            };
            if (!f._fbq) f._fbq = n;
            n.push = n;
            n.loaded = true;
            n.version = '2.0';
            n.queue = [];
            t = b.createElement(e);
            t.async = true;
            t.src = v;
            s = b.getElementsByTagName(e)[0];
            s.parentNode.insertBefore(t, s);
        })(window, document, 'script', 'https://connect.facebook.net/en_US/fbevents.js');

        window.fbq('consent', 'grant');
        window.fbq('init', pixelId);
        window.fbq('track', 'PageView');

        const eventId = body.dataset.leadEventId;
        if (eventId) {
            window.fbq('track', 'Lead', {}, { eventID: eventId });
        }

        window.toyotaMetaInitialized = true;
    } else if (window.toyotaMetaInitialized) {
        window.fbq('consent', consent.marketing ? 'grant' : 'revoke');
    }

    const eventId = body.dataset.leadEventId;
    if (consent.analytics && eventId && !window.toyotaAnalyticsLeadSent) {
        if (/^GTM-[A-Z0-9]+$/.test(gtmId || '')) {
            window.dataLayer.push({ event: 'generate_lead', event_id: eventId });
        } else if (/^G-[A-Z0-9]+$/.test(ga4Id || '')) {
            window.gtag('event', 'generate_lead', { event_id: eventId });
        }

        window.toyotaAnalyticsLeadSent = true;
    }
}

// Gửi sự kiện CRO sau khi khách đã đồng ý analytics hoặc marketing.
window.toyotaTrackConversion = (eventName, parameters = {}, metaEvent = null) => {
    const consent = window.toyotaConsentState;
    if (!consent || (!consent.analytics && !consent.marketing)) return;

    const safeParameters = Object.fromEntries(
        Object.entries(parameters).filter(([, value]) => ['string', 'number', 'boolean'].includes(typeof value)),
    );

    window.dataLayer = window.dataLayer || [];
    window.dataLayer.push({ event: eventName, ...safeParameters });

    if (consent.analytics && typeof window.gtag === 'function') {
        window.gtag('event', eventName, safeParameters);
    }

    if (consent.marketing && typeof window.fbq === 'function') {
        if (metaEvent) window.fbq('track', metaEvent, safeParameters);
        else window.fbq('trackCustom', eventName, safeParameters);
    }
};

Alpine.data('cookieConsent', () => ({
    visible: true,
    analytics: false,
    marketing: false,
    error: '',
    saving: false,
    storageError: '',

    init() {
        const saved = readConsent();
        if (saved) {
            this.analytics = saved.analytics;
            this.marketing = saved.marketing;
            this.visible = !saved.confirmed;

            if (saved.confirmed) {
                applyTracking(saved);
            } else {
                this.error = document.body.dataset.cookieConfirmError;
            }
        }
    },

    async save(analytics, marketing) {
        await this.persist({ analytics, marketing });
    },

    async persist(consent) {
        if (this.saving) {
            return;
        }

        this.analytics = consent.analytics;
        this.marketing = consent.marketing;
        this.error = '';
        this.storageError = '';
        this.saving = true;

        try {
            window.localStorage.setItem(consentKey, JSON.stringify({ version: 1, ...consent }));
        } catch {
            this.storageError = document.body.dataset.cookieSessionOnly;
        }

        applyTracking(consent);
        window.dispatchEvent(new CustomEvent('toyota-cookie-consent-updated', { detail: consent }));

        try {
            await saveConsent(consent);
            this.visible = false;
        } catch (error) {
            this.visible = true;
            this.error = error instanceof Error ? error.message : document.body.dataset.cookieSaveError;
        } finally {
            this.saving = false;
        }
    },
}));

Alpine.data('leadTrackingConsent', () => ({
    marketingConsent: false,
    sending: false,

    init() {
        const consent = readConsent();
        this.marketingConsent = consent?.marketing === true && consent.confirmed === true;
        window.addEventListener('toyota-cookie-consent-updated', (event) => {
            this.marketingConsent = event.detail.marketing === true;
        });
    },
}));

Alpine.data('vehicleConfigurator', (variants, initialVariant, initialColor, urls) => ({
    variants,
    selectedIndex: 0,
    selectedColorSlug: null,
    fallbackImage: urls.fallbackImage || null,
    imageLoading: false,

    init() {
        const requestedIndex = this.variants.findIndex((variant) => variant.slug === initialVariant);
        this.selectedIndex = requestedIndex >= 0 ? requestedIndex : 0;

        const colors = this.variant?.colors || [];
        this.selectedColorSlug = colors.some((color) => color.slug === initialColor)
            ? initialColor
            : (colors.find((color) => color.image)?.slug || colors[0]?.slug || null);

        window.toyotaTrackConversion?.('view_vehicle', {
            content_type: 'vehicle',
            vehicle: window.location.pathname.split('/').filter(Boolean).slice(-1)[0] || '',
        }, 'ViewContent');

        this.variants.flatMap((variant) => variant.colors || []).forEach((color) => {
            if (color.image) {
                const image = new Image();
                image.src = color.image;
            }
        });

        this.syncSelection();
    },

    get variant() {
        return this.variants[this.selectedIndex] || null;
    },

    get color() {
        return this.variant?.colors?.find((item) => item.slug === this.selectedColorSlug) || null;
    },

    get totalPrice() {
        return this.variant ? Number(this.variant.listPrice) + Number(this.color?.surcharge || 0) : null;
    },

    formatMoney(value) {
        return new Intl.NumberFormat('vi-VN').format(Number(value) || 0);
    },

    selectVariant(index) {
        this.selectedIndex = index;
        const colors = this.variant?.colors || [];

        if (!colors.some((color) => color.slug === this.selectedColorSlug)) {
            this.selectedColorSlug = colors[0]?.slug || null;
        }

        this.imageLoading = Boolean(this.color?.image);
        this.syncSelection();
        this.trackSelection();
    },

    selectColor(slug) {
        const next = this.variant?.colors?.find((color) => color.slug === slug);
        if (!next) return;

        this.selectedColorSlug = slug;
        this.imageLoading = Boolean(next.image);
        this.syncSelection();
        this.trackSelection();
    },

    trackSelection() {
        window.toyotaTrackConversion?.('vehicle_selection', {
            vehicle: window.location.pathname.split('/').filter(Boolean).slice(-1)[0] || '',
            variant: this.variant?.slug || '',
            color: this.color?.slug || '',
        });
    },

    imageLoaded() {
        this.imageLoading = false;
    },

    syncSelection() {
        const url = new URL(window.location.href);
        if (this.variant?.slug) url.searchParams.set('variant', this.variant.slug);
        else url.searchParams.delete('variant');
        if (this.color?.slug) url.searchParams.set('color', this.color.slug);
        else url.searchParams.delete('color');
        window.history.replaceState({}, '', url);

        const detail = {
            variant: this.variant,
            color: this.color,
            totalPrice: this.totalPrice,
        };
        window.dispatchEvent(new CustomEvent('vehicle-selection', { detail }));

        document.querySelectorAll('[data-vehicle-config-tool]').forEach((link) => {
            const type = link.dataset.vehicleConfigTool;
            const base = type === 'installment' ? urls.installment : urls.onRoad;
            const calculator = new URL(base, window.location.origin);
            if (detail.variant?.id) calculator.searchParams.set('variant_id', detail.variant.id);
            if (detail.totalPrice) calculator.searchParams.set('price', detail.totalPrice);
            if (type === 'on-road' && link.dataset.region) calculator.searchParams.set('region', link.dataset.region);
            link.href = calculator.toString();
        });
    },

    quoteUrl(type) {
        const url = new URL(urls.lead, window.location.origin);
        url.searchParams.set('type', type);
        url.searchParams.set('vehicle', urls.vehicleSlug);
        if (this.variant?.slug) url.searchParams.set('variant', this.variant.slug);
        if (this.color?.slug) url.searchParams.set('color', this.color.slug);
        return url.toString();
    },

    calculatorUrl(type) {
        const base = type === 'installment' ? urls.installment : urls.onRoad;
        const url = new URL(base, window.location.origin);
        if (this.variant?.id) url.searchParams.set('variant_id', this.variant.id);
        if (this.totalPrice) url.searchParams.set('price', this.totalPrice);
        return url.toString();
    },
}));

Alpine.data('variantSpecPanel', (initialSpecs = {}, initialName = '') => ({
    specs: initialSpecs,
    selectedVariantName: initialName,

    init() {
        window.addEventListener('vehicle-selection', (event) => {
            this.specs = event.detail.variant?.specs || {};
            this.selectedVariantName = event.detail.variant?.name || '';
        });
    },
}));

Alpine.data('variantGallery', (initialImage = null) => ({
    image: initialImage,

    init() {
        window.addEventListener('vehicle-selection', (event) => {
            this.image = event.detail.color?.image || null;
        });
    },
}));

Alpine.data('vehicleGalleryFilter', () => ({
    category: 'all',

    hasVisibleImages() {
        return this.category === 'all'
            ? this.$el.querySelectorAll('[data-gallery-category]').length > 0
            : [...this.$el.querySelectorAll('[data-gallery-category]')]
                .some((image) => image.dataset.galleryCategory === this.category);
    },
}));

Alpine.data('heroCarousel', (slideCount) => ({
    active: 0,
    reducedMotion: window.matchMedia('(prefers-reduced-motion: reduce)').matches,
    timer: null,

    init() {
        if (!this.reducedMotion && slideCount > 1) {
            this.startTimer();
        }
    },

    next() {
        this.active = (this.active + 1) % slideCount;
    },

    previous() {
        this.active = (this.active - 1 + slideCount) % slideCount;
    },

    goTo(index) {
        this.active = index;
    },

    startTimer() {
        this.stopTimer();
        this.timer = window.setInterval(() => this.next(), 8000);
    },

    stopTimer() {
        if (this.timer !== null) {
            window.clearInterval(this.timer);
            this.timer = null;
        }
    },

    destroy() {
        this.stopTimer();
    },
}));

Alpine.data('heroImageCarousel', (slideCount, intervalMs = 6000) => ({
    active: 0,
    reducedMotion: window.matchMedia('(prefers-reduced-motion: reduce)').matches,
    timer: null,

    init() {
        if (!this.reducedMotion && slideCount > 1) {
            this.startTimer();
        }
    },

    next() {
        this.goTo((this.active + 1) % slideCount);
    },

    previous() {
        this.goTo((this.active - 1 + slideCount) % slideCount);
    },

    goTo(index) {
        this.active = index;
        this.$refs.heroImageTrack.scrollTo({
            left: this.$refs.heroImageTrack.clientWidth * index,
            behavior: this.reducedMotion ? 'auto' : 'smooth',
        });
    },

    updateActive() {
        this.active = Math.round(this.$refs.heroImageTrack.scrollLeft / this.$refs.heroImageTrack.clientWidth);
    },

    startTimer() {
        this.stopTimer();
        this.timer = window.setInterval(() => this.next(), intervalMs);
    },

    stopTimer() {
        if (this.timer !== null) {
            window.clearInterval(this.timer);
            this.timer = null;
        }
    },

    destroy() {
        this.stopTimer();
    },
}));

function updatePromotionCountdowns() {
    document.querySelectorAll('[data-countdown]').forEach((element) => {
        const endsAt = Date.parse(element.dataset.countdown || '');
        if (!Number.isFinite(endsAt)) {
            element.textContent = '';
            return;
        }

        const remaining = Math.max(0, endsAt - Date.now());
        if (remaining === 0) {
            element.textContent = element.dataset.expiredLabel || '';
            return;
        }

        const days = Math.floor(remaining / 86400000);
        const hours = Math.floor((remaining % 86400000) / 3600000);
        const minutes = Math.floor((remaining % 3600000) / 60000);
        const seconds = Math.floor((remaining % 60000) / 1000);
        element.textContent = [
            days ? `${days} ${element.dataset.daysLabel}` : '',
            hours ? `${hours} ${element.dataset.hoursLabel}` : '',
            minutes ? `${minutes} ${element.dataset.minutesLabel}` : '',
            `${seconds} ${element.dataset.secondsLabel}`,
        ].filter(Boolean).join(' ');
    });
}

updatePromotionCountdowns();
window.setInterval(updatePromotionCountdowns, 1000);

Alpine.start();

