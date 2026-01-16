<?php

/**
 * WordPress Hardening - Security Lockdown
 *
 * @package Lemur\Security
 */

declare(strict_types=1);

namespace Lemur\Security;

/**
 * Hardens WordPress by disabling unnecessary features
 */
class Hardening
{
    /**
     * Initialize the module
     */
    public static function init(): void
    {
        // File editor
        self::disableFileEditor();

        // Comments
        self::disableComments();

        // Meta tags cleanup
        self::cleanupHead();

        // XML-RPC & Pingbacks
        self::disableXmlRpc();

        // REST API restrictions
        self::restrictRestApi();

        // Login security
        self::secureLogin();

        // Misc hardening
        self::miscHardening();
    }

    /**
     * Disable file editor in admin (plugins/themes)
     */
    private static function disableFileEditor(): void
    {
        // Define constant if not already defined (usually in wp-config.php)
        if (!defined('DISALLOW_FILE_EDIT')) {
            define('DISALLOW_FILE_EDIT', true);
        }

        // Remove editor submenu items
        add_action('admin_menu', function (): void {
            remove_submenu_page('themes.php', 'theme-editor.php');
            remove_submenu_page('plugins.php', 'plugin-editor.php');
        }, 999);

        // Block direct access to editor
        add_action('admin_init', function (): void {
            global $pagenow;
            $blocked_pages = ['theme-editor.php', 'plugin-editor.php'];

            if (in_array($pagenow, $blocked_pages, true)) {
                wp_die(
                    esc_html__('L\'éditeur de fichiers est désactivé pour des raisons de sécurité.', 'lemur'),
                    esc_html__('Accès refusé', 'lemur'),
                    ['response' => 403]
                );
            }
        }, 1);
    }

    /**
     * Completely disable comments
     */
    private static function disableComments(): void
    {
        // Disable comments support for all post types
        add_action('init', function (): void {
            // Remove comment support from posts
            remove_post_type_support('post', 'comments');
            remove_post_type_support('post', 'trackbacks');
            remove_post_type_support('page', 'comments');
            remove_post_type_support('page', 'trackbacks');

            // Close comments on all post types
            foreach (get_post_types() as $post_type) {
                if (post_type_supports($post_type, 'comments')) {
                    remove_post_type_support($post_type, 'comments');
                    remove_post_type_support($post_type, 'trackbacks');
                }
            }
        }, 100);

        // Force comments closed
        add_filter('comments_open', '__return_false', 20, 2);
        add_filter('pings_open', '__return_false', 20, 2);

        // Hide existing comments
        add_filter('comments_array', '__return_empty_array', 10, 2);
        add_filter('get_comments_number', '__return_zero');

        // Remove comments from admin menu
        add_action('admin_menu', function (): void {
            remove_menu_page('edit-comments.php');
        }, 999);

        // Remove comments from admin bar
        add_action('admin_bar_menu', function (\WP_Admin_Bar $wp_admin_bar): void {
            $wp_admin_bar->remove_node('comments');
        }, 999);

        // Remove comments metabox from dashboard
        add_action('admin_init', function (): void {
            remove_meta_box('dashboard_recent_comments', 'dashboard', 'normal');
        }, 10);

        // Remove comments column from posts list
        add_filter('manage_posts_columns', function (array $columns): array {
            unset($columns['comments']);
            return $columns;
        }, 10);

        add_filter('manage_pages_columns', function (array $columns): array {
            unset($columns['comments']);
            return $columns;
        }, 10);

        // Redirect comments admin page
        add_action('admin_init', function (): void {
            global $pagenow;
            if ($pagenow === 'edit-comments.php') {
                wp_safe_redirect(admin_url());
                exit;
            }
        }, 1);

        // Remove comment feed links
        remove_action('wp_head', 'feed_links_extra', 3);

        // Disable comments REST API endpoint
        add_filter('rest_endpoints', function (array $endpoints): array {
            unset($endpoints['/wp/v2/comments']);
            unset($endpoints['/wp/v2/comments/(?P<id>[\d]+)']);
            return $endpoints;
        }, 10);

        // Remove comments from activity widget
        add_action('admin_init', function (): void {
            remove_meta_box('dashboard_recent_drafts', 'dashboard', 'side');
        }, 10);
    }

    /**
     * Clean up WordPress head output
     */
    private static function cleanupHead(): void
    {
        // Remove WordPress version
        remove_action('wp_head', 'wp_generator');
        add_filter('the_generator', '__return_empty_string');

        // Remove version from scripts and styles
        add_filter('style_loader_src', [self::class, 'removeVersionQuery'], 10, 2);
        add_filter('script_loader_src', [self::class, 'removeVersionQuery'], 10, 2);

        // Remove WLW manifest
        remove_action('wp_head', 'wlwmanifest_link');

        // Remove RSD link
        remove_action('wp_head', 'rsd_link');

        // Remove shortlink
        remove_action('wp_head', 'wp_shortlink_wp_head');
        remove_action('template_redirect', 'wp_shortlink_header', 11);

        // Remove adjacent posts links
        remove_action('wp_head', 'adjacent_posts_rel_link_wp_head', 10);

        // Remove REST API link
        remove_action('wp_head', 'rest_output_link_wp_head', 10);
        remove_action('template_redirect', 'rest_output_link_header', 11);

        // Remove oEmbed discovery links
        remove_action('wp_head', 'wp_oembed_add_discovery_links', 10);

        // Remove emoji scripts
        remove_action('wp_head', 'print_emoji_detection_script', 7);
        remove_action('admin_print_scripts', 'print_emoji_detection_script');
        remove_action('wp_print_styles', 'print_emoji_styles');
        remove_action('admin_print_styles', 'print_emoji_styles');
        remove_filter('the_content_feed', 'wp_staticize_emoji');
        remove_filter('comment_text_rss', 'wp_staticize_emoji');
        remove_filter('wp_mail', 'wp_staticize_emoji_for_email');

        // Remove DNS prefetch for emojis
        add_filter('emoji_svg_url', '__return_false');

        // Remove X-Pingback header
        add_filter('wp_headers', function (array $headers): array {
            unset($headers['X-Pingback']);
            return $headers;
        }, 10);
    }

    /**
     * Remove version query string from assets
     */
    public static function removeVersionQuery(string $src, string $handle): string
    {
        // Keep version for admin and logged-in users (cache busting)
        if (is_admin() || is_user_logged_in()) {
            return $src;
        }

        // Keep version for theme assets
        if (strpos($src, 'lemur') !== false) {
            return $src;
        }

        // Remove ver= from URL
        if (strpos($src, 'ver=') !== false) {
            $src = remove_query_arg('ver', $src);
        }

        return $src;
    }

    /**
     * Disable XML-RPC completely
     */
    private static function disableXmlRpc(): void
    {
        // Disable XML-RPC methods
        add_filter('xmlrpc_enabled', '__return_false');

        // Disable X-Pingback header
        add_filter('xmlrpc_methods', function (array $methods): array {
            return [];
        }, 10);

        // Block XML-RPC requests
        add_action('init', function (): void {
            if (defined('XMLRPC_REQUEST') && XMLRPC_REQUEST) {
                wp_die(
                    'XML-RPC est désactivé.',
                    'Forbidden',
                    ['response' => 403]
                );
            }
        }, 1);

        // Remove XML-RPC RSD endpoint
        add_filter('xmlrpc_rsd_apis', '__return_empty_array');

        // Disable pingbacks
        add_filter('pre_ping', function (array &$links): void {
            $links = [];
        }, 10);

        // Disable self-pingback
        add_action('pre_ping', function (array &$links, array &$pung, int $post_id): void {
            $home = home_url();
            foreach ($links as $key => $link) {
                if (strpos($link, $home) === 0) {
                    unset($links[$key]);
                }
            }
        }, 10, 3);
    }

    /**
     * Restrict REST API access
     */
    private static function restrictRestApi(): void
    {
        // Require authentication for most REST endpoints
        add_filter('rest_authentication_errors', function ($result) {
            // Don't override existing errors
            if ($result !== null) {
                return $result;
            }

            // Allow logged-in users
            if (is_user_logged_in()) {
                return $result;
            }

            // Allow specific public endpoints
            $request_uri = isset($_SERVER['REQUEST_URI']) ? sanitize_text_field(wp_unslash($_SERVER['REQUEST_URI'])) : '';

            $public_routes = [
                '/wp-json/wp/v2/posts',
                '/wp-json/wp/v2/pages',
                '/wp-json/wp/v2/categories',
                '/wp-json/wp/v2/tags',
                '/wp-json/wp/v2/media',
                '/wp-json/oembed/',
                '/wp-json/lemur/',
            ];

            foreach ($public_routes as $route) {
                if (strpos($request_uri, $route) !== false) {
                    return $result;
                }
            }

            // Block other endpoints for non-authenticated users
            return new \WP_Error(
                'rest_forbidden',
                __('Authentification requise.', 'lemur'),
                ['status' => 401]
            );
        }, 10);

        // Remove user enumeration via REST API
        add_filter('rest_endpoints', function (array $endpoints): array {
            if (!is_user_logged_in()) {
                unset($endpoints['/wp/v2/users']);
                unset($endpoints['/wp/v2/users/(?P<id>[\d]+)']);
            }
            return $endpoints;
        }, 10);
    }

    /**
     * Secure WordPress login
     */
    private static function secureLogin(): void
    {
        // Generic login error messages (prevent username enumeration)
        add_filter('login_errors', function (): string {
            return __('Identifiants incorrects.', 'lemur');
        }, 10);

        // Remove login hints
        add_filter('login_message', '__return_empty_string');

        // Disable login by email option display
        add_filter('gettext', function (string $text): string {
            if ($text === 'Username or Email Address') {
                return __('Identifiant', 'lemur');
            }
            return $text;
        }, 10);

        // Add security headers to login page
        add_action('login_init', function (): void {
            header('X-Frame-Options: DENY');
            header('X-Content-Type-Options: nosniff');
            header('X-XSS-Protection: 1; mode=block');
        }, 1);

        // Disable author archives (user enumeration)
        add_action('template_redirect', function (): void {
            if (is_author()) {
                wp_safe_redirect(home_url(), 301);
                exit;
            }
        }, 1);

        // Block author query parameter enumeration
        add_action('init', function (): void {
            if (isset($_GET['author']) && !is_admin()) {
                wp_safe_redirect(home_url(), 301);
                exit;
            }
        }, 1);

        // Remove user sitemap
        add_filter('wp_sitemaps_add_provider', function ($provider, string $name) {
            if ($name === 'users') {
                return false;
            }
            return $provider;
        }, 10, 2);
    }

    /**
     * Miscellaneous hardening
     */
    private static function miscHardening(): void
    {
        // Disable application passwords for non-admin users
        add_filter('wp_is_application_passwords_available_for_user', function (bool $available, \WP_User $user): bool {
            if (!user_can($user, 'manage_options')) {
                return false;
            }
            return $available;
        }, 10, 2);

        // Remove WordPress version from RSS feeds
        add_filter('the_generator', '__return_empty_string');

        // Disable file editing via admin (backup)
        add_filter('file_mod_allowed', function (bool $allowed, string $context): bool {
            if ($context === 'edit_plugins' || $context === 'edit_themes') {
                return false;
            }
            return $allowed;
        }, 10, 2);

        // Remove unnecessary dashboard widgets
        add_action('wp_dashboard_setup', function (): void {
            // Remove WordPress news
            remove_meta_box('dashboard_primary', 'dashboard', 'side');
            // Remove quick draft
            remove_meta_box('dashboard_quick_press', 'dashboard', 'side');
            // Remove at a glance (can reveal version info)
            // remove_meta_box('dashboard_right_now', 'dashboard', 'normal');
        }, 999);

        // Disable user registration (unless explicitly enabled)
        add_filter('option_users_can_register', '__return_zero');

        // Force strong passwords
        add_action('user_profile_update_errors', function (\WP_Error $errors, bool $update, \stdClass $user): void {
            if (isset($_POST['pass1']) && !empty($_POST['pass1'])) {
                $password = sanitize_text_field($_POST['pass1']);
                if (strlen($password) < 12) {
                    $errors->add('weak_password', __('Le mot de passe doit contenir au moins 12 caractères.', 'lemur'));
                }
            }
        }, 10, 3);

        // Disable HTML in user descriptions
        remove_filter('pre_user_description', 'wp_filter_kses');
        add_filter('pre_user_description', 'wp_strip_all_tags');

        // Limit post revisions
        if (!defined('WP_POST_REVISIONS')) {
            define('WP_POST_REVISIONS', 5);
        }

        // Disable auto-save for posts (optional - uncomment if wanted)
        // add_action('admin_init', function() {
        //     wp_deregister_script('autosave');
        // });

        // Remove Welcome panel
        remove_action('welcome_panel', 'wp_welcome_panel');

        // Disable core auto-updates UI (if managed elsewhere)
        add_filter('auto_update_core', '__return_false');
        add_filter('auto_update_plugin', '__return_false');
        add_filter('auto_update_theme', '__return_false');
    }

    /**
     * Check if hardening is active
     */
    public static function isActive(): bool
    {
        return defined('DISALLOW_FILE_EDIT') && DISALLOW_FILE_EDIT;
    }

    /**
     * Get hardening status for admin display
     */
    public static function getStatus(): array
    {
        return [
            'file_editor_disabled' => defined('DISALLOW_FILE_EDIT') && DISALLOW_FILE_EDIT,
            'comments_disabled' => !comments_open(),
            'xmlrpc_disabled' => !has_filter('xmlrpc_enabled'),
            'author_archives_disabled' => true,
            'rest_api_restricted' => true,
            'strong_passwords' => true,
            'revisions_limited' => defined('WP_POST_REVISIONS') && WP_POST_REVISIONS <= 10,
        ];
    }
}
