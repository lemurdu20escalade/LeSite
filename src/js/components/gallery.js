/**
 * Gallery Component
 *
 * Alpine.js lightbox component and vanilla JS album filters.
 *
 * @package Lemur
 */

/**
 * Gallery Lightbox Alpine.js component
 *
 * Provides full-screen image viewing with keyboard and touch navigation.
 *
 * @returns {object} Alpine.js component data
 */
export function galleryLightbox() {
    return {
        isOpen: false,
        currentIndex: 0,
        loading: false,
        images: [],
        touchStartX: 0,

        /**
         * Initialize the lightbox
         */
        init() {
            // Load images data from JSON script
            const dataEl = document.getElementById('gallery-data');
            if (dataEl) {
                try {
                    this.images = JSON.parse(dataEl.textContent);
                } catch (e) {
                    console.error('[Gallery] Failed to parse gallery data:', e);
                    this.images = [];
                }
            }

            // Block body scroll when lightbox is open
            this.$watch('isOpen', (value) => {
                document.body.style.overflow = value ? 'hidden' : '';
            });
        },

        /**
         * Get current image data
         *
         * @returns {object} Current image object
         */
        get currentImage() {
            return this.images[this.currentIndex] || { full: '', alt: '', caption: '' };
        },

        /**
         * Open lightbox at specified index
         *
         * @param {number} index Image index to display
         */
        openLightbox(index) {
            this.currentIndex = index;
            this.loading = true;
            this.isOpen = true;

            // Preload the image
            this.preloadImage();
        },

        /**
         * Close the lightbox
         */
        closeLightbox() {
            this.isOpen = false;
        },

        /**
         * Navigate to next image
         */
        nextImage() {
            if (this.currentIndex < this.images.length - 1) {
                this.loading = true;
                this.currentIndex++;
                this.preloadImage();
            }
        },

        /**
         * Navigate to previous image
         */
        prevImage() {
            if (this.currentIndex > 0) {
                this.loading = true;
                this.currentIndex--;
                this.preloadImage();
            }
        },

        /**
         * Preload current image
         */
        preloadImage() {
            const img = new Image();
            img.onload = () => {
                this.loading = false;
            };
            img.onerror = () => {
                this.loading = false;
            };
            img.src = this.currentImage.full;
        },

        /**
         * Handle touch start for swipe navigation
         *
         * @param {TouchEvent} e Touch event
         */
        handleTouchStart(e) {
            this.touchStartX = e.touches[0].clientX;
        },

        /**
         * Handle touch end for swipe navigation
         *
         * @param {TouchEvent} e Touch event
         */
        handleTouchEnd(e) {
            const touchEndX = e.changedTouches[0].clientX;
            const diff = this.touchStartX - touchEndX;
            const threshold = 50;

            if (Math.abs(diff) > threshold) {
                if (diff > 0) {
                    this.nextImage();
                } else {
                    this.prevImage();
                }
            }
        },
    };
}

/**
 * Initialize gallery album filters
 *
 * Sets up click handlers for album filter buttons.
 */
export function initGalleryFilters() {
    const filterButtons = document.querySelectorAll('.gallery-filter');
    const galleryItems = document.querySelectorAll('.gallery-item');

    if (filterButtons.length === 0) {
        return;
    }

    filterButtons.forEach((button) => {
        button.addEventListener('click', () => {
            const filter = button.dataset.filter;

            // Update button states
            filterButtons.forEach((btn) => {
                btn.classList.remove('gallery-filter--active');
                btn.setAttribute('aria-pressed', 'false');
            });
            button.classList.add('gallery-filter--active');
            button.setAttribute('aria-pressed', 'true');

            // Show/hide gallery items
            galleryItems.forEach((item) => {
                if (filter === 'all' || item.dataset.album === filter) {
                    item.style.display = '';
                } else {
                    item.style.display = 'none';
                }
            });
        });
    });
}
