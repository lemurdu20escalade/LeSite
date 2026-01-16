<?php
/**
 * Archive: Événements
 *
 * Displays events with filters and pagination.
 *
 * @package Lemur
 */

declare(strict_types=1);

use Lemur\CustomPostTypes\Events;

get_header();

// Get filter parameters
// phpcs:ignore WordPress.Security.NonceVerification.Recommended
$current_type = isset($_GET['type']) ? sanitize_text_field(wp_unslash($_GET['type'])) : '';
// phpcs:ignore WordPress.Security.NonceVerification.Recommended
$show_past = isset($_GET['show_past']);

// Get event types for filter
$event_types = get_terms([
    'taxonomy'   => Events::TAXONOMY,
    'hide_empty' => true,
]);
?>

<main id="main-content" class="site-main">
    <div class="container">
        <header class="archive-header">
            <h1 class="archive-header__title">
                <?php esc_html_e('Événements', 'lemur'); ?>
            </h1>
            <p class="archive-header__description">
                <?php esc_html_e('Sorties, compétitions et événements du club.', 'lemur'); ?>
            </p>
        </header>

        <div class="archive-controls">
            <nav class="archive-filters" role="navigation" aria-label="<?php esc_attr_e('Filtrer les événements', 'lemur'); ?>">
                <?php
                $all_url = remove_query_arg('type', get_post_type_archive_link(Events::POST_TYPE));
                if ($show_past) {
                    $all_url = add_query_arg('show_past', '1', $all_url);
                }
                $is_all_active = empty($current_type);
                ?>
                <a
                    href="<?php echo esc_url($all_url); ?>"
                    class="filter-tab<?php echo $is_all_active ? ' is-active' : ''; ?>"
                    <?php echo $is_all_active ? 'aria-current="page"' : ''; ?>
                >
                    <?php esc_html_e('Tous', 'lemur'); ?>
                </a>

                <?php if ($event_types && !is_wp_error($event_types)) : ?>
                    <?php foreach ($event_types as $type) : ?>
                        <?php
                        $type_url = add_query_arg('type', $type->slug, get_post_type_archive_link(Events::POST_TYPE));
                        if ($show_past) {
                            $type_url = add_query_arg('show_past', '1', $type_url);
                        }
                        $is_active = $current_type === $type->slug;
                        ?>
                        <a
                            href="<?php echo esc_url($type_url); ?>"
                            class="filter-tab<?php echo $is_active ? ' is-active' : ''; ?>"
                            <?php echo $is_active ? 'aria-current="page"' : ''; ?>
                        >
                            <?php echo esc_html($type->name); ?>
                        </a>
                    <?php endforeach; ?>
                <?php endif; ?>
            </nav>

            <form method="get" class="archive-options">
                <?php if ($current_type) : ?>
                    <input type="hidden" name="type" value="<?php echo esc_attr($current_type); ?>">
                <?php endif; ?>

                <label class="archive-option">
                    <input
                        type="checkbox"
                        name="show_past"
                        value="1"
                        <?php checked($show_past); ?>
                        data-auto-submit
                    >
                    <?php esc_html_e('Inclure les événements passés', 'lemur'); ?>
                </label>

                <noscript>
                    <button type="submit" class="btn btn--sm">
                        <?php esc_html_e('Appliquer', 'lemur'); ?>
                    </button>
                </noscript>
            </form>
        </div>

        <?php if (have_posts()) : ?>
            <div class="events-grid">
                <?php while (have_posts()) : the_post(); ?>
                    <?php get_template_part('templates/parts/cards/event-card'); ?>
                <?php endwhile; ?>
            </div>

            <?php the_posts_pagination([
                'mid_size'  => 2,
                'prev_text' => sprintf(
                    '<span class="sr-only">%s</span>%s',
                    esc_html__('Page précédente', 'lemur'),
                    lemur_ui_icon('chevron-right', ['class' => 'pagination__icon pagination__icon--prev'])
                ),
                'next_text' => sprintf(
                    '<span class="sr-only">%s</span>%s',
                    esc_html__('Page suivante', 'lemur'),
                    lemur_ui_icon('chevron-right', ['class' => 'pagination__icon'])
                ),
            ]); ?>
        <?php else : ?>
            <div class="no-results">
                <p><?php esc_html_e('Aucun événement à venir pour le moment.', 'lemur'); ?></p>

                <?php if (!$show_past) : ?>
                    <?php
                    $past_url = add_query_arg('show_past', '1', get_post_type_archive_link(Events::POST_TYPE));
                    if ($current_type) {
                        $past_url = add_query_arg('type', $current_type, $past_url);
                    }
                    ?>
                    <a href="<?php echo esc_url($past_url); ?>" class="btn btn--outline">
                        <?php esc_html_e('Voir les événements passés', 'lemur'); ?>
                    </a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</main>

<?php
get_footer();
