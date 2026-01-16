<?php
/**
 * Lemur Escalade Theme
 *
 * @package Lemur
 */

declare(strict_types=1);

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Theme constants
define('LEMUR_VERSION', '1.0.0');
define('LEMUR_PATH', get_template_directory());
define('LEMUR_URI', get_template_directory_uri());

// Load Composer autoloader
$autoloader = LEMUR_PATH . '/vendor/autoload.php';

if (!file_exists($autoloader)) {
    add_action('admin_notices', static function (): void {
        echo '<div class="notice notice-error"><p>';
        echo esc_html__('Lemur: Veuillez exécuter "composer install" dans le dossier du thème.', 'lemur');
        echo '</p></div>';
    });
    return;
}

require_once $autoloader;

// Initialize theme
add_action('after_setup_theme', static function (): void {
    \Lemur\Core\Theme::init();
});
