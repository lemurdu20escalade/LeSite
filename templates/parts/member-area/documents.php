<?php
/**
 * Documents Library - Member Area
 *
 * Private document library with filtering by category and year.
 *
 * @package Lemur
 */

declare(strict_types=1);

defined('ABSPATH') || exit;

use Lemur\CustomPostTypes\Documents;
use Lemur\MemberArea\Access\Capabilities;

$can_edit = Capabilities::canEditDocuments();

// Get filter params
$current_category = isset($_GET['category']) ? sanitize_text_field($_GET['category']) : '';
$current_year = isset($_GET['year']) ? (int) $_GET['year'] : 0;

// Build query args
$query_args = [];

if (!empty($current_category)) {
    $query_args['tax_query'] = [
        [
            'taxonomy' => Documents::TAXONOMY,
            'field'    => 'slug',
            'terms'    => $current_category,
        ],
    ];
}

if ($current_year > 0) {
    $query_args['meta_query'] = [
        [
            'key'   => '_' . Documents::FIELD_YEAR,
            'value' => $current_year,
        ],
    ];
}

$documents = Documents::getDocuments($query_args);
$categories = Documents::getCategories();

// Get unique years
$years = [];
$all_docs = Documents::getDocuments();
foreach ($all_docs as $doc) {
    $year = carbon_get_post_meta($doc->ID, Documents::FIELD_YEAR);
    if ($year && !in_array($year, $years, true)) {
        $years[] = $year;
    }
}
rsort($years);
?>

<div class="member-documents">
    <!-- Header -->
    <header class="documents__header">
        <div class="documents__title-group">
            <h1 class="documents__title"><?php esc_html_e('Documents', 'lemur'); ?></h1>
            <span class="documents__count"><?php echo count($documents); ?> <?php esc_html_e('document(s)', 'lemur'); ?></span>
        </div>

        <?php if ($can_edit): ?>
            <a href="<?php echo esc_url(admin_url('post-new.php?post_type=' . Documents::POST_TYPE)); ?>"
               class="documents__add-btn">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <line x1="12" y1="5" x2="12" y2="19"/>
                    <line x1="5" y1="12" x2="19" y2="12"/>
                </svg>
                <?php esc_html_e('Ajouter', 'lemur'); ?>
            </a>
        <?php endif; ?>
    </header>

    <!-- Navigation -->
    <?php lemur_render_member_nav('documents'); ?>

    <!-- Filters -->
    <div class="documents__filters">
        <div class="documents__filter">
            <label for="category-filter" class="documents__filter-label">
                <?php esc_html_e('Catégorie :', 'lemur'); ?>
            </label>
            <select id="category-filter" class="documents__filter-select" onchange="window.location.href=this.value">
                <option value="<?php echo esc_url(remove_query_arg('category')); ?>">
                    <?php esc_html_e('Toutes', 'lemur'); ?>
                </option>
                <?php foreach ($categories as $cat): ?>
                    <?php
                    $cat_url = add_query_arg('category', $cat->slug);
                    if ($current_year > 0) {
                        $cat_url = add_query_arg('year', $current_year, $cat_url);
                    }
                    ?>
                    <option value="<?php echo esc_url($cat_url); ?>" <?php selected($current_category, $cat->slug); ?>>
                        <?php echo esc_html($cat->name); ?> (<?php echo esc_html($cat->count); ?>)
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="documents__filter">
            <label for="year-filter" class="documents__filter-label">
                <?php esc_html_e('Année :', 'lemur'); ?>
            </label>
            <select id="year-filter" class="documents__filter-select" onchange="window.location.href=this.value">
                <option value="<?php echo esc_url(remove_query_arg('year')); ?>">
                    <?php esc_html_e('Toutes', 'lemur'); ?>
                </option>
                <?php foreach ($years as $year): ?>
                    <?php
                    $year_url = add_query_arg('year', $year);
                    if (!empty($current_category)) {
                        $year_url = add_query_arg('category', $current_category, $year_url);
                    }
                    ?>
                    <option value="<?php echo esc_url($year_url); ?>" <?php selected($current_year, (int) $year); ?>>
                        <?php echo esc_html($year); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <?php if (!empty($current_category) || $current_year > 0): ?>
            <a href="<?php echo esc_url(remove_query_arg(['category', 'year'])); ?>" class="documents__filter-reset">
                <?php esc_html_e('Effacer les filtres', 'lemur'); ?>
            </a>
        <?php endif; ?>
    </div>

    <!-- Documents List -->
    <?php if (!empty($documents)): ?>
        <ul class="documents__list">
            <?php foreach ($documents as $document):
                $file_id = carbon_get_post_meta($document->ID, Documents::FIELD_FILE);
                $file_path = $file_id ? get_attached_file((int) $file_id) : null;
                $file_ext = $file_path ? strtoupper(pathinfo($file_path, PATHINFO_EXTENSION)) : '';
                $file_size = $file_path && file_exists($file_path) ? size_format(filesize($file_path)) : '';

                $description = carbon_get_post_meta($document->ID, Documents::FIELD_DESCRIPTION);
                $doc_year = carbon_get_post_meta($document->ID, Documents::FIELD_YEAR);
                $visibility = carbon_get_post_meta($document->ID, Documents::FIELD_VISIBILITY);
                $downloads = (int) carbon_get_post_meta($document->ID, Documents::FIELD_DOWNLOAD_COUNT);

                $terms = wp_get_post_terms($document->ID, Documents::TAXONOMY);
                $category = !empty($terms) && !is_wp_error($terms) ? $terms[0]->name : '';

                $download_url = rest_url('lemur/v1/download/' . $document->ID);
            ?>
                <li class="documents__item" data-type="<?php echo esc_attr($file_ext); ?>">
                    <div class="documents__item-icon">
                        <?php if ($file_ext === 'PDF'): ?>
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                <path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/>
                                <polyline points="14 2 14 8 20 8"/>
                                <line x1="16" y1="13" x2="8" y2="13"/>
                                <line x1="16" y1="17" x2="8" y2="17"/>
                                <polyline points="10 9 9 9 8 9"/>
                            </svg>
                        <?php else: ?>
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                <path d="M13 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V9z"/>
                                <polyline points="13 2 13 9 20 9"/>
                            </svg>
                        <?php endif; ?>
                    </div>

                    <div class="documents__item-content">
                        <h3 class="documents__item-title">
                            <?php echo esc_html(get_the_title($document)); ?>
                            <?php if ($visibility === Documents::VISIBILITY_BUREAU): ?>
                                <span class="documents__item-badge" title="<?php esc_attr_e('Bureau uniquement', 'lemur'); ?>">
                                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                        <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/>
                                        <path d="M7 11V7a5 5 0 0110 0v4"/>
                                    </svg>
                                </span>
                            <?php endif; ?>
                        </h3>

                        <?php if ($description): ?>
                            <p class="documents__item-description"><?php echo esc_html($description); ?></p>
                        <?php endif; ?>

                        <div class="documents__item-meta">
                            <?php if ($category): ?>
                                <span class="documents__item-category"><?php echo esc_html($category); ?></span>
                            <?php endif; ?>

                            <?php if ($doc_year): ?>
                                <span class="documents__item-year"><?php echo esc_html($doc_year); ?></span>
                            <?php endif; ?>

                            <?php if ($file_ext): ?>
                                <span class="documents__item-type"><?php echo esc_html($file_ext); ?></span>
                            <?php endif; ?>

                            <?php if ($file_size): ?>
                                <span class="documents__item-size"><?php echo esc_html($file_size); ?></span>
                            <?php endif; ?>

                            <?php if ($downloads > 0): ?>
                                <span class="documents__item-downloads">
                                    <?php echo esc_html($downloads); ?> <?php esc_html_e('téléchargement(s)', 'lemur'); ?>
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <a href="<?php echo esc_url($download_url . '?_wpnonce=' . wp_create_nonce('wp_rest')); ?>"
                       class="documents__item-download"
                       download
                       title="<?php esc_attr_e('Télécharger', 'lemur'); ?>">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/>
                            <polyline points="7 10 12 15 17 10"/>
                            <line x1="12" y1="15" x2="12" y2="3"/>
                        </svg>
                        <span class="screen-reader-text"><?php esc_html_e('Télécharger', 'lemur'); ?></span>
                    </a>

                    <?php if ($can_edit): ?>
                        <a href="<?php echo esc_url(get_edit_post_link($document->ID)); ?>"
                           class="documents__item-edit"
                           title="<?php esc_attr_e('Modifier', 'lemur'); ?>">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                <path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"/>
                                <path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/>
                            </svg>
                            <span class="screen-reader-text"><?php esc_html_e('Modifier', 'lemur'); ?></span>
                        </a>
                    <?php endif; ?>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php else: ?>
        <div class="documents__empty">
            <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                <path d="M13 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V9z"/>
                <polyline points="13 2 13 9 20 9"/>
            </svg>
            <p><?php esc_html_e('Aucun document trouvé.', 'lemur'); ?></p>
            <?php if (!empty($current_category) || $current_year > 0): ?>
                <a href="<?php echo esc_url(remove_query_arg(['category', 'year'])); ?>" class="documents__empty-link">
                    <?php esc_html_e('Voir tous les documents', 'lemur'); ?>
                </a>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>
