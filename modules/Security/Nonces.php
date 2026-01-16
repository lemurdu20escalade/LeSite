<?php

/**
 * CSRF Protection with Nonces
 *
 * @package Lemur\Security
 */

declare(strict_types=1);

namespace Lemur\Security;

/**
 * Centralized nonce management for CSRF protection
 */
class Nonces
{
    /**
     * Nonce lifetime in seconds
     */
    public const NONCE_LIFETIME = 12 * HOUR_IN_SECONDS;

    /**
     * Predefined action mappings
     */
    private static array $actions = [
        'lemur_form' => 'lemur_form_action',
        'lemur_ajax' => 'lemur_ajax_action',
        'lemur_contact' => 'lemur_contact_action',
        'lemur_event_registration' => 'lemur_event_registration_action',
        'lemur_document' => 'lemur_document_action',
    ];

    /**
     * Initialize the module
     */
    public static function init(): void
    {
        add_action('wp_enqueue_scripts', [self::class, 'localizeNonces'], 10);
        add_action('wp_ajax_lemur_refresh_nonce', [self::class, 'handleRefreshNonce'], 10);
        add_action('wp_ajax_nopriv_lemur_refresh_nonce', [self::class, 'handleRefreshNonce'], 10);
    }

    /**
     * Pass nonces to JavaScript
     */
    public static function localizeNonces(): void
    {
        $nonces = [];
        foreach (self::$actions as $key => $action) {
            $nonces[$key] = wp_create_nonce($action);
        }

        wp_localize_script('lemur-main', 'lemurNonces', $nonces);
        wp_localize_script('lemur-main', 'lemurAjax', [
            'url' => admin_url('admin-ajax.php'),
            'restUrl' => rest_url('lemur/v1/'),
            'restNonce' => wp_create_nonce('wp_rest'),
        ]);
    }

    /**
     * Create a nonce for a specific action
     */
    public static function create(string $action): string
    {
        $nonce_action = self::$actions[$action] ?? $action;
        return wp_create_nonce($nonce_action);
    }

    /**
     * Verify a nonce
     *
     * @return bool True if valid, false otherwise
     */
    public static function verify(string $nonce, string $action): bool
    {
        if ($nonce === '') {
            self::logFailure($action, 'empty_nonce');
            return false;
        }

        $nonce_action = self::$actions[$action] ?? $action;
        $result = wp_verify_nonce($nonce, $nonce_action);

        if ($result === false) {
            self::logFailure($action, 'invalid_nonce');
            return false;
        }

        return true;
    }

    /**
     * Verify a nonce and stop execution if invalid
     */
    public static function verifyOrDie(string $nonce, string $action): void
    {
        if (!self::verify($nonce, $action)) {
            wp_die(
                esc_html__('Erreur de sécurité. Veuillez rafraîchir la page et réessayer.', 'lemur'),
                esc_html__('Erreur de sécurité', 'lemur'),
                ['response' => 403]
            );
        }
    }

    /**
     * Generate a hidden nonce field for forms
     */
    public static function field(string $action, bool $referer = true): void
    {
        $nonce_action = self::$actions[$action] ?? $action;
        wp_nonce_field($nonce_action, '_lemur_nonce', $referer);
    }

    /**
     * Get nonce field as string
     */
    public static function getField(string $action, bool $referer = true): string
    {
        $nonce_action = self::$actions[$action] ?? $action;
        return wp_nonce_field($nonce_action, '_lemur_nonce', $referer, false);
    }

    /**
     * Verify nonce from an AJAX request
     */
    public static function verifyAjax(string $action): bool
    {
        // phpcs:disable WordPress.Security.NonceVerification.Recommended
        $nonce = '';
        if (isset($_REQUEST['_lemur_nonce'])) {
            $nonce = sanitize_text_field(wp_unslash($_REQUEST['_lemur_nonce']));
        } elseif (isset($_REQUEST['nonce'])) {
            $nonce = sanitize_text_field(wp_unslash($_REQUEST['nonce']));
        }
        // phpcs:enable

        return self::verify($nonce, $action);
    }

    /**
     * Verify nonce from a REST request
     */
    public static function verifyRest(\WP_REST_Request $request): bool
    {
        $nonce = $request->get_header('X-WP-Nonce');
        if ($nonce === null) {
            return false;
        }

        return wp_verify_nonce($nonce, 'wp_rest') !== false;
    }

    /**
     * Handle nonce refresh AJAX request
     */
    public static function handleRefreshNonce(): void
    {
        wp_send_json_success([
            'nonce' => wp_create_nonce('wp_rest'),
            'nonces' => array_map(function ($action) {
                return wp_create_nonce($action);
            }, self::$actions),
        ]);
    }

    /**
     * Log nonce verification failures
     */
    private static function logFailure(string $action, string $reason): void
    {
        if (!defined('WP_DEBUG') || !WP_DEBUG) {
            return;
        }

        $ip = isset($_SERVER['REMOTE_ADDR']) ? sanitize_text_field(wp_unslash($_SERVER['REMOTE_ADDR'])) : 'unknown';

        error_log(sprintf(
            '[Lemur Security] Nonce verification failed - Action: %s, Reason: %s, IP: %s, User: %d',
            $action,
            $reason,
            $ip,
            get_current_user_id()
        ));
    }

    /**
     * Add a custom action mapping
     */
    public static function registerAction(string $key, string $action): void
    {
        self::$actions[$key] = $action;
    }

    /**
     * Get all registered actions
     */
    public static function getActions(): array
    {
        return self::$actions;
    }
}
