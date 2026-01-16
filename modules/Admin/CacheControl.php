<?php

/**
 * Admin Cache Control
 *
 * Allows admins to invalidate client-side cache for all users.
 * Provides admin bar quick action and tools page.
 *
 * @package Lemur\Admin
 */

declare(strict_types=1);

namespace Lemur\Admin;

/**
 * Cache control management for admins
 */
class CacheControl
{
    /**
     * Option key for cache version
     */
    public const OPTION_KEY = 'lemur_cache_version';

    /**
     * Menu slug
     */
    public const MENU_SLUG = 'lemur-cache-control';

    /**
     * Nonce action
     */
    private const NONCE_ACTION = 'lemur_clear_cache';

    /**
     * Initialize the module
     */
    public static function init(): void
    {
        add_action('admin_menu', [self::class, 'addAdminMenu'], 10);
        add_action('admin_bar_menu', [self::class, 'addAdminBarMenu'], 100);
        add_action('admin_post_lemur_quick_clear_cache', [self::class, 'handleQuickClear'], 10);
        add_action('admin_notices', [self::class, 'showClearNotice'], 10);
        add_action('admin_head', [self::class, 'addAdminBarStyles'], 10);
        add_action('wp_head', [self::class, 'addAdminBarStyles'], 10);
        add_action('wp_head', [self::class, 'outputCacheVersion'], 5);

        // REST API endpoint for cache version check
        add_action('rest_api_init', [self::class, 'registerRestRoute'], 10);
    }

    /**
     * Add admin menu item under Tools
     */
    public static function addAdminMenu(): void
    {
        add_submenu_page(
            'tools.php',
            __('Cache Client', 'lemur'),
            __('Cache Client', 'lemur'),
            'manage_options',
            self::MENU_SLUG,
            [self::class, 'renderAdminPage']
        );
    }

    /**
     * Add admin bar quick cache control
     *
     * @param \WP_Admin_Bar $wp_admin_bar Admin bar instance
     */
    public static function addAdminBarMenu(\WP_Admin_Bar $wp_admin_bar): void
    {
        if (!current_user_can('manage_options')) {
            return;
        }

        // Parent menu
        $wp_admin_bar->add_node([
            'id'    => 'lemur-cache',
            'title' => '<span class="ab-icon dashicons dashicons-update"></span>' .
                       esc_html__('Cache', 'lemur'),
            'href'  => admin_url('tools.php?page=' . self::MENU_SLUG),
            'meta'  => [
                'title' => __('Gestion du cache client', 'lemur'),
            ],
        ]);

        // Quick clear action
        $clear_url = wp_nonce_url(
            admin_url('admin-post.php?action=lemur_quick_clear_cache'),
            self::NONCE_ACTION
        );

        $wp_admin_bar->add_node([
            'id'     => 'lemur-cache-clear',
            'parent' => 'lemur-cache',
            'title'  => __('Vider le cache', 'lemur'),
            'href'   => $clear_url,
            'meta'   => [
                'title' => __('Invalider le cache immédiatement', 'lemur'),
            ],
        ]);

        // Settings link
        $wp_admin_bar->add_node([
            'id'     => 'lemur-cache-settings',
            'parent' => 'lemur-cache',
            'title'  => __('Réglages', 'lemur'),
            'href'   => admin_url('tools.php?page=' . self::MENU_SLUG),
        ]);
    }

    /**
     * Handle quick cache clear from admin bar
     */
    public static function handleQuickClear(): void
    {
        if (!current_user_can('manage_options')) {
            wp_die(
                esc_html__('Vous n\'avez pas les permissions nécessaires.', 'lemur'),
                esc_html__('Accès refusé', 'lemur'),
                ['response' => 403]
            );
        }

        check_admin_referer(self::NONCE_ACTION);

        self::invalidateCache();

        $redirect_url = wp_get_referer();
        if (!$redirect_url) {
            $redirect_url = admin_url();
        }

        $redirect_url = add_query_arg('lemur_cache_cleared', '1', $redirect_url);

        wp_safe_redirect($redirect_url);
        exit;
    }

    /**
     * Show admin notice after cache clear
     */
    public static function showClearNotice(): void
    {
        if (!isset($_GET['lemur_cache_cleared'])) {
            return;
        }

        printf(
            '<div class="notice notice-success is-dismissible"><p>%s</p></div>',
            esc_html__('Cache client invalidé avec succès !', 'lemur')
        );
    }

    /**
     * Admin bar styles for cache icon
     */
    public static function addAdminBarStyles(): void
    {
        if (!current_user_can('manage_options')) {
            return;
        }
        ?>
        <style>
            #wp-admin-bar-lemur-cache .ab-icon {
                top: 2px;
            }
            #wp-admin-bar-lemur-cache .ab-icon::before {
                content: "\f463";
                font-family: dashicons;
                font-size: 16px;
            }
        </style>
        <?php
    }

    /**
     * Render admin page
     */
    public static function renderAdminPage(): void
    {
        // Handle form submission
        if (isset($_POST['lemur_clear_cache']) && check_admin_referer(self::NONCE_ACTION . '_form')) {
            self::invalidateCache();
            echo '<div class="notice notice-success"><p>' .
                 esc_html__('Cache invalidé ! Les utilisateurs chargeront les nouvelles données au prochain rechargement.', 'lemur') .
                 '</p></div>';
        }

        $current_version = self::getCacheVersion();
        ?>
        <div class="wrap">
            <h1><?php esc_html_e('Cache Client', 'lemur'); ?></h1>

            <div class="card" style="max-width: 600px; padding: 20px;">
                <h2><?php esc_html_e('Invalider le cache', 'lemur'); ?></h2>
                <p><?php esc_html_e('Change la version du cache. Au prochain chargement, les utilisateurs récupéreront les données fraîches depuis le serveur.', 'lemur'); ?></p>

                <form method="post">
                    <?php wp_nonce_field(self::NONCE_ACTION . '_form'); ?>
                    <p>
                        <button type="submit" name="lemur_clear_cache" class="button button-primary">
                            <?php esc_html_e('Invalider le cache', 'lemur'); ?>
                        </button>
                    </p>
                </form>

                <hr style="margin: 20px 0;" />

                <h3><?php esc_html_e('État actuel', 'lemur'); ?></h3>
                <p>
                    <strong><?php esc_html_e('Version :', 'lemur'); ?></strong>
                    <code><?php echo esc_html($current_version > 0 ? (string) $current_version : __('aucune', 'lemur')); ?></code>
                </p>
                <?php if ($current_version > 0) : ?>
                    <p>
                        <strong><?php esc_html_e('Dernière invalidation :', 'lemur'); ?></strong>
                        <?php echo esc_html(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), $current_version)); ?>
                    </p>
                <?php endif; ?>

                <hr style="margin: 20px 0;" />

                <h3><?php esc_html_e('Utilisation JavaScript', 'lemur'); ?></h3>
                <p><?php esc_html_e('Le numéro de version est disponible dans :', 'lemur'); ?></p>
                <pre style="background: #f5f5f5; padding: 10px; overflow-x: auto;"><code>window.lemurCacheVersion</code></pre>
                <p><?php esc_html_e('API REST pour vérifier la version :', 'lemur'); ?></p>
                <pre style="background: #f5f5f5; padding: 10px; overflow-x: auto;"><code>GET /wp-json/lemur/v1/cache-version</code></pre>
            </div>
        </div>
        <?php
    }

    /**
     * Output cache version to frontend
     */
    public static function outputCacheVersion(): void
    {
        $version = self::getCacheVersion();
        printf(
            '<script>window.lemurCacheVersion = %d;</script>' . "\n",
            (int) $version
        );
    }

    /**
     * Register REST API route
     */
    public static function registerRestRoute(): void
    {
        register_rest_route('lemur/v1', '/cache-version', [
            'methods'             => 'GET',
            'callback'            => [self::class, 'restGetCacheVersion'],
            'permission_callback' => '__return_true',
        ]);
    }

    /**
     * REST API callback for cache version
     *
     * @return \WP_REST_Response
     */
    public static function restGetCacheVersion(): \WP_REST_Response
    {
        $version = self::getCacheVersion();

        $response = rest_ensure_response([
            'version' => (int) $version,
        ]);

        // Prevent caching of this endpoint
        $response->header('Cache-Control', 'no-cache, no-store, must-revalidate');
        $response->header('Pragma', 'no-cache');
        $response->header('Expires', '0');

        return $response;
    }

    /**
     * Get current cache version
     *
     * @return int Cache version timestamp
     */
    public static function getCacheVersion(): int
    {
        return (int) get_option(self::OPTION_KEY, 0);
    }

    /**
     * Invalidate cache by updating version
     *
     * @return int New cache version
     */
    public static function invalidateCache(): int
    {
        $new_version = time();
        update_option(self::OPTION_KEY, $new_version);

        /**
         * Fires after cache is invalidated
         *
         * @param int $new_version New cache version timestamp
         */
        do_action('lemur_cache_invalidated', $new_version);

        return $new_version;
    }
}
