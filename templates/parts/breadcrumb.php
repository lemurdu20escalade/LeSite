<?php
/**
 * Breadcrumb Navigation
 *
 * Minimal breadcrumb with home icon.
 *
 * @package Lemur
 */

declare(strict_types=1);

// Don't show on front page
if (is_front_page()) {
    return;
}

$items = [];

// Home (icon only)
$items[] = [
    'url' => home_url('/'),
    'label' => __('Accueil', 'lemur'),
    'is_home' => true,
];

// Build breadcrumb based on context
if (is_singular()) {
    $post = get_post();
    $post_type = get_post_type();

    // Post type archive (for CPT)
    if ($post_type !== 'page' && $post_type !== 'post') {
        $post_type_obj = get_post_type_object($post_type);
        if ($post_type_obj && $post_type_obj->has_archive) {
            $items[] = [
                'url' => get_post_type_archive_link($post_type),
                'label' => $post_type_obj->labels->name,
            ];
        }
    }

    // Blog archive for posts
    if ($post_type === 'post') {
        $blog_page = get_page_by_path('actu');
        if ($blog_page) {
            $items[] = [
                'url' => get_permalink($blog_page),
                'label' => get_the_title($blog_page),
            ];
        }
    }

    // Parent page(s) - limit to 1 for cleaner look
    if ($post_type === 'page' && $post && $post->post_parent) {
        $parent = get_post($post->post_parent);
        if ($parent) {
            $items[] = [
                'url' => get_permalink($parent),
                'label' => get_the_title($parent),
            ];
        }
    }

    // Current page
    $items[] = [
        'url' => '',
        'label' => wp_trim_words(get_the_title(), 5, '...'),
    ];

} elseif (is_post_type_archive()) {
    $post_type_obj = get_queried_object();
    if ($post_type_obj) {
        $items[] = [
            'url' => '',
            'label' => $post_type_obj->labels->name,
        ];
    }

} elseif (is_category() || is_tag() || is_tax()) {
    $term = get_queried_object();
    if ($term) {
        $items[] = [
            'url' => '',
            'label' => $term->name,
        ];
    }

} elseif (is_search()) {
    $items[] = [
        'url' => '',
        'label' => __('Recherche', 'lemur'),
    ];

} elseif (is_404()) {
    $items[] = [
        'url' => '',
        'label' => __('Erreur 404', 'lemur'),
    ];

} elseif (is_archive()) {
    $items[] = [
        'url' => '',
        'label' => get_the_archive_title(),
    ];
}

// Don't render if only home
if (count($items) <= 1) {
    return;
}

$last_index = count($items) - 1;
?>
<nav class="breadcrumb" aria-label="<?php esc_attr_e('Fil d\'Ariane', 'lemur'); ?>">
    <ol class="breadcrumb__list" itemscope itemtype="https://schema.org/BreadcrumbList">
        <?php foreach ($items as $index => $item) :
            $is_current = ($index === $last_index);
            $is_home = !empty($item['is_home']);
        ?>
            <li class="breadcrumb__item<?php echo $is_home ? ' breadcrumb__item--home' : ''; ?>" itemprop="itemListElement" itemscope itemtype="https://schema.org/ListItem">
                <?php if ($item['url'] && !$is_current) : ?>
                    <a href="<?php echo esc_url($item['url']); ?>"
                       class="breadcrumb__link"
                       <?php echo $is_home ? 'aria-label="' . esc_attr__('Accueil', 'lemur') . '"' : ''; ?>
                       itemprop="item">
                        <?php if ($is_home) : ?>
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
                                <polyline points="9 22 9 12 15 12 15 22"></polyline>
                            </svg>
                            <span class="sr-only" itemprop="name"><?php echo esc_html($item['label']); ?></span>
                        <?php else : ?>
                            <span itemprop="name"><?php echo esc_html($item['label']); ?></span>
                        <?php endif; ?>
                    </a>
                <?php else : ?>
                    <span class="breadcrumb__current" aria-current="page" itemprop="item">
                        <span itemprop="name"><?php echo esc_html($item['label']); ?></span>
                    </span>
                <?php endif; ?>
                <meta itemprop="position" content="<?php echo esc_attr((string) ($index + 1)); ?>">
            </li>
        <?php endforeach; ?>
    </ol>
</nav>
