/**
 * Lightbox Module
 *
 * Uses GLightbox for gallery lightbox with slideshow functionality.
 *
 * @package Lemur
 */

import GLightbox from 'glightbox';
import 'glightbox/dist/css/glightbox.min.css';

let lightboxInstance = null;

/**
 * Initialize GLightbox on gallery links
 *
 * Looks for links with [data-lightbox] attribute.
 */
export function initLightbox() {
    // Initialize on elements with data-lightbox attribute
    const galleryLinks = document.querySelectorAll('[data-lightbox]');

    if (galleryLinks.length === 0) {
        return;
    }

    // Destroy existing instance if any
    if (lightboxInstance) {
        lightboxInstance.destroy();
    }

    // Create new GLightbox instance
    lightboxInstance = GLightbox({
        selector: '[data-lightbox]',
        touchNavigation: true,
        loop: true,
        autoplayVideos: true,
        openEffect: 'zoom',
        closeEffect: 'fade',
        slideEffect: 'slide',
        moreLength: 0,
        slideExtraAttributes: {
            preload: 'auto',
        },
        // French translations
        zoomable: true,
        draggable: true,
        dragToleranceX: 40,
        dragToleranceY: 65,
        // Slideshow settings
        autofocusVideos: false,
        // Custom close button text
        svg: {
            close: '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>',
            next: '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"></polyline></svg>',
            prev: '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"></polyline></svg>',
        },
    });

    return lightboxInstance;
}

/**
 * Destroy lightbox instance
 */
export function destroyLightbox() {
    if (lightboxInstance) {
        lightboxInstance.destroy();
        lightboxInstance = null;
    }
}

/**
 * Refresh lightbox (useful after dynamic content changes)
 */
export function refreshLightbox() {
    if (lightboxInstance) {
        lightboxInstance.reload();
    } else {
        initLightbox();
    }
}
