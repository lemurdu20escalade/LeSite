<?php
/**
 * Lightbox Component
 *
 * Accessible full-screen image lightbox with keyboard navigation and touch support.
 * Requires Alpine.js galleryLightbox() component to be initialized.
 *
 * @package Lemur
 */

declare(strict_types=1);

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}
?>

<div
    class="lightbox"
    x-show="isOpen"
    x-cloak
    x-trap.inert.noscroll="isOpen"
    x-transition:enter="lightbox--enter"
    x-transition:enter-start="lightbox--enter-start"
    x-transition:enter-end="lightbox--enter-end"
    x-transition:leave="lightbox--leave"
    x-transition:leave-start="lightbox--leave-start"
    x-transition:leave-end="lightbox--leave-end"
    @keydown.escape.window="closeLightbox()"
    @keydown.arrow-left.window="prevImage()"
    @keydown.arrow-right.window="nextImage()"
    role="dialog"
    aria-modal="true"
    :aria-hidden="(!isOpen).toString()"
    aria-label="<?php esc_attr_e('Galerie photo plein écran', 'lemur'); ?>"
>
    <!-- Backdrop -->
    <div
        class="lightbox__backdrop"
        @click="closeLightbox()"
    ></div>

    <!-- Container -->
    <div class="lightbox__container">
        <!-- Close Button -->
        <button
            type="button"
            class="lightbox__close"
            @click="closeLightbox()"
            aria-label="<?php esc_attr_e('Fermer la galerie', 'lemur'); ?>"
        >
            <?php lemur_the_ui_icon('x', ['width' => 24, 'height' => 24]); ?>
        </button>

        <!-- Previous Button -->
        <button
            type="button"
            class="lightbox__nav lightbox__nav--prev"
            @click="prevImage()"
            :disabled="currentIndex === 0"
            aria-label="<?php esc_attr_e('Image précédente', 'lemur'); ?>"
        >
            <?php lemur_the_ui_icon('chevron-left', ['width' => 32, 'height' => 32]); ?>
        </button>

        <!-- Image Content -->
        <div
            class="lightbox__content"
            @touchstart="handleTouchStart($event)"
            @touchend="handleTouchEnd($event)"
        >
            <figure class="lightbox__figure">
                <img
                    :src="currentImage.full"
                    :alt="currentImage.alt"
                    class="lightbox__image"
                    x-show="!loading"
                    @load="loading = false"
                >
                <div class="lightbox__loader" x-show="loading" aria-live="polite">
                    <span class="lightbox__spinner" role="status">
                        <span class="sr-only"><?php esc_html_e('Chargement...', 'lemur'); ?></span>
                    </span>
                </div>

                <figcaption
                    class="lightbox__caption"
                    x-show="currentImage.caption"
                    x-text="currentImage.caption"
                ></figcaption>
            </figure>
        </div>

        <!-- Next Button -->
        <button
            type="button"
            class="lightbox__nav lightbox__nav--next"
            @click="nextImage()"
            :disabled="currentIndex === images.length - 1"
            aria-label="<?php esc_attr_e('Image suivante', 'lemur'); ?>"
        >
            <?php lemur_the_ui_icon('chevron-right', ['width' => 32, 'height' => 32]); ?>
        </button>

        <!-- Counter -->
        <div class="lightbox__counter" aria-live="polite">
            <span x-text="currentIndex + 1"></span> / <span x-text="images.length"></span>
        </div>
    </div>
</div>
