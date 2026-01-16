<?php
/**
 * Navigation management
 *
 * @package Lemur\Core
 */

declare(strict_types=1);

namespace Lemur\Core;

/**
 * Handle theme navigation menus
 */
class Navigation
{
    /**
     * Menu locations
     */
    public const LOCATIONS = [
        'primary' => 'Menu principal',
        'footer'  => 'Menu pied de page',
    ];

    /**
     * Initialize navigation
     * Note: Called from Theme::init() which runs on after_setup_theme,
     * so we register menus directly instead of adding another hook.
     */
    public static function init(): void
    {
        self::registerMenus();
    }

    /**
     * Register navigation menus
     */
    public static function registerMenus(): void
    {
        register_nav_menus(
            array_map(
                static fn(string $label): string => __($label, 'lemur'),
                self::LOCATIONS
            )
        );
    }

    /**
     * Get menu items for a location
     *
     * @param string $location Menu location
     * @return array<int, \WP_Post>|false
     */
    public static function getMenuItems(string $location): array|false
    {
        $locations = get_nav_menu_locations();

        if (!isset($locations[$location])) {
            return false;
        }

        return wp_get_nav_menu_items($locations[$location]) ?: false;
    }

    /**
     * Render a navigation menu
     *
     * @param string               $location   Menu location
     * @param array<string, mixed> $args       Additional wp_nav_menu arguments
     */
    public static function render(string $location, array $args = []): void
    {
        $defaults = [
            'theme_location' => $location,
            'container'      => 'nav',
            'container_class' => 'navigation navigation--' . $location,
            'menu_class'     => 'navigation__list',
            'fallback_cb'    => false,
        ];

        wp_nav_menu(array_merge($defaults, $args));
    }
}
