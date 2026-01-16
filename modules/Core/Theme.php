<?php
/**
 * Main Theme class
 *
 * @package Lemur\Core
 */

declare(strict_types=1);

namespace Lemur\Core;

/**
 * Theme bootstrap and initialization
 */
class Theme
{
    /**
     * Whether the theme has been initialized
     */
    private static bool $initialized = false;

    /**
     * Initialize the theme
     */
    public static function init(): void
    {
        if (self::$initialized) {
            return;
        }

        self::$initialized = true;

        // Register theme supports
        self::registerSupports();

        // Initialize core modules
        self::initModules();
    }

    /**
     * Register theme supports
     */
    private static function registerSupports(): void
    {
        // Let WordPress manage the document title
        add_theme_support('title-tag');

        // Enable support for Post Thumbnails
        add_theme_support('post-thumbnails');

        // Switch default core markup to output valid HTML5
        // Note: comment-form and comment-list removed (comments disabled)
        add_theme_support('html5', [
            'search-form',
            'gallery',
            'caption',
            'style',
            'script',
        ]);

        // Add support for responsive embedded content
        add_theme_support('responsive-embeds');

        // Add support for selective refresh of widgets
        add_theme_support('customize-selective-refresh-widgets');

        // Add support for custom logo
        add_theme_support('custom-logo', [
            'height'      => 100,
            'width'       => 400,
            'flex-height' => true,
            'flex-width'  => true,
        ]);
    }

    /**
     * Initialize theme modules
     */
    private static function initModules(): void
    {
        // Core modules
        Assets::init();
        Navigation::init();
        SvgUpload::init();
        DisableGutenberg::init();
        ConsoleLog::init();
        TemplateLoader::init();

        // Images optimization
        \Lemur\Images\Optimizer::init();

        // Fields (Carbon Fields containers)
        \Lemur\Fields\ThemeOptions::init();
        \Lemur\Fields\PageFields::init();
        \Lemur\Fields\PostFields::init();
        \Lemur\Fields\SEOFields::init();

        // Custom Post Types
        \Lemur\CustomPostTypes\Events::init();
        \Lemur\CustomPostTypes\Members::init();
        \Lemur\CustomPostTypes\FAQ::init();
        \Lemur\CustomPostTypes\Collectives::init();
        \Lemur\CustomPostTypes\Documents::init();
        \Lemur\CustomPostTypes\Tasks::init();

        // SEO modules
        \Lemur\SEO\SchemaOrg::init();
        \Lemur\SEO\MetaTags::init();
        \Lemur\SEO\Robots::init();
        \Lemur\SEO\Performance::init();

        // Security modules
        \Lemur\Security\Hardening::init();
        \Lemur\Security\EmailObfuscator::init();
        \Lemur\Security\Headers::init();
        \Lemur\Security\Nonces::init();

        // Admin modules
        \Lemur\Admin\CacheControl::init();
        \Lemur\Admin\LoginCustomization::init();

        // Member Area modules
        \Lemur\MemberArea\Access\RolesManager::init();
        \Lemur\MemberArea\Access\Capabilities::init();
        \Lemur\MemberArea\Access\AccessControl::init();
        \Lemur\MemberArea\Auth\BackupAuth::init();
        \Lemur\MemberArea\Auth\SessionManager::init();
        \Lemur\Rest\MembersEndpoint::init();
        \Lemur\Rest\TasksEndpoint::init();
    }

    /**
     * Check if we're in development mode
     */
    public static function isDevMode(): bool
    {
        return defined('LEMUR_DEV_MODE') && LEMUR_DEV_MODE;
    }

    /**
     * Get the Vite dev server URL
     */
    public static function getViteDevUrl(): string
    {
        return defined('LEMUR_VITE_DEV_URL') ? LEMUR_VITE_DEV_URL : 'http://localhost:5173';
    }

    /**
     * Get theme directory path
     */
    public static function getPath(string $path = ''): string
    {
        return get_template_directory() . ($path ? '/' . ltrim($path, '/') : '');
    }

    /**
     * Get theme directory URI
     */
    public static function getUri(string $path = ''): string
    {
        return get_template_directory_uri() . ($path ? '/' . ltrim($path, '/') : '');
    }

    /**
     * Get theme version
     */
    public static function getVersion(): string
    {
        return defined('LEMUR_VERSION') ? LEMUR_VERSION : '1.0.0';
    }
}
