/**
 * Site Header Component
 *
 * Handles sticky header behavior with hide/show on scroll direction.
 * Manages mobile menu toggle and keyboard accessibility.
 *
 * @package Lemur
 */

/**
 * Alpine.js component for site header
 *
 * @returns {Object} Alpine component data
 */
export function siteHeader() {
    return {
        // State
        mobileMenuOpen: false,
        isHidden: false,
        lastScrollY: 0,
        scrollThreshold: 100,
        ticking: false,

        // Bound handlers for proper cleanup
        _boundOnScroll: null,
        _boundOnResize: null,
        _boundOnKeydown: null,
        _focusTrapController: null,

        /**
         * Header bindings for Alpine x-bind
         */
        get headerBindings() {
            return {
                ':class': `{
                    'site-header--hidden': isHidden,
                    'site-header--mobile-open': mobileMenuOpen
                }`,
            };
        },

        /**
         * Initialize component
         */
        init() {
            // Create bound handlers for proper cleanup
            this._boundOnScroll = this.onScroll.bind(this);
            this._boundOnResize = this.onResize.bind(this);
            this._boundOnKeydown = this.onKeydown.bind(this);

            // Listen for scroll
            window.addEventListener('scroll', this._boundOnScroll, { passive: true });

            // Close mobile menu on resize to desktop
            window.addEventListener('resize', this._boundOnResize);

            // Close mobile menu with Escape key
            document.addEventListener('keydown', this._boundOnKeydown);

            // Handle mobile menu state changes
            this.$watch('mobileMenuOpen', (isOpen) => {
                if (isOpen) {
                    document.body.style.overflow = 'hidden';
                    this.setupFocusTrap();
                } else {
                    document.body.style.overflow = '';
                    this.removeFocusTrap();
                }
            });
        },

        /**
         * Handle resize event
         */
        onResize() {
            if (window.innerWidth >= 1024 && this.mobileMenuOpen) {
                this.mobileMenuOpen = false;
            }
        },

        /**
         * Handle keydown event
         */
        onKeydown(event) {
            if (event.key === 'Escape' && this.mobileMenuOpen) {
                this.mobileMenuOpen = false;
                // Focus back to burger button
                const burger = this.$refs.burger;
                if (burger instanceof HTMLElement) {
                    burger.focus();
                }
            }
        },

        /**
         * Handle scroll event
         */
        onScroll() {
            if (!this.ticking) {
                window.requestAnimationFrame(() => {
                    this.handleScroll();
                    this.ticking = false;
                });
                this.ticking = true;
            }
        },

        /**
         * Process scroll direction and toggle header visibility
         */
        handleScroll() {
            const currentScrollY = window.scrollY;

            // Don't hide if mobile menu is open
            if (this.mobileMenuOpen) {
                this.lastScrollY = currentScrollY;
                return;
            }

            // Don't hide at the top of the page
            if (currentScrollY < this.scrollThreshold) {
                this.isHidden = false;
                this.lastScrollY = currentScrollY;
                return;
            }

            // Scrolling down: hide header
            if (currentScrollY > this.lastScrollY + 10) {
                this.isHidden = true;
            }
            // Scrolling up: show header
            else if (currentScrollY < this.lastScrollY - 10) {
                this.isHidden = false;
            }

            this.lastScrollY = currentScrollY;
        },

        /**
         * Setup focus trap within mobile menu
         */
        setupFocusTrap() {
            const mobileMenu = this.$refs.mobileMenu;
            if (!mobileMenu) return;

            // Use AbortController for clean listener removal
            this._focusTrapController = new AbortController();

            const focusableElements = mobileMenu.querySelectorAll(
                'a[href], button, textarea, input, select, [tabindex]:not([tabindex="-1"])'
            );

            if (focusableElements.length === 0) return;

            const firstElement = focusableElements[0];
            const lastElement = focusableElements[focusableElements.length - 1];

            // Focus first element
            if (firstElement instanceof HTMLElement) {
                firstElement.focus();
            }

            // Handle tab key for focus trap
            mobileMenu.addEventListener('keydown', (event) => {
                if (event.key !== 'Tab') return;

                if (event.shiftKey) {
                    // Shift + Tab
                    if (document.activeElement === firstElement) {
                        event.preventDefault();
                        if (lastElement instanceof HTMLElement) {
                            lastElement.focus();
                        }
                    }
                } else {
                    // Tab
                    if (document.activeElement === lastElement) {
                        event.preventDefault();
                        if (firstElement instanceof HTMLElement) {
                            firstElement.focus();
                        }
                    }
                }
            }, { signal: this._focusTrapController.signal });
        },

        /**
         * Remove focus trap
         */
        removeFocusTrap() {
            if (this._focusTrapController) {
                this._focusTrapController.abort();
                this._focusTrapController = null;
            }
        },

        /**
         * Cleanup on destroy
         */
        destroy() {
            window.removeEventListener('scroll', this._boundOnScroll);
            window.removeEventListener('resize', this._boundOnResize);
            document.removeEventListener('keydown', this._boundOnKeydown);
            this.removeFocusTrap();
        },
    };
}
