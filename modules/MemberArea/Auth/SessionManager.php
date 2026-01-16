<?php
/**
 * Session Manager for Member Area
 *
 * Manages extended sessions for authenticated members.
 * Provides configurable session lifetime and secure token management.
 *
 * @package Lemur\MemberArea\Auth
 */

declare(strict_types=1);

namespace Lemur\MemberArea\Auth;

/**
 * Session management for member area
 */
class SessionManager
{
    /**
     * Default session lifetime in days
     */
    public const DEFAULT_SESSION_DAYS = 7;

    /**
     * Option key for session lifetime setting
     */
    public const OPTION_SESSION_DAYS = 'lemur_session_days';

    /**
     * User meta keys
     */
    public const META_SESSION_TOKEN = '_lemur_session_token';
    public const META_SESSION_EXPIRY = '_lemur_session_expiry';
    public const META_SESSION_IP = '_lemur_session_ip';
    public const META_SESSION_UA = '_lemur_session_ua';

    /**
     * Cookie name for extended session
     */
    public const COOKIE_NAME = 'lemur_member_session';

    /**
     * Initialize session manager
     */
    public static function init(): void
    {
        // Extend WordPress auth cookie expiration for members
        add_filter('auth_cookie_expiration', [self::class, 'filterCookieExpiration'], 10, 3);

        // Create session on login
        add_action('wp_login', [self::class, 'onLogin'], 10, 2);

        // Destroy session on logout
        add_action('wp_logout', [self::class, 'onLogout'], 10);

        // Validate session on init
        add_action('init', [self::class, 'validateSession'], 5);

        // Regenerate session on password change
        add_action('after_password_reset', [self::class, 'onPasswordReset'], 10);
        add_action('profile_update', [self::class, 'onProfileUpdate'], 10, 2);

        // Register settings
        add_action('admin_init', [self::class, 'registerSettings'], 10);
    }

    /**
     * Filter WordPress cookie expiration for Lemur members
     *
     * @param int  $expiration Default expiration
     * @param int  $user_id    User ID
     * @param bool $remember   Whether "remember me" was checked
     * @return int
     */
    public static function filterCookieExpiration(int $expiration, int $user_id, bool $remember): int
    {
        // Only extend for Lemur members
        if (!\Lemur\MemberArea\Access\RolesManager::isLemurUser($user_id)) {
            return $expiration;
        }

        // If "remember me" is checked, use our extended session
        if ($remember) {
            return self::getSessionLifetimeSeconds();
        }

        return $expiration;
    }

    /**
     * Handle user login
     *
     * @param string   $user_login Username
     * @param \WP_User $user       User object
     */
    public static function onLogin(string $user_login, \WP_User $user): void
    {
        // Only manage sessions for Lemur members
        if (!\Lemur\MemberArea\Access\RolesManager::isLemurUser($user->ID)) {
            return;
        }

        self::createSession($user->ID);
    }

    /**
     * Create a new session for user
     *
     * @param int $user_id User ID
     * @return string Session token
     */
    public static function createSession(int $user_id): string
    {
        // Generate secure token
        $token = wp_generate_password(64, false);
        $hashed_token = wp_hash($token);

        // Calculate expiry
        $expiry = time() + self::getSessionLifetimeSeconds();

        // Store session data
        update_user_meta($user_id, self::META_SESSION_TOKEN, $hashed_token);
        update_user_meta($user_id, self::META_SESSION_EXPIRY, $expiry);

        // Store security context (for session validation)
        $ip = self::getClientIP();
        $ua = self::getClientUA();

        if ($ip !== null) {
            update_user_meta($user_id, self::META_SESSION_IP, wp_hash($ip));
        }

        if ($ua !== null) {
            update_user_meta($user_id, self::META_SESSION_UA, wp_hash($ua));
        }

        return $token;
    }

    /**
     * Validate current session
     */
    public static function validateSession(): void
    {
        if (!is_user_logged_in()) {
            return;
        }

        $user_id = get_current_user_id();

        // Only validate for Lemur members
        if (!\Lemur\MemberArea\Access\RolesManager::isLemurUser($user_id)) {
            return;
        }

        // Check session expiry
        $expiry = (int) get_user_meta($user_id, self::META_SESSION_EXPIRY, true);

        if ($expiry > 0 && $expiry < time()) {
            // Session expired, log out
            self::destroySession($user_id);
            wp_logout();

            // Redirect to login with message
            if (!wp_doing_ajax() && !defined('REST_REQUEST')) {
                wp_safe_redirect(add_query_arg('session_expired', '1', wp_login_url()));
                exit;
            }

            return;
        }

        // Validate session security context (IP + UA)
        if (!self::validateSecurityContext($user_id)) {
            // Potential session hijacking - destroy and log out
            self::destroySession($user_id);
            wp_logout();

            // Log the security event
            self::logSecurityEvent($user_id, 'session_context_mismatch');

            if (!wp_doing_ajax() && !defined('REST_REQUEST')) {
                wp_safe_redirect(add_query_arg('security_check', '1', wp_login_url()));
                exit;
            }
        }
    }

    /**
     * Validate security context (IP + UA) to detect session hijacking
     *
     * Uses a two-factor approach: if BOTH IP and UA have changed,
     * it's likely a session hijacking attempt. Single changes (mobile roaming,
     * browser update) are tolerated.
     *
     * @param int $user_id User ID
     * @return bool True if context is valid
     */
    private static function validateSecurityContext(int $user_id): bool
    {
        $stored_ip_hash = get_user_meta($user_id, self::META_SESSION_IP, true);
        $stored_ua_hash = get_user_meta($user_id, self::META_SESSION_UA, true);

        // If no stored context, skip validation (legacy sessions)
        if (empty($stored_ip_hash) && empty($stored_ua_hash)) {
            return true;
        }

        $current_ip = self::getClientIP();
        $current_ua = self::getClientUA();

        $ip_matches = true;
        $ua_matches = true;

        if (!empty($stored_ip_hash) && $current_ip !== null) {
            $ip_matches = wp_hash($current_ip) === $stored_ip_hash;
        }

        if (!empty($stored_ua_hash) && $current_ua !== null) {
            $ua_matches = wp_hash($current_ua) === $stored_ua_hash;
        }

        // Security policy: invalidate if BOTH IP and UA changed
        // This catches session hijacking while allowing legitimate changes
        if (!$ip_matches && !$ua_matches) {
            return false;
        }

        return true;
    }

    /**
     * Log security events for audit
     *
     * @param int    $user_id User ID
     * @param string $event   Event type
     */
    private static function logSecurityEvent(int $user_id, string $event): void
    {
        $ip = self::getClientIP() ?? 'unknown';
        $ua = self::getClientUA() ?? 'unknown';

        error_log(sprintf(
            '[Lemur Security] %s - User: %d, IP: %s, UA: %s',
            $event,
            $user_id,
            $ip,
            substr($ua, 0, 100)
        ));
    }

    /**
     * Handle user logout
     */
    public static function onLogout(): void
    {
        $user_id = get_current_user_id();

        if ($user_id > 0) {
            self::destroySession($user_id);
        }
    }

    /**
     * Destroy user session
     *
     * @param int $user_id User ID
     */
    public static function destroySession(int $user_id): void
    {
        delete_user_meta($user_id, self::META_SESSION_TOKEN);
        delete_user_meta($user_id, self::META_SESSION_EXPIRY);
        delete_user_meta($user_id, self::META_SESSION_IP);
        delete_user_meta($user_id, self::META_SESSION_UA);
    }

    /**
     * Handle password reset
     *
     * @param \WP_User $user User object
     */
    public static function onPasswordReset(\WP_User $user): void
    {
        // Destroy all sessions on password reset
        self::destroySession($user->ID);
    }

    /**
     * Handle profile update (check for password change)
     *
     * @param int              $user_id       User ID
     * @param \WP_User|null    $old_user_data Old user data
     */
    public static function onProfileUpdate(int $user_id, $old_user_data = null): void
    {
        // If password was changed, destroy session
        // WordPress handles this via wp_password_change_notification
        // This is a safety net
    }

    /**
     * Get session lifetime in seconds
     */
    public static function getSessionLifetimeSeconds(): int
    {
        $days = self::getSessionLifetimeDays();

        return $days * DAY_IN_SECONDS;
    }

    /**
     * Get session lifetime in days
     */
    public static function getSessionLifetimeDays(): int
    {
        $days = (int) get_option(self::OPTION_SESSION_DAYS, self::DEFAULT_SESSION_DAYS);

        // Ensure reasonable bounds (1 to 30 days)
        return max(1, min(30, $days));
    }

    /**
     * Set session lifetime in days
     *
     * @param int $days Number of days
     */
    public static function setSessionLifetimeDays(int $days): bool
    {
        $days = max(1, min(30, $days));

        return update_option(self::OPTION_SESSION_DAYS, $days);
    }

    /**
     * Register admin settings
     */
    public static function registerSettings(): void
    {
        register_setting(
            'lemur_member_settings',
            self::OPTION_SESSION_DAYS,
            [
                'type'              => 'integer',
                'sanitize_callback' => [self::class, 'sanitizeSessionDays'],
                'default'           => self::DEFAULT_SESSION_DAYS,
            ]
        );
    }

    /**
     * Sanitize session days option
     *
     * @param mixed $value Input value
     */
    public static function sanitizeSessionDays($value): int
    {
        $days = (int) $value;

        return max(1, min(30, $days));
    }

    /**
     * Get client IP address
     *
     * @return string|null
     */
    private static function getClientIP(): ?string
    {
        // Check various headers (in order of reliability)
        $headers = [
            'HTTP_CF_CONNECTING_IP', // Cloudflare
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_REAL_IP',
            'REMOTE_ADDR',
        ];

        foreach ($headers as $header) {
            if (!empty($_SERVER[$header])) {
                $ip = $_SERVER[$header];

                // Handle comma-separated IPs (X-Forwarded-For)
                if (str_contains($ip, ',')) {
                    $ip = trim(explode(',', $ip)[0]);
                }

                // Validate IP
                if (filter_var($ip, FILTER_VALIDATE_IP)) {
                    return $ip;
                }
            }
        }

        return null;
    }

    /**
     * Get client user agent
     *
     * @return string|null
     */
    private static function getClientUA(): ?string
    {
        if (!empty($_SERVER['HTTP_USER_AGENT'])) {
            return substr(sanitize_text_field($_SERVER['HTTP_USER_AGENT']), 0, 256);
        }

        return null;
    }

    /**
     * Check if session is still valid
     *
     * @param int $user_id User ID
     */
    public static function isSessionValid(int $user_id): bool
    {
        $expiry = (int) get_user_meta($user_id, self::META_SESSION_EXPIRY, true);

        if ($expiry === 0) {
            return true; // No session tracking
        }

        return $expiry > time();
    }

    /**
     * Get session expiry time for user
     *
     * @param int $user_id User ID
     * @return int|null Unix timestamp or null if no session
     */
    public static function getSessionExpiry(int $user_id): ?int
    {
        $expiry = get_user_meta($user_id, self::META_SESSION_EXPIRY, true);

        if ($expiry === '' || $expiry === false) {
            return null;
        }

        return (int) $expiry;
    }

    /**
     * Extend current session
     *
     * @param int $user_id User ID
     */
    public static function extendSession(int $user_id): void
    {
        $new_expiry = time() + self::getSessionLifetimeSeconds();

        update_user_meta($user_id, self::META_SESSION_EXPIRY, $new_expiry);
    }
}
