<?php

/**
 * SEO Performance Optimizations
 *
 * @package Lemur\SEO
 */

declare(strict_types=1);

namespace Lemur\SEO;

/**
 * Performance optimizations for SEO (preload, prefetch, etc.)
 */
class Performance
{
    /**
     * Initialize the module
     */
    public static function init(): void
    {
        add_action('wp_head', [self::class, 'addPreloadLinks'], 1);
        add_action('wp_head', [self::class, 'addPrefetchLinks'], 2);
        add_action('wp_head', [self::class, 'addDnsPrefetch'], 1);
        add_filter('script_loader_tag', [self::class, 'addAsyncDefer'], 10, 3);

        // Remove jQuery Migrate in production (not needed for modern code)
        add_action('wp_default_scripts', [self::class, 'removeJQueryMigrate'], 10);

        // Limit post revisions
        self::limitPostRevisions();

        // Increase autosave interval
        self::increaseAutosaveInterval();
    }

    /**
     * Remove jQuery Migrate script (not needed for modern jQuery usage)
     *
     * @param \WP_Scripts $scripts Scripts object
     */
    public static function removeJQueryMigrate(\WP_Scripts $scripts): void
    {
        // Only on frontend, not in admin
        if (is_admin()) {
            return;
        }

        if (isset($scripts->registered['jquery'])) {
            $script = $scripts->registered['jquery'];
            if ($script->deps) {
                $script->deps = array_diff($script->deps, ['jquery-migrate']);
            }
        }
    }

    /**
     * Limit post revisions to reduce database bloat
     */
    private static function limitPostRevisions(): void
    {
        if (!defined('WP_POST_REVISIONS')) {
            define('WP_POST_REVISIONS', 5);
        }
    }

    /**
     * Increase autosave interval to reduce server load
     * Default is 60 seconds, we increase to 5 minutes
     */
    private static function increaseAutosaveInterval(): void
    {
        if (!defined('AUTOSAVE_INTERVAL')) {
            define('AUTOSAVE_INTERVAL', 300);
        }
    }

    /**
     * Add preload links for critical resources
     */
    public static function addPreloadLinks(): void
    {
        $preloads = [];

        // Preload main font (if using Google Fonts or local)
        // Uncomment and adjust if you have a primary font to preload
        /*
        $preloads[] = [
            'href' => get_template_directory_uri() . '/assets/fonts/inter-var.woff2',
            'as' => 'font',
            'type' => 'font/woff2',
            'crossorigin' => true,
        ];
        */

        // Preload hero image on front page
        if (is_front_page()) {
            $hero_image = self::getHeroImage();
            if ($hero_image) {
                $preloads[] = [
                    'href' => $hero_image,
                    'as' => 'image',
                    'fetchpriority' => 'high',
                ];
            }
        }

        // Preload featured image on single posts
        if (is_singular() && has_post_thumbnail()) {
            $thumb_url = get_the_post_thumbnail_url(get_the_ID(), 'lemur-hero');
            if ($thumb_url) {
                $preloads[] = [
                    'href' => $thumb_url,
                    'as' => 'image',
                    'fetchpriority' => 'high',
                ];
            }
        }

        // Output preload links
        foreach ($preloads as $preload) {
            $attrs = ['rel="preload"'];
            $attrs[] = sprintf('href="%s"', esc_url($preload['href']));
            $attrs[] = sprintf('as="%s"', esc_attr($preload['as']));

            if (!empty($preload['type'])) {
                $attrs[] = sprintf('type="%s"', esc_attr($preload['type']));
            }
            if (!empty($preload['crossorigin'])) {
                $attrs[] = 'crossorigin';
            }
            if (!empty($preload['fetchpriority'])) {
                $attrs[] = sprintf('fetchpriority="%s"', esc_attr($preload['fetchpriority']));
            }

            printf('<link %s>' . "\n", implode(' ', $attrs));
        }
    }

    /**
     * Add prefetch links for likely next pages
     */
    public static function addPrefetchLinks(): void
    {
        $prefetches = [];

        // On single post, prefetch next/prev posts
        if (is_singular('post')) {
            $next = get_next_post();
            $prev = get_previous_post();

            if ($next) {
                $prefetches[] = get_permalink($next);
            }
            if ($prev) {
                $prefetches[] = get_permalink($prev);
            }
        }

        // On archive, prefetch first few posts
        if (is_archive() && have_posts()) {
            global $wp_query;
            $count = 0;
            foreach ($wp_query->posts as $post) {
                if ($count >= 3) {
                    break;
                }
                $prefetches[] = get_permalink($post);
                $count++;
            }
        }

        // Output prefetch links
        foreach (array_unique($prefetches) as $url) {
            printf('<link rel="prefetch" href="%s">' . "\n", esc_url($url));
        }
    }

    /**
     * Add DNS prefetch for external domains
     */
    public static function addDnsPrefetch(): void
    {
        $domains = [
            '//fonts.googleapis.com',
            '//fonts.gstatic.com',
            '//www.google-analytics.com',
            '//www.googletagmanager.com',
        ];

        // Add preconnect for critical third-party origins
        $preconnects = [
            'https://fonts.googleapis.com',
            'https://fonts.gstatic.com',
        ];

        foreach ($preconnects as $origin) {
            printf('<link rel="preconnect" href="%s" crossorigin>' . "\n", esc_url($origin));
        }

        foreach ($domains as $domain) {
            printf('<link rel="dns-prefetch" href="%s">' . "\n", esc_attr($domain));
        }
    }

    /**
     * Add async/defer to non-critical scripts
     */
    public static function addAsyncDefer(string $tag, string $handle, string $src): string
    {
        // Scripts to load with defer
        $defer_scripts = [
            'google-analytics',
            'gtm',
            'lemur-lightbox',
        ];

        // Scripts to load with async
        $async_scripts = [
            // 'some-async-script',
        ];

        if (in_array($handle, $defer_scripts, true)) {
            return str_replace(' src=', ' defer src=', $tag);
        }

        if (in_array($handle, $async_scripts, true)) {
            return str_replace(' src=', ' async src=', $tag);
        }

        return $tag;
    }

    /**
     * Get hero image URL from front page
     */
    private static function getHeroImage(): string
    {
        if (!function_exists('carbon_get_post_meta')) {
            return '';
        }

        $front_page_id = get_option('page_on_front');
        if (!$front_page_id) {
            return '';
        }

        // Try to get hero image from page builder blocks
        $blocks = carbon_get_post_meta($front_page_id, 'lemur_page_blocks');
        if (!empty($blocks) && is_array($blocks)) {
            foreach ($blocks as $block) {
                if (isset($block['_type']) && $block['_type'] === 'hero' && !empty($block['image'])) {
                    $url = wp_get_attachment_image_url($block['image'], 'lemur-hero');
                    if ($url) {
                        return $url;
                    }
                }
            }
        }

        // Fallback to featured image
        if (has_post_thumbnail($front_page_id)) {
            $url = get_the_post_thumbnail_url($front_page_id, 'lemur-hero');
            if ($url) {
                return $url;
            }
        }

        return '';
    }

    /**
     * Add fetchpriority to images
     */
    public static function addFetchPriority(string $html, int $attachment_id, string $size): string
    {
        // Add high priority to hero images
        if ($size === 'lemur-hero') {
            $html = str_replace('<img', '<img fetchpriority="high"', $html);
        }

        return $html;
    }
}
