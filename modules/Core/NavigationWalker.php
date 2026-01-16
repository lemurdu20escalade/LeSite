<?php
/**
 * Navigation Walker
 *
 * Custom walker for accessible navigation menus with ARIA support.
 *
 * @package Lemur\Core
 */

declare(strict_types=1);

namespace Lemur\Core;

use Walker_Nav_Menu;

/**
 * Custom navigation walker for primary menu
 */
class NavigationWalker extends Walker_Nav_Menu
{
    /**
     * Starts the list before the elements are added.
     *
     * @param string   $output Used to append additional content (passed by reference).
     * @param int      $depth  Depth of menu item.
     * @param \stdClass|null $args   An object of wp_nav_menu() arguments.
     */
    public function start_lvl(&$output, $depth = 0, $args = null): void
    {
        $indent = str_repeat("\t", $depth);
        $output .= "\n{$indent}<ul class=\"sub-menu\" role=\"menu\">\n";
    }

    /**
     * Ends the list after the elements are added.
     *
     * @param string   $output Used to append additional content (passed by reference).
     * @param int      $depth  Depth of menu item.
     * @param \stdClass|null $args   An object of wp_nav_menu() arguments.
     */
    public function end_lvl(&$output, $depth = 0, $args = null): void
    {
        $indent = str_repeat("\t", $depth);
        $output .= "{$indent}</ul>\n";
    }

    /**
     * Starts the element output.
     *
     * @param string   $output Used to append additional content (passed by reference).
     * @param \WP_Post $item   Menu item data object.
     * @param int      $depth  Depth of menu item.
     * @param \stdClass|null $args   An object of wp_nav_menu() arguments.
     * @param int      $id     Current item ID.
     */
    public function start_el(&$output, $item, $depth = 0, $args = null, $id = 0): void
    {
        $args = $args ?? new \stdClass();
        $indent = ($depth > 0) ? str_repeat("\t", $depth) : '';

        // Build classes
        $classes = empty($item->classes) ? [] : (array) $item->classes;
        $classes[] = 'nav-menu__item';

        if ($depth === 0) {
            $classes[] = 'nav-menu__item--top';
        }

        $hasChildren = in_array('menu-item-has-children', $classes, true);
        if ($hasChildren) {
            $classes[] = 'has-submenu';
        }

        // Current state
        $isCurrent = $item->current ?? false;
        $isCurrentParent = $item->current_item_parent ?? false;
        $isCurrentAncestor = $item->current_item_ancestor ?? false;

        if ($isCurrent) {
            $classes[] = 'is-active';
        }
        if ($isCurrentParent || $isCurrentAncestor) {
            $classes[] = 'is-active-parent';
        }

        $classNames = implode(' ', array_filter($classes));

        // Build li attributes
        $liAtts = [];
        $liAtts['class'] = $classNames;
        $liAtts['role'] = 'none';

        $liAttributes = $this->buildAttributes($liAtts);

        $output .= "{$indent}<li{$liAttributes}>";

        // Build link attributes
        $linkAtts = [];
        $linkAtts['href'] = !empty($item->url) ? $item->url : '';
        $linkAtts['class'] = 'nav-menu__link';
        $linkAtts['role'] = 'menuitem';

        if ($isCurrent) {
            $linkAtts['aria-current'] = 'page';
        }

        if ($hasChildren && $depth === 0) {
            $linkAtts['aria-haspopup'] = 'true';
            $linkAtts['aria-expanded'] = 'false';
        }

        // External link handling
        if (!empty($item->target) && $item->target === '_blank') {
            $linkAtts['target'] = '_blank';
            $linkAtts['rel'] = 'noopener noreferrer';
        }

        $linkAttributes = $this->buildAttributes($linkAtts);

        // Get title
        $title = apply_filters('the_title', $item->title, $item->ID);
        $title = apply_filters('nav_menu_item_title', $title, $item, $args, $depth);

        // Build link
        $itemOutput = '';
        $itemOutput .= $args->before ?? '';
        $itemOutput .= '<a' . $linkAttributes . '>';
        $itemOutput .= ($args->link_before ?? '') . esc_html($title) . ($args->link_after ?? '');

        // Add dropdown indicator for items with children
        if ($hasChildren && $depth === 0) {
            $itemOutput .= '<svg class="nav-menu__chevron" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M6 9l6 6 6-6"/></svg>';
        }

        $itemOutput .= '</a>';
        $itemOutput .= $args->after ?? '';

        $output .= apply_filters('walker_nav_menu_start_el', $itemOutput, $item, $depth, $args);
    }

    /**
     * Ends the element output.
     *
     * @param string   $output Used to append additional content (passed by reference).
     * @param \WP_Post $item   Menu item data object.
     * @param int      $depth  Depth of menu item.
     * @param \stdClass|null $args   An object of wp_nav_menu() arguments.
     */
    public function end_el(&$output, $item, $depth = 0, $args = null): void
    {
        $output .= "</li>\n";
    }

    /**
     * Build HTML attributes string from array
     *
     * @param array<string, string|null> $atts Attributes array
     * @return string HTML attributes string
     */
    private function buildAttributes(array $atts): string
    {
        $attributes = '';
        foreach ($atts as $attr => $value) {
            if ($value !== null && $value !== '') {
                $attributes .= ' ' . esc_attr($attr) . '="' . esc_attr($value) . '"';
            }
        }
        return $attributes;
    }
}
