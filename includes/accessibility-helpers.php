<?php

/**
 * Accessibility Helper Functions
 *
 * @package Lemur
 */

declare(strict_types=1);

/**
 * Generate a unique ID for ARIA associations
 */
function lemur_unique_id(string $prefix = 'lemur'): string
{
    static $counter = 0;
    return $prefix . '-' . (++$counter);
}

/**
 * Generate ARIA attributes for an expandable element
 */
function lemur_aria_expandable(string $controls_id, bool $expanded = false): string
{
    return sprintf(
        'aria-expanded="%s" aria-controls="%s"',
        $expanded ? 'true' : 'false',
        esc_attr($controls_id)
    );
}

/**
 * Generate attributes for an external link
 */
function lemur_external_link_attrs(): string
{
    return 'target="_blank" rel="noopener noreferrer"';
}

/**
 * Generate sr-only label for external link
 */
function lemur_external_link_label(): string
{
    return '<span class="sr-only">' . esc_html__('(ouvre dans un nouvel onglet)', 'lemur') . '</span>';
}

/**
 * Generate an image with required alt attribute
 */
function lemur_img(int $attachment_id, string $size = 'large', array $attrs = []): string
{
    if ($attachment_id <= 0) {
        return '';
    }

    $alt = get_post_meta($attachment_id, '_wp_attachment_image_alt', true);

    // Fallback to title if no alt
    if ($alt === '' || $alt === false) {
        $alt = get_the_title($attachment_id);
    }

    $default_attrs = [
        'alt' => $alt,
        'loading' => 'lazy',
    ];

    $attrs = wp_parse_args($attrs, $default_attrs);

    return wp_get_attachment_image($attachment_id, $size, false, $attrs);
}

/**
 * Calculate contrast ratio between two colors
 *
 * @param string $color1 Hex color (e.g., '#ffffff')
 * @param string $color2 Hex color (e.g., '#000000')
 * @return float Contrast ratio
 */
function lemur_contrast_ratio(string $color1, string $color2): float
{
    $l1 = lemur_relative_luminance($color1);
    $l2 = lemur_relative_luminance($color2);

    $lighter = max($l1, $l2);
    $darker = min($l1, $l2);

    return ($lighter + 0.05) / ($darker + 0.05);
}

/**
 * Calculate relative luminance of a color
 *
 * @param string $hex Hex color
 * @return float Relative luminance (0-1)
 */
function lemur_relative_luminance(string $hex): float
{
    $hex = ltrim($hex, '#');

    if (strlen($hex) === 3) {
        $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
    }

    $r = hexdec(substr($hex, 0, 2)) / 255;
    $g = hexdec(substr($hex, 2, 2)) / 255;
    $b = hexdec(substr($hex, 4, 2)) / 255;

    $r = $r <= 0.03928 ? $r / 12.92 : pow(($r + 0.055) / 1.055, 2.4);
    $g = $g <= 0.03928 ? $g / 12.92 : pow(($g + 0.055) / 1.055, 2.4);
    $b = $b <= 0.03928 ? $b / 12.92 : pow(($b + 0.055) / 1.055, 2.4);

    return 0.2126 * $r + 0.7152 * $g + 0.0722 * $b;
}

/**
 * Check if contrast meets WCAG AA requirements
 *
 * @param string $color1 Hex color
 * @param string $color2 Hex color
 * @param bool $large_text Whether text is large (18pt+ or 14pt bold)
 * @return bool True if contrast is sufficient
 */
function lemur_contrast_is_valid(string $color1, string $color2, bool $large_text = false): bool
{
    $ratio = lemur_contrast_ratio($color1, $color2);
    $minimum = $large_text ? 3.0 : 4.5;

    return $ratio >= $minimum;
}

/**
 * Generate a heading with dynamic level
 *
 * @param array $args {
 *     @type string $level Heading level (h1-h6)
 *     @type string $text Heading text
 *     @type string $class CSS classes
 *     @type string $id Element ID
 * }
 */
function lemur_heading(array $args): void
{
    $defaults = [
        'level' => 'h2',
        'text' => '',
        'class' => '',
        'id' => '',
    ];

    $args = wp_parse_args($args, $defaults);

    $valid_levels = ['h1', 'h2', 'h3', 'h4', 'h5', 'h6'];
    $level = in_array($args['level'], $valid_levels, true) ? $args['level'] : 'h2';

    $attrs = '';
    if ($args['id'] !== '') {
        $attrs .= sprintf(' id="%s"', esc_attr($args['id']));
    }
    if ($args['class'] !== '') {
        $attrs .= sprintf(' class="%s"', esc_attr($args['class']));
    }

    printf(
        '<%1$s%2$s>%3$s</%1$s>',
        $level,
        $attrs,
        esc_html($args['text'])
    );
}

/**
 * Check if current page is using keyboard navigation
 * (Helper for server-side checks - actual detection is in JS)
 */
function lemur_is_keyboard_navigation(): bool
{
    return false; // Actual detection is client-side via JS
}
