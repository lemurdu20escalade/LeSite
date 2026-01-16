<?php
/**
 * Template Name: Actualités
 * Template Post Type: page
 *
 * Displays a grid of blog posts (news/articles).
 *
 * @package Lemur
 */

declare(strict_types=1);

get_header();

// Query blog posts
$paged = get_query_var('paged') ? get_query_var('paged') : 1;
$posts_query = new WP_Query([
    'post_type'      => 'post',
    'post_status'    => 'publish',
    'posts_per_page' => 12,
    'paged'          => $paged,
    'orderby'        => 'date',
    'order'          => 'DESC',
]);
?>

<main id="main" class="site-main">
    <div class="container">
        <header class="page-header">
            <h1 class="page-header__title"><?php the_title(); ?></h1>
            <?php if (get_the_content()) : ?>
                <div class="page-header__intro">
                    <?php the_content(); ?>
                </div>
            <?php endif; ?>
        </header>

        <?php if ($posts_query->have_posts()) : ?>
            <div class="posts-grid">
                <?php while ($posts_query->have_posts()) : $posts_query->the_post(); ?>
                    <article id="post-<?php the_ID(); ?>" <?php post_class('post-card'); ?>>
                        <?php if (has_post_thumbnail()) : ?>
                            <a href="<?php the_permalink(); ?>" class="post-card__image">
                                <?php the_post_thumbnail('lemur-card-large'); ?>
                            </a>
                        <?php endif; ?>

                        <div class="post-card__content">
                            <time class="post-card__date" datetime="<?php echo esc_attr(get_the_date('c')); ?>">
                                <?php echo esc_html(get_the_date('j F Y')); ?>
                            </time>

                            <h2 class="post-card__title">
                                <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
                            </h2>

                            <?php if (has_excerpt()) : ?>
                                <p class="post-card__excerpt"><?php echo esc_html(get_the_excerpt()); ?></p>
                            <?php endif; ?>

                            <a href="<?php the_permalink(); ?>" class="post-card__link">
                                <?php esc_html_e('Lire la suite', 'lemur'); ?>
                                <span aria-hidden="true">&rarr;</span>
                            </a>
                        </div>
                    </article>
                <?php endwhile; ?>
            </div>

            <?php if ($posts_query->max_num_pages > 1) : ?>
                <nav class="posts-pagination">
                    <?php
                    echo paginate_links([
                        'total'     => $posts_query->max_num_pages,
                        'current'   => $paged,
                        'prev_text' => __('&laquo; Précédent', 'lemur'),
                        'next_text' => __('Suivant &raquo;', 'lemur'),
                    ]);
                    ?>
                </nav>
            <?php endif; ?>

            <?php wp_reset_postdata(); ?>

        <?php else : ?>
            <p class="no-posts"><?php esc_html_e('Aucune actualité pour le moment.', 'lemur'); ?></p>
        <?php endif; ?>
    </div>
</main>

<?php
get_footer();
