<?php
/**
 * Access Control for Member Area
 *
 * Handles page protection, access verification, and content restriction.
 *
 * @package Lemur\MemberArea\Access
 */

declare(strict_types=1);

namespace Lemur\MemberArea\Access;

/**
 * Access control and content protection
 */
class AccessControl
{
    /**
     * Protected page slugs
     */
    public const PROTECTED_PAGES = [
        'espace-membre',
        'documents',
        'annuaire',
        'todo-list',
        'calendrier-membres',
    ];

    /**
     * Query var for access denied reason
     */
    public const QUERY_VAR_DENIED_REASON = 'lemur_access_denied';

    /**
     * Option key for custom login page
     */
    public const OPTION_LOGIN_PAGE = 'lemur_member_login_page';

    /**
     * Initialize access control
     */
    public static function init(): void
    {
        // Protect pages on template redirect
        add_action('template_redirect', [self::class, 'protectPages'], 10);

        // Add no-cache headers for member pages
        add_action('send_headers', [self::class, 'addNoCacheHeaders'], 10);

        // Add noindex meta tag for member pages (belt and suspenders with X-Robots-Tag)
        add_action('wp_head', [self::class, 'addNoIndexMeta'], 1);

        // Register shortcodes
        add_shortcode('membre_only', [self::class, 'shortcodeMemberOnly']);
        add_shortcode('bureau_only', [self::class, 'shortcodeBureauOnly']);
        add_shortcode('collectif_only', [self::class, 'shortcodeCollectifOnly']);
        add_shortcode('membre_content', [self::class, 'shortcodeMemberContent']);

        // Add login form message for session expired
        add_filter('login_message', [self::class, 'loginMessage'], 10);

        // Register query var
        add_filter('query_vars', [self::class, 'registerQueryVars'], 10);
    }

    /**
     * Add noindex meta tag for protected pages
     */
    public static function addNoIndexMeta(): void
    {
        if (!self::isProtectedPage()) {
            return;
        }

        // Meta tag as additional layer (header is primary)
        echo '<meta name="robots" content="noindex, nofollow, noarchive, nosnippet">' . "\n";
    }

    /**
     * Protect member-only pages
     */
    public static function protectPages(): void
    {
        // Skip admin and AJAX
        if (is_admin() || wp_doing_ajax()) {
            return;
        }

        // Check if current page is protected
        if (!self::isProtectedPage()) {
            return;
        }

        // Check access
        if (!Capabilities::canAccessMemberArea()) {
            self::handleAccessDenied();
        }
    }

    /**
     * Check if current page is protected
     */
    public static function isProtectedPage(): bool
    {
        // Check by slug
        if (is_page(self::PROTECTED_PAGES)) {
            return true;
        }

        // Check by page meta (Carbon Fields)
        if (is_page()) {
            $post_id = get_queried_object_id();

            if ($post_id > 0) {
                $is_protected = carbon_get_post_meta($post_id, 'page_members_only');

                if ($is_protected) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Handle access denied
     *
     * @param string $reason Reason for denial
     */
    public static function handleAccessDenied(string $reason = 'not_logged_in'): void
    {
        // If not logged in, redirect to login
        if (!is_user_logged_in()) {
            self::redirectToLogin($reason);
            return;
        }

        // If logged in but no access, show 403
        self::show403($reason);
    }

    /**
     * Redirect to login page
     *
     * @param string $reason Reason for redirect
     */
    public static function redirectToLogin(string $reason = 'not_logged_in'): void
    {
        $login_url = self::getLoginUrl();

        // Add return URL
        $redirect_url = add_query_arg([
            'redirect_to' => urlencode(self::getCurrentUrl()),
            'reason'      => $reason,
        ], $login_url);

        wp_safe_redirect($redirect_url);
        exit;
    }

    /**
     * Show 403 access denied page
     *
     * @param string $reason Reason for denial
     */
    public static function show403(string $reason = 'insufficient_permissions'): void
    {
        global $wp_query;

        // Set 403 status
        status_header(403);
        $wp_query->set_404();

        // Set query var for template
        set_query_var(self::QUERY_VAR_DENIED_REASON, $reason);

        // Try to load custom 403 template
        $template = locate_template('403.php');

        if ($template) {
            include $template;
            exit;
        }

        // Fallback: display simple message
        wp_die(
            self::getAccessDeniedMessage($reason),
            __('Accès refusé', 'lemur'),
            ['response' => 403]
        );
    }

    /**
     * Get login URL
     */
    public static function getLoginUrl(): string
    {
        // Check for custom login page
        $custom_page = get_option(self::OPTION_LOGIN_PAGE);

        if ($custom_page) {
            $page_url = get_permalink((int) $custom_page);

            if ($page_url) {
                return $page_url;
            }
        }

        return wp_login_url();
    }

    /**
     * Get current URL
     */
    public static function getCurrentUrl(): string
    {
        $protocol = is_ssl() ? 'https://' : 'http://';
        $host = sanitize_text_field($_SERVER['HTTP_HOST'] ?? '');
        $uri = sanitize_text_field($_SERVER['REQUEST_URI'] ?? '/');

        return $protocol . $host . $uri;
    }

    /**
     * Add security headers for member pages
     */
    public static function addNoCacheHeaders(): void
    {
        if (!self::isProtectedPage()) {
            return;
        }

        // Prevent caching of member pages
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
        header('Cache-Control: post-check=0, pre-check=0', false);
        header('Pragma: no-cache');
        header('Expires: Thu, 01 Jan 1970 00:00:00 GMT');

        // CRITICAL: Prevent search engine indexing of member pages
        header('X-Robots-Tag: noindex, nofollow, noarchive, nosnippet', true);
    }

    /**
     * Shortcode: Member only content
     *
     * @param array<string, mixed> $atts    Shortcode attributes
     * @param string|null          $content Content between tags
     */
    public static function shortcodeMemberOnly(array $atts, ?string $content = null): string
    {
        if (!Capabilities::canAccessMemberArea()) {
            return '';
        }

        return do_shortcode($content ?? '');
    }

    /**
     * Shortcode: Bureau only content
     *
     * @param array<string, mixed> $atts    Shortcode attributes
     * @param string|null          $content Content between tags
     */
    public static function shortcodeBureauOnly(array $atts, ?string $content = null): string
    {
        if (!Capabilities::isBureau()) {
            return '';
        }

        return do_shortcode($content ?? '');
    }

    /**
     * Shortcode: Collectif only content
     *
     * @param array<string, mixed> $atts    Shortcode attributes
     * @param string|null          $content Content between tags
     */
    public static function shortcodeCollectifOnly(array $atts, ?string $content = null): string
    {
        $atts = shortcode_atts([
            'name' => '',
        ], $atts);

        $collectif = sanitize_text_field($atts['name']);

        if (empty($collectif)) {
            return '';
        }

        if (!RolesManager::userInCollectif($collectif)) {
            return '';
        }

        return do_shortcode($content ?? '');
    }

    /**
     * Shortcode: Member content with fallback message
     *
     * @param array<string, mixed> $atts    Shortcode attributes
     * @param string|null          $content Content between tags
     */
    public static function shortcodeMemberContent(array $atts, ?string $content = null): string
    {
        $atts = shortcode_atts([
            'guest'   => __('Ce contenu est réservé aux membres.', 'lemur'),
            'role'    => '',
            'class'   => 'membre-restricted',
            'show_login' => 'true',
        ], $atts);

        // Check role-specific access
        $has_access = false;
        $role = sanitize_text_field($atts['role']);

        if ($role === 'bureau') {
            $has_access = Capabilities::isBureau();
        } elseif (!empty($role)) {
            $has_access = RolesManager::userInCollectif($role);
        } else {
            $has_access = Capabilities::canAccessMemberArea();
        }

        if ($has_access) {
            return do_shortcode($content ?? '');
        }

        // Build guest message
        $message = '<div class="' . esc_attr($atts['class']) . '">';
        $message .= '<p>' . esc_html($atts['guest']) . '</p>';

        // Add login link if requested and user not logged in
        if ($atts['show_login'] === 'true' && !is_user_logged_in()) {
            $login_url = self::getLoginUrl();
            $message .= '<p><a href="' . esc_url($login_url) . '" class="membre-login-link">';
            $message .= esc_html__('Se connecter', 'lemur');
            $message .= '</a></p>';
        }

        $message .= '</div>';

        return $message;
    }

    /**
     * Get access denied message
     *
     * @param string $reason Denial reason
     */
    public static function getAccessDeniedMessage(string $reason = ''): string
    {
        $messages = [
            'not_logged_in'            => __('Vous devez être connecté pour accéder à cette page.', 'lemur'),
            'insufficient_permissions' => __('Vous n\'avez pas les permissions nécessaires pour accéder à cette page.', 'lemur'),
            'session_expired'          => __('Votre session a expiré. Veuillez vous reconnecter.', 'lemur'),
            'bureau_required'          => __('Cette page est réservée aux membres du bureau.', 'lemur'),
            'collectif_required'       => __('Cette page est réservée aux membres de ce collectif.', 'lemur'),
        ];

        return $messages[$reason] ?? $messages['insufficient_permissions'];
    }

    /**
     * Add login message for session expired
     *
     * @param string $message Existing message
     */
    public static function loginMessage(string $message): string
    {
        if (!isset($_GET['session_expired'])) {
            return $message;
        }

        $expired_message = '<p class="message">';
        $expired_message .= esc_html__('Votre session a expiré. Veuillez vous reconnecter.', 'lemur');
        $expired_message .= '</p>';

        return $expired_message . $message;
    }

    /**
     * Register query vars
     *
     * @param array<string> $vars Query vars
     * @return array<string>
     */
    public static function registerQueryVars(array $vars): array
    {
        $vars[] = self::QUERY_VAR_DENIED_REASON;

        return $vars;
    }

    /**
     * Check if user can access specific content
     *
     * @param string   $restriction Restriction type: 'member', 'bureau', or collectif name
     * @param int|null $user_id     User ID or null for current user
     */
    public static function canAccess(string $restriction, ?int $user_id = null): bool
    {
        switch ($restriction) {
            case 'member':
            case 'membre':
                return Capabilities::canAccessMemberArea($user_id);

            case 'bureau':
                return Capabilities::isBureau($user_id);

            default:
                // Assume it's a collectif name
                return RolesManager::userInCollectif($restriction, $user_id);
        }
    }
}
