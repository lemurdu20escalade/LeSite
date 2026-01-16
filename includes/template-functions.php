<?php
/**
 * Template functions for use in theme templates
 *
 * @package Lemur
 */

declare(strict_types=1);

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Render a template part with data
 *
 * @param string               $slug Template slug
 * @param string|null          $name Template name
 * @param array<string, mixed> $args Arguments to pass to the template
 */
function lemur_template_part(string $slug, ?string $name = null, array $args = []): void
{
    get_template_part($slug, $name, $args);
}

/**
 * Render a card component
 *
 * @param string               $type Card type (event, member, etc.)
 * @param array<string, mixed> $args Card data
 */
function lemur_card(string $type, array $args = []): void
{
    get_template_part('templates/parts/cards/card', $type, $args);
}

/**
 * Render a component
 *
 * @param string               $name Component name
 * @param array<string, mixed> $args Component data
 */
function lemur_component(string $name, array $args = []): void
{
    get_template_part('templates/parts/components/' . $name, null, $args);
}

/**
 * Render a section
 *
 * @param string               $name Section name
 * @param array<string, mixed> $args Section data
 */
function lemur_section(string $name, array $args = []): void
{
    get_template_part('templates/parts/sections/section', $name, $args);
}

/**
 * Output the site logo or title
 *
 * @param bool $echo Whether to echo or return
 * @return string|void
 */
function lemur_site_logo(bool $echo = true): ?string
{
    $output = '';

    if (has_custom_logo()) {
        $output = get_custom_logo();
    } else {
        $output = sprintf(
            '<a href="%s" class="site-title" rel="home">%s</a>',
            esc_url(home_url('/')),
            esc_html(get_bloginfo('name'))
        );
    }

    if ($echo) {
        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        echo $output;
        return null;
    }

    return $output;
}

/**
 * Output pagination
 *
 * @param array<string, mixed> $args Pagination arguments
 */
function lemur_pagination(array $args = []): void
{
    $defaults = [
        'prev_text' => __('Pr&eacute;c&eacute;dent', 'lemur'),
        'next_text' => __('Suivant', 'lemur'),
        'class'     => 'pagination',
    ];

    $args = wp_parse_args($args, $defaults);

    the_posts_pagination($args);
}

/**
 * Get breadcrumb trail
 *
 * @return array<int, array{title: string, url: string|null}>
 */
function lemur_get_breadcrumbs(): array
{
    $breadcrumbs = [
        [
            'title' => __('Accueil', 'lemur'),
            'url'   => home_url('/'),
        ],
    ];

    if (is_singular()) {
        $post = get_post();

        if ($post && $post->post_parent) {
            $ancestors = get_post_ancestors($post->ID);

            foreach (array_reverse($ancestors) as $ancestor) {
                $breadcrumbs[] = [
                    'title' => get_the_title($ancestor),
                    'url'   => get_permalink($ancestor),
                ];
            }
        }

        $breadcrumbs[] = [
            'title' => get_the_title(),
            'url'   => null,
        ];
    } elseif (is_archive()) {
        $breadcrumbs[] = [
            'title' => get_the_archive_title(),
            'url'   => null,
        ];
    } elseif (is_search()) {
        $breadcrumbs[] = [
            'title' => sprintf(__('Recherche : %s', 'lemur'), get_search_query()),
            'url'   => null,
        ];
    }

    return $breadcrumbs;
}

/**
 * Render breadcrumbs
 */
function lemur_breadcrumbs(): void
{
    $breadcrumbs = lemur_get_breadcrumbs();

    if (count($breadcrumbs) <= 1) {
        return;
    }

    echo '<nav class="breadcrumbs" aria-label="' . esc_attr__('Fil d\'Ariane', 'lemur') . '">';
    echo '<ol class="breadcrumbs__list" itemscope itemtype="https://schema.org/BreadcrumbList">';

    foreach ($breadcrumbs as $index => $item) {
        $position = $index + 1;
        $isLast = $position === count($breadcrumbs);

        echo '<li class="breadcrumbs__item" itemprop="itemListElement" itemscope itemtype="https://schema.org/ListItem">';

        if ($item['url'] && !$isLast) {
            printf(
                '<a href="%s" class="breadcrumbs__link" itemprop="item"><span itemprop="name">%s</span></a>',
                esc_url($item['url']),
                esc_html($item['title'])
            );
        } else {
            printf(
                '<span class="breadcrumbs__current" itemprop="name">%s</span>',
                esc_html($item['title'])
            );
        }

        echo '<meta itemprop="position" content="' . esc_attr((string) $position) . '">';
        echo '</li>';
    }

    echo '</ol>';
    echo '</nav>';
}
