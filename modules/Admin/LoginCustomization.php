<?php

/**
 * Login Page Customization
 *
 * Customizes the WordPress login page with theme branding.
 *
 * @package Lemur\Admin
 */

declare(strict_types=1);

namespace Lemur\Admin;

use Lemur\Core\Theme;

/**
 * Login page branding and customization
 */
class LoginCustomization
{
    /**
     * Theme colors (matching _tokens.css)
     */
    private const COLOR_PRIMARY_500 = '#22c55e';
    private const COLOR_PRIMARY_600 = '#16a34a';
    private const COLOR_PRIMARY_700 = '#15803d';
    private const COLOR_NEUTRAL_50 = '#fafafa';
    private const COLOR_NEUTRAL_100 = '#f5f5f5';
    private const COLOR_NEUTRAL_200 = '#e5e5e5';
    private const COLOR_NEUTRAL_900 = '#171717';

    /**
     * Initialize the module
     */
    public static function init(): void
    {
        add_action('login_enqueue_scripts', [self::class, 'enqueueStyles'], 10);
        add_filter('login_headerurl', [self::class, 'getLogoUrl'], 10);
        add_filter('login_headertext', [self::class, 'getLogoTitle'], 10);
        add_action('login_footer', [self::class, 'addFooterCredits'], 10);
    }

    /**
     * Enqueue login page styles
     */
    public static function enqueueStyles(): void
    {
        $logo_url = self::getLogoImageUrl();
        ?>
        <style type="text/css">
            /* Login page body */
            body.login {
                background-color: <?php echo esc_attr(self::COLOR_NEUTRAL_50); ?>;
                font-family: 'Inter', system-ui, -apple-system, sans-serif;
            }

            /* Logo container */
            #login h1 {
                height: 100px;
                margin-bottom: 24px;
            }

            /* Logo link */
            #login h1 a,
            .login h1 a {
                display: block;
                height: 100%;
                width: 100%;
                background-image: url("<?php echo esc_url($logo_url); ?>") !important;
                background-repeat: no-repeat;
                background-position: center;
                background-size: contain;
                outline: none;
            }

            /* Login form */
            .login form,
            #loginform {
                background: #fff;
                border: 1px solid <?php echo esc_attr(self::COLOR_NEUTRAL_200); ?>;
                border-radius: 8px;
                box-shadow: none;
                padding: 24px;
            }

            /* Labels */
            .login label {
                color: <?php echo esc_attr(self::COLOR_NEUTRAL_900); ?>;
                font-weight: 500;
            }

            /* Inputs */
            .login input[type="text"],
            .login input[type="password"],
            .login input[type="email"] {
                background: <?php echo esc_attr(self::COLOR_NEUTRAL_50); ?>;
                border: 1px solid <?php echo esc_attr(self::COLOR_NEUTRAL_200); ?>;
                border-radius: 6px;
                padding: 10px 12px;
                font-size: 16px;
                transition: border-color 0.2s ease, box-shadow 0.2s ease;
            }

            .login input[type="text"]:focus,
            .login input[type="password"]:focus,
            .login input[type="email"]:focus {
                border-color: <?php echo esc_attr(self::COLOR_PRIMARY_500); ?>;
                box-shadow: 0 0 0 2px rgba(34, 197, 94, 0.2);
                outline: none;
            }

            /* Submit button */
            .login .button-primary,
            #wp-submit {
                background: <?php echo esc_attr(self::COLOR_PRIMARY_600); ?>;
                border: none;
                border-radius: 6px;
                color: #fff !important;
                font-weight: 600;
                padding: 8px 24px;
                height: auto;
                line-height: 1.5;
                text-shadow: none;
                transition: background-color 0.2s ease;
            }

            .login .button-primary:hover,
            .login .button-primary:focus,
            #wp-submit:hover,
            #wp-submit:focus {
                background: <?php echo esc_attr(self::COLOR_PRIMARY_700); ?>;
                color: #fff !important;
            }

            /* Links */
            .login #nav a,
            .login #backtoblog a,
            .login .privacy-policy-page-link a {
                color: <?php echo esc_attr(self::COLOR_NEUTRAL_900); ?>;
                text-decoration: none;
                transition: color 0.2s ease;
            }

            .login #nav a:hover,
            .login #backtoblog a:hover,
            .login .privacy-policy-page-link a:hover {
                color: <?php echo esc_attr(self::COLOR_PRIMARY_600); ?>;
            }

            /* Error messages */
            .login #login_error {
                border-left-color: #ef4444;
                background: #fef2f2;
                color: #991b1b;
            }

            /* Success messages */
            .login .message {
                border-left-color: <?php echo esc_attr(self::COLOR_PRIMARY_500); ?>;
                background: #f0fdf4;
                color: #166534;
            }

            /* Remember me checkbox */
            .login .forgetmenot {
                margin-top: 8px;
            }

            /* Footer text */
            .login #backtoblog,
            .login #nav {
                padding: 8px 24px;
            }

            /* Privacy policy link */
            .privacy-policy-page-link {
                margin-top: 16px;
            }

            /* Custom footer credits */
            .lemur-login-credits {
                text-align: center;
                color: <?php echo esc_attr(self::COLOR_NEUTRAL_900); ?>;
                font-size: 12px;
                margin-top: 24px;
                opacity: 0.6;
            }

            .lemur-login-credits a {
                color: inherit;
                text-decoration: none;
            }

            .lemur-login-credits a:hover {
                text-decoration: underline;
            }

            /* Hide WordPress logo link text for better a11y */
            .login h1 a {
                text-indent: -9999px;
                overflow: hidden;
            }
        </style>
        <?php
    }

    /**
     * Get logo URL (links to site home)
     *
     * @return string Home URL
     */
    public static function getLogoUrl(): string
    {
        return home_url('/');
    }

    /**
     * Get logo title
     *
     * @return string Site name
     */
    public static function getLogoTitle(): string
    {
        return get_bloginfo('name');
    }

    /**
     * Get logo image URL
     *
     * @return string Logo image URL
     */
    private static function getLogoImageUrl(): string
    {
        // Check for custom logo set in Customizer
        $custom_logo_id = get_theme_mod('custom_logo');

        if ($custom_logo_id) {
            $logo_url = wp_get_attachment_image_url($custom_logo_id, 'full');
            if ($logo_url) {
                return $logo_url;
            }
        }

        // Fallback to theme logo file
        $theme_logo = Theme::getPath('assets/images/logo.svg');
        if (file_exists($theme_logo)) {
            return Theme::getUri('assets/images/logo.svg');
        }

        // Ultimate fallback to site icon
        $site_icon_id = get_option('site_icon');
        if ($site_icon_id) {
            $icon_url = wp_get_attachment_image_url($site_icon_id, 'full');
            if ($icon_url) {
                return $icon_url;
            }
        }

        // Return empty if no logo found (will use default WordPress logo)
        return '';
    }

    /**
     * Add footer credits
     */
    public static function addFooterCredits(): void
    {
        $site_name = get_bloginfo('name');
        ?>
        <p class="lemur-login-credits">
            &copy; <?php echo esc_html(gmdate('Y')); ?>
            <a href="<?php echo esc_url(home_url('/')); ?>"><?php echo esc_html($site_name); ?></a>
        </p>
        <?php
    }
}
