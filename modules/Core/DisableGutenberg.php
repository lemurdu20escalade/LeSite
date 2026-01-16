<?php
/**
 * Disable Gutenberg Editor
 *
 * Disables the block editor (Gutenberg) site-wide and restores
 * the classic editor for all post types.
 *
 * @package Lemur\Core
 */

declare(strict_types=1);

namespace Lemur\Core;

/**
 * Disable Gutenberg block editor across the site
 */
class DisableGutenberg
{
    /**
     * Initialize Gutenberg disabling
     */
    public static function init(): void
    {
        // Disable Gutenberg for all post types
        add_filter('use_block_editor_for_post', '__return_false', 100);
        add_filter('use_block_editor_for_post_type', '__return_false', 100);

        // Disable Gutenberg for widgets
        add_filter('use_widgets_block_editor', '__return_false');

        // Remove Gutenberg-related admin styles
        add_action('admin_enqueue_scripts', [self::class, 'dequeueGutenbergStyles'], 100);

        // Remove Gutenberg frontend block styles
        add_action('wp_enqueue_scripts', [self::class, 'dequeueBlockStyles'], 100);

        // Remove Gutenberg inline styles
        add_action('wp_footer', [self::class, 'removeInlineStyles'], 100);

        // Remove block-related body classes
        add_filter('body_class', [self::class, 'removeBlockBodyClasses']);

        // Delayed cleanup (after plugins/themes have registered their hooks)
        add_action('init', [self::class, 'removeBlockFeatures'], 100);
    }

    /**
     * Remove block features after init
     *
     * Must run on init hook to ensure theme supports and actions are registered.
     */
    public static function removeBlockFeatures(): void
    {
        // Disable block patterns
        remove_theme_support('core-block-patterns');

        // Disable block directory (plugin installer in editor)
        remove_action('enqueue_block_editor_assets', 'wp_enqueue_editor_block_directory_assets');

        // Remove duotone SVG filters
        remove_action('wp_body_open', 'wp_global_styles_render_svg_filters');
    }

    /**
     * Dequeue Gutenberg admin styles
     */
    public static function dequeueGutenbergStyles(): void
    {
        wp_dequeue_style('wp-block-library');
        wp_dequeue_style('wp-block-library-theme');
    }

    /**
     * Dequeue block styles from frontend
     */
    public static function dequeueBlockStyles(): void
    {
        wp_dequeue_style('wp-block-library');
        wp_dequeue_style('wp-block-library-theme');
        wp_dequeue_style('wc-blocks-style'); // WooCommerce blocks
        wp_dequeue_style('global-styles'); // Global styles from theme.json
        wp_dequeue_style('classic-theme-styles');
    }

    /**
     * Remove inline global styles
     */
    public static function removeInlineStyles(): void
    {
        wp_dequeue_style('global-styles');
        wp_dequeue_style('core-block-supports');
    }

    /**
     * Remove block-related body classes
     *
     * @param string[] $classes Body classes
     * @return string[]
     */
    public static function removeBlockBodyClasses(array $classes): array
    {
        return array_filter($classes, function ($class) {
            return !str_starts_with($class, 'wp-block-');
        });
    }
}
