export function registerNavbar(Alpine) {
    const setupVehicleMegaMenus = () => {
        document.querySelectorAll('[data-vehicle-mega]').forEach((menu) => {
            const tabs = [...menu.querySelectorAll('[data-vehicle-tab]')];
            const grid = menu.querySelector('[data-vehicle-grid]');
            const cards = [...menu.querySelectorAll('[data-vehicle-card]')];
            let filterTimer = null;

            tabs.forEach((tab) => {
                tab.addEventListener('click', () => {
                    const category = tab.dataset.vehicleTab;
                    tabs.forEach((candidate) => {
                        const selected = candidate === tab;
                        candidate.classList.toggle('is-active', selected);
                        candidate.setAttribute('aria-pressed', selected ? 'true' : 'false');
                    });

                    grid.classList.add('is-fading');
                    window.clearTimeout(filterTimer);
                    filterTimer = window.setTimeout(() => {
                        cards.forEach((card) => {
                            const matches = category === 'all' || (card.dataset.categories || '').split(/\s+/).includes(category);
                            const price = card.querySelector('[data-vehicle-price]');
                            const hybridBadge = card.querySelector('[data-hybrid-badge]');
                            card.hidden = !matches;
                            card.setAttribute('aria-hidden', matches ? 'false' : 'true');
                            if (hybridBadge) {
                                hybridBadge.hidden = category === 'all' || category === 'hybrid' || card.dataset.hasHybrid !== 'true';
                            }
                            if (price) {
                                price.textContent = category === 'hybrid'
                                    ? (price.dataset.priceHybrid || price.dataset.priceStandard || '')
                                    : (price.dataset.priceStandard || '');
                            }
                        });
                        requestAnimationFrame(() => grid.classList.remove('is-fading'));
                    }, 75);
                });
            });
        });
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', setupVehicleMegaMenus, { once: true });
    } else {
        setupVehicleMegaMenus();
    }

    Alpine.data('siteNavbar', () => ({
        mobileOpen: false,
        contactOpen: false,
        contactTimer: null,
        contactHideTimer: null,
        quoteOpen: false,
        scrolled: false,
        quoteTrigger: null,
        contactOutsideHandler: null,

        init() {
            this.updateScrollState();
            window.addEventListener('scroll', this.updateScrollState.bind(this), { passive: true });
            this.contactOutsideHandler = (event) => {
                const widget = this.$refs.contactWidget;
                if (this.contactOpen && widget && !widget.contains(event.target)) {
                    this.closeContactMenu();
                }
            };
            document.addEventListener('pointerdown', this.contactOutsideHandler, true);
            window.addEventListener('keydown', (event) => {
                if (event.key === 'Escape') {
                    this.closeQuoteModal();
                    this.closeMenu();
                    this.closeContactMenu();
                }
            });
        },

        destroy() {
            this.clearContactTimer();
            this.clearContactHideTimer();
            if (this.contactOutsideHandler) {
                document.removeEventListener('pointerdown', this.contactOutsideHandler, true);
            }
        },

        updateScrollState() {
            this.scrolled = window.scrollY > 80;
        },

        toggleMenu() {
            this.mobileOpen = !this.mobileOpen;
            this.syncScrollLock();
            if (this.mobileOpen) {
                this.$nextTick(() => this.$refs.mobileClose?.focus());
            } else {
                this.$nextTick(() => this.$refs.mobileTrigger?.focus());
            }
        },

        closeMenu() {
            if (!this.mobileOpen) return;
            this.mobileOpen = false;
            this.syncScrollLock();
            this.$nextTick(() => this.$refs.mobileTrigger?.focus());
        },

        toggleContactMenu() {
            if (this.contactOpen) {
                this.closeContactMenu();
                return;
            }

            this.clearContactHideTimer();
            this.contactOpen = true;
            window.toyotaTrackConversion?.('contact_menu_open', { placement: 'floating-contact' });
            const menu = this.$refs.contactMenu;
            if (menu) {
                menu.hidden = false;
                menu.setAttribute('aria-hidden', 'false');
                requestAnimationFrame(() => menu.classList.add('is-open'));
            }

            this.clearContactTimer();
            this.contactTimer = window.setTimeout(() => this.closeContactMenu(), 7000);
            this.$nextTick(() => menu?.querySelector('a, button')?.focus());
        },

        closeContactMenu(restoreFocus = true) {
            this.clearContactTimer();
            if (!this.contactOpen) return;
            this.contactOpen = false;

            const menu = this.$refs.contactMenu;
            if (menu) {
                menu.classList.remove('is-open');
                menu.setAttribute('aria-hidden', 'true');
                this.clearContactHideTimer();
                this.contactHideTimer = window.setTimeout(() => {
                    menu.hidden = true;
                    this.contactHideTimer = null;
                }, 180);
            }

            if (restoreFocus) {
                this.$nextTick(() => this.$refs.contactTrigger?.focus());
            }
        },

        clearContactTimer() {
            if (this.contactTimer !== null) {
                window.clearTimeout(this.contactTimer);
                this.contactTimer = null;
            }
        },

        clearContactHideTimer() {
            if (this.contactHideTimer !== null) {
                window.clearTimeout(this.contactHideTimer);
                this.contactHideTimer = null;
            }
        },

        trackContact(type) {
            window.toyotaTrackConversion?.(
                'contact_click',
                { contact_type: type, placement: 'floating-contact' },
                type === 'quote' ? 'Lead' : 'Contact',
            );
        },

        openQuoteModal(trigger = null) {
            this.clearContactTimer();
            this.quoteTrigger = trigger || document.activeElement;
            this.mobileOpen = false;
            this.contactOpen = false;
            this.quoteOpen = true;
            this.syncScrollLock();
            this.$nextTick(() => this.$refs.quoteDialog?.querySelector('input:not([type="hidden"]), select, button')?.focus());
        },

        closeQuoteModal() {
            if (!this.quoteOpen) return;
            this.quoteOpen = false;
            this.syncScrollLock();
            this.$nextTick(() => this.quoteTrigger?.focus());
        },

        syncScrollLock() {
            document.documentElement.classList.toggle('navbar-menu-open', this.mobileOpen || this.quoteOpen);
        },

        trapQuoteFocus(event) {
            if (!this.quoteOpen || event.key !== 'Tab') return;

            const focusable = [...this.$refs.quoteDialog.querySelectorAll('a[href], input:not([disabled]), select:not([disabled]), textarea:not([disabled]), button:not([disabled])')]
                .filter((element) => element.getClientRects().length > 0);
            const first = focusable[0];
            const last = focusable[focusable.length - 1];

            if (event.shiftKey && document.activeElement === first) {
                event.preventDefault();
                last?.focus();
            } else if (!event.shiftKey && document.activeElement === last) {
                event.preventDefault();
                first?.focus();
            }
        },

        trapMenuFocus(event) {
            if (!this.mobileOpen || event.key !== 'Tab') return;

            const focusable = [...this.$refs.mobilePanel.querySelectorAll('a[href], button:not([disabled]), summary')]
                .filter((element) => element.getClientRects().length > 0);
            const first = focusable[0];
            const last = focusable[focusable.length - 1];

            if (event.shiftKey && document.activeElement === first) {
                event.preventDefault();
                last?.focus();
            } else if (!event.shiftKey && document.activeElement === last) {
                event.preventDefault();
                first?.focus();
            }
        },
    }));
}
