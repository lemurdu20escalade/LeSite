<?php

/**
 * Security Headers
 *
 * @package Lemur\Security
 */

declare(strict_types=1);

namespace Lemur\Security;

/**
 * Configures HTTP security headers
 */
class Headers
{
    /**
     * Headers to send
     */
    private array $headers = [];

    /**
     * Whether HTTPS is enabled
     */
    private bool $isHttps;

    /**
     * Initialize the module
     */
    public static function init(): void
    {
        $instance = new self();
        $instance->isHttps = is_ssl();

        add_action('send_headers', [$instance, 'sendSecurityHeaders'], 10);
        add_action('admin_init', [$instance, 'sendAdminHeaders'], 10);

        // Admin notice for HTTPS check
        if (current_user_can('manage_options')) {
            add_action('admin_notices', [$instance, 'checkHttpsNotice'], 10);
        }
    }

    /**
     * Send security headers on frontend
     */
    public function sendSecurityHeaders(): void
    {
        // Don't send headers on admin
        if (is_admin()) {
            return;
        }

        $this->buildHeaders();

        foreach ($this->headers as $header => $value) {
            if ($value !== '' && $value !== null) {
                header("{$header}: {$value}");
            }
        }
    }

    /**
     * Additional headers for admin
     */
    public function sendAdminHeaders(): void
    {
        // Stricter X-Frame-Options in admin
        header('X-Frame-Options: DENY');
    }

    /**
     * Build the headers list
     */
    private function buildHeaders(): void
    {
        $this->headers = [
            // Prevent MIME type sniffing
            'X-Content-Type-Options' => 'nosniff',

            // Clickjacking protection
            'X-Frame-Options' => 'SAMEORIGIN',

            // XSS browser protection (legacy)
            'X-XSS-Protection' => '1; mode=block',

            // Referrer control
            'Referrer-Policy' => 'strict-origin-when-cross-origin',

            // Browser feature permissions
            'Permissions-Policy' => $this->getPermissionsPolicy(),

            // Content Security Policy
            'Content-Security-Policy' => $this->getContentSecurityPolicy(),

            // Cross-Origin policies
            'Cross-Origin-Opener-Policy' => 'same-origin',
            'Cross-Origin-Resource-Policy' => 'same-origin',
        ];

        // HSTS only on HTTPS
        if ($this->isHttps) {
            $this->headers['Strict-Transport-Security'] = 'max-age=31536000; includeSubDomains; preload';
        }

        // Cache control based on login status
        if (is_user_logged_in()) {
            $this->headers['Cache-Control'] = 'private, no-cache, no-store, must-revalidate';
            $this->headers['Pragma'] = 'no-cache';
        } else {
            // Allow some caching for visitors
            $this->headers['Cache-Control'] = 'public, max-age=3600';
        }
    }

    /**
     * Generate Permissions-Policy header
     */
    private function getPermissionsPolicy(): string
    {
        $policies = [
            // Disable unused features
            'accelerometer' => '()',
            'autoplay' => '(self)',
            'camera' => '()',
            'cross-origin-isolated' => '()',
            'display-capture' => '()',
            'encrypted-media' => '()',
            'fullscreen' => '(self)',
            'geolocation' => '()',
            'gyroscope' => '()',
            'keyboard-map' => '()',
            'magnetometer' => '()',
            'microphone' => '()',
            'midi' => '()',
            'payment' => '()',
            'picture-in-picture' => '()',
            'publickey-credentials-get' => '()',
            'screen-wake-lock' => '()',
            'sync-xhr' => '()',
            'usb' => '()',
            'web-share' => '(self)',
            'xr-spatial-tracking' => '()',
        ];

        $parts = [];
        foreach ($policies as $feature => $value) {
            $parts[] = "{$feature}={$value}";
        }

        return implode(', ', $parts);
    }

    /**
     * Generate Content-Security-Policy header
     */
    private function getContentSecurityPolicy(): string
    {
        $site_url = home_url();
        $vite_dev_url = defined('LEMUR_VITE_DEV_URL') ? LEMUR_VITE_DEV_URL : '';

        $directives = [
            'default-src' => ["'self'"],
            'script-src' => [
                "'self'",
                "'unsafe-inline'", // Required for Alpine.js x-data inline
                "'unsafe-eval'",   // May be needed for some libraries
                'https://www.googletagmanager.com',
                'https://www.google-analytics.com',
            ],
            'style-src' => [
                "'self'",
                "'unsafe-inline'", // Required for inline styles
                'https://fonts.googleapis.com',
            ],
            'img-src' => [
                "'self'",
                'data:',
                'https:',
                'https://www.google-analytics.com',
            ],
            'font-src' => [
                "'self'",
                'https://fonts.gstatic.com',
            ],
            'connect-src' => [
                "'self'",
                'https://www.google-analytics.com',
            ],
            'frame-src' => [
                "'self'",
                'https://www.google.com', // For reCAPTCHA
                'https://www.youtube.com', // For videos
            ],
            'frame-ancestors' => ["'self'"],
            'form-action' => ["'self'"],
            'base-uri' => ["'self'"],
            'object-src' => ["'none'"],
            'upgrade-insecure-requests' => [],
        ];

        // Add Vite dev server in dev mode
        if ($vite_dev_url && defined('LEMUR_DEV_MODE') && LEMUR_DEV_MODE) {
            $directives['script-src'][] = $vite_dev_url;
            $directives['connect-src'][] = $vite_dev_url;
            $directives['connect-src'][] = str_replace('http:', 'ws:', $vite_dev_url);
        }

        // Build CSP string
        $csp_parts = [];
        foreach ($directives as $directive => $values) {
            if (empty($values)) {
                $csp_parts[] = $directive;
            } else {
                $csp_parts[] = $directive . ' ' . implode(' ', $values);
            }
        }

        return implode('; ', $csp_parts);
    }

    /**
     * Display admin notice if not using HTTPS
     */
    public function checkHttpsNotice(): void
    {
        // Only on dashboard
        $screen = get_current_screen();
        if ($screen === null || $screen->id !== 'dashboard') {
            return;
        }

        if (!is_ssl()) {
            printf(
                '<div class="notice notice-warning is-dismissible"><p><strong>%s</strong> %s</p></div>',
                esc_html__('Lemur Sécurité :', 'lemur'),
                esc_html__('Le site n\'utilise pas HTTPS. Les headers HSTS ne seront pas actifs.', 'lemur')
            );
        }
    }

    /**
     * Test headers (for admin page)
     */
    public static function testHeaders(): array
    {
        $url = home_url('/');
        $response = wp_remote_head($url, ['sslverify' => false]);

        if (is_wp_error($response)) {
            return ['error' => $response->get_error_message()];
        }

        $headers = wp_remote_retrieve_headers($response);
        $required_headers = [
            'x-content-type-options',
            'x-frame-options',
            'x-xss-protection',
            'referrer-policy',
            'content-security-policy',
        ];

        $results = [];
        foreach ($required_headers as $header) {
            $results[$header] = isset($headers[$header]) ? $headers[$header] : 'MISSING';
        }

        return $results;
    }
}
