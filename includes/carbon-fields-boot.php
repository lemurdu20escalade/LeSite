<?php
/**
 * Carbon Fields Bootstrap
 *
 * @package Lemur
 */

declare(strict_types=1);

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

use Carbon_Fields\Carbon_Fields;

/**
 * Boot Carbon Fields library
 */
function lemur_boot_carbon_fields(): void
{
    if (!class_exists(Carbon_Fields::class)) {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
            error_log('[Lemur] Carbon Fields not found. Run composer install.');
        }
        return;
    }

    Carbon_Fields::boot();
}

add_action('after_setup_theme', 'lemur_boot_carbon_fields', 5);
