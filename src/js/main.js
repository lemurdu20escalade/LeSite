/**
 * Lemur Escalade - Main JavaScript
 *
 * @package Lemur
 */

// Import styles for Vite to process
import '../css/main.css';

// Import Alpine.js
import Alpine from 'alpinejs';
import collapse from '@alpinejs/collapse';
import focus from '@alpinejs/focus';

// Import components
import { siteHeader } from './components/site-header.js';
import { faqSearch, initFaqFilters } from './components/faq.js';
import { galleryLightbox, initGalleryFilters } from './components/gallery.js';

// Import modules
import { initLazyImages, observeNewImages } from './modules/lazy-images.js';
import { initLightbox } from './modules/lightbox.js';
import { initEmailObfuscator, observeEmails } from './modules/email-obfuscator.js';
import LemurAccessibility from './modules/accessibility.js';
import { initKanban } from './modules/kanban.js';

/**
 * Initialize Alpine.js
 */
function initAlpine() {
    // Register plugins
    Alpine.plugin(collapse);
    Alpine.plugin(focus);

    // Register components
    Alpine.data('siteHeader', siteHeader);
    Alpine.data('faqSearch', faqSearch);
    Alpine.data('galleryLightbox', galleryLightbox);

    // Start Alpine
    Alpine.start();
}

/**
 * Initialize the theme
 */
function init() {
    // Initialize Alpine.js before DOM ready (Alpine handles its own timing)
    initAlpine();

    // DOM ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', onDOMReady);
    } else {
        onDOMReady();
    }
}

/**
 * Called when DOM is ready
 */
function onDOMReady() {
    // Initialize components
    initSkipLink();
    initAutoSubmit();
    initFaqFilters();
    initGalleryFilters();

    // Initialize lazy image enhancements
    initLazyImages();

    // Observe dynamically added images (AJAX, infinite scroll)
    observeNewImages();

    // Initialize lightbox for galleries
    initLightbox();

    // Initialize email obfuscator
    initEmailObfuscator();
    observeEmails();

    // Initialize Kanban board (member area)
    initKanban();
}

/**
 * Initialize skip link functionality
 */
function initSkipLink() {
    const skipLink = document.querySelector('.skip-link');
    if (!skipLink) return;

    skipLink.addEventListener('click', (event) => {
        event.preventDefault();
        const href = skipLink.getAttribute('href');
        if (!href) return;

        const target = document.querySelector(href);
        if (target instanceof HTMLElement) {
            target.setAttribute('tabindex', '-1');
            target.focus();
        }
    });
}

/**
 * Initialize auto-submit inputs
 *
 * Inputs with [data-auto-submit] will submit their parent form on change.
 */
function initAutoSubmit() {
    document.querySelectorAll('[data-auto-submit]').forEach((input) => {
        input.addEventListener('change', () => {
            const form = input.closest('form');
            if (form) {
                form.submit();
            }
        });
    });
}

// Initialize
init();

// HMR support
if (import.meta.hot) {
    import.meta.hot.accept();
}
