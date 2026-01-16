<?php
/**
 * Main Template File (Stub)
 *
 * Minimal template required by WordPress.
 * Will be expanded with proper templates in Epic 4.
 *
 * @package Lemur
 */

declare(strict_types=1);

get_header();
?>

<main id="main" class="site-main">
    <div class="container">
        <?php if (have_posts()) : ?>
            <?php while (have_posts()) : the_post(); ?>
                <article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
                    <h2><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2>
                    <?php the_excerpt(); ?>
                </article>
            <?php endwhile; ?>
            <?php the_posts_pagination(); ?>
        <?php else : ?>
            <p><?php esc_html_e('Aucun contenu trouvé.', 'lemur'); ?></p>
        <?php endif; ?>
    </div>
</main>

<?php
get_footer();
