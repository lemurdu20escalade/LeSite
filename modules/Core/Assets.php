<?php
/**
 * Assets management
 *
 * @package Lemur\Core
 */

declare(strict_types=1);

namespace Lemur\Core;

/**
 * Handle theme assets (CSS, JS) with Vite integration
 */
class Assets
{
    /**
     * Script handles that need type="module"
     */
    private const MODULE_HANDLES = ['vite-client', 'lemur-main'];

    /**
     * Cached manifest data
     *
     * @var array<string, mixed>|null|false False = not loaded, null = load failed
     */
    private static array|null|false $manifestCache = false;

    /**
     * Initialize assets
     */
    public static function init(): void
    {
        add_action('wp_enqueue_scripts', [self::class, 'enqueue'], 10);
        add_action('send_headers', [self::class, 'addCorsHeaders'], 10);
    }

    /**
     * Enqueue theme assets
     */
    public static function enqueue(): void
    {
        if (Theme::isDevMode()) {
            self::enqueueDev();
        } else {
            self::enqueueProd();
        }
    }

    /**
     * Enqueue development assets (Vite HMR)
     */
    private static function enqueueDev(): void
    {
        $viteUrl = Theme::getViteDevUrl();

        // Vite client for HMR
        // phpcs:ignore WordPress.WP.EnqueuedResourceParameters.MissingVersion
        wp_enqueue_script(
            'vite-client',
            $viteUrl . '/@vite/client',
            [],
            null,
            false
        );

        // Main JS module
        // phpcs:ignore WordPress.WP.EnqueuedResourceParameters.MissingVersion
        wp_enqueue_script(
            'lemur-main',
            $viteUrl . '/src/js/main.js',
            [],
            null,
            true
        );

        // Add type="module" to scripts
        add_filter('script_loader_tag', [self::class, 'addModuleType'], 10, 3);
    }

    /**
     * Enqueue production assets from Vite manifest
     */
    private static function enqueueProd(): void
    {
        $manifest = self::getManifest();

        if (!$manifest) {
            return;
        }

        // Enqueue main CSS
        if (isset($manifest['src/css/main.css']['file'])) {
            wp_enqueue_style(
                'lemur-style',
                Theme::getUri('dist/' . $manifest['src/css/main.css']['file']),
                [],
                Theme::getVersion()
            );
        }

        // Enqueue main JS
        if (isset($manifest['src/js/main.js']['file'])) {
            wp_enqueue_script(
                'lemur-main',
                Theme::getUri('dist/' . $manifest['src/js/main.js']['file']),
                [],
                Theme::getVersion(),
                true
            );

            // Add type="module" to script
            add_filter('script_loader_tag', [self::class, 'addModuleType'], 10, 3);
        }

        // Handle CSS imported by JS
        if (isset($manifest['src/js/main.js']['css'])) {
            foreach ($manifest['src/js/main.js']['css'] as $index => $cssFile) {
                wp_enqueue_style(
                    'lemur-style-' . $index,
                    Theme::getUri('dist/' . $cssFile),
                    [],
                    Theme::getVersion()
                );
            }
        }
    }

    /**
     * Get the Vite manifest (cached)
     *
     * @return array<string, mixed>|null
     */
    private static function getManifest(): ?array
    {
        // Return cached result if already loaded
        if (self::$manifestCache !== false) {
            return self::$manifestCache;
        }

        $manifestPath = Theme::getPath('dist/.vite/manifest.json');

        if (!file_exists($manifestPath)) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
                error_log('[Lemur] Vite manifest not found: ' . $manifestPath);
            }
            self::$manifestCache = null;
            return null;
        }

        // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
        $manifest = json_decode(file_get_contents($manifestPath), true);

        if (!$manifest || !isset($manifest['src/js/main.js']['file'])) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
                error_log('[Lemur] Invalid or incomplete Vite manifest');
            }
            self::$manifestCache = null;
            return null;
        }

        self::$manifestCache = $manifest;
        return $manifest;
    }

    /**
     * Add type="module" attribute to script tags
     *
     * @param string $tag    The script tag.
     * @param string $handle The script handle.
     * @param string $src    The script source.
     * @return string Modified script tag.
     */
    public static function addModuleType(string $tag, string $handle, string $src): string
    {
        if (in_array($handle, self::MODULE_HANDLES, true)) {
            $tag = (string) preg_replace('/<script\s/', '<script type="module" ', $tag, 1);
        }

        return $tag;
    }

    /**
     * Add CORS headers for Vite dev server
     */
    public static function addCorsHeaders(): void
    {
        if (Theme::isDevMode()) {
            header('Access-Control-Allow-Origin: ' . Theme::getViteDevUrl());
        }
    }
}
