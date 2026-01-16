<?php
/**
 * Archive des collectifs
 *
 * @package Lemur
 */

declare(strict_types=1);

get_header();
?>

<main id="main-content" class="site-main">
    <div class="container">
        <header class="archive-header">
            <h1 class="archive-header__title"><?php esc_html_e('Les Collectifs', 'lemur'); ?></h1>
            <p class="archive-header__description">
                <?php esc_html_e('Le club fonctionne par collectifs auxquels chacun peut adhérer. Groupes de travail bénévoles qui font vivre l\'association.', 'lemur'); ?>
            </p>
        </header>

        <?php if (have_posts()) : ?>
            <div class="collectifs-grid">
                <?php while (have_posts()) : the_post(); ?>
                    <?php get_template_part('templates/parts/cards/collective', 'card'); ?>
                <?php endwhile; ?>
            </div>

            <?php the_posts_pagination([
                'prev_text' => '&larr;',
                'next_text' => '&rarr;',
            ]); ?>
        <?php else : ?>
            <p class="no-results"><?php esc_html_e('Aucun collectif pour le moment.', 'lemur'); ?></p>
        <?php endif; ?>
    </div>
</main>

<?php
get_footer();
