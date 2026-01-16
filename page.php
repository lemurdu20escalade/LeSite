<?php
/**
 * Page Template
 *
 * Template for displaying pages with the page builder.
 * Uses Carbon Fields complex field for section management.
 *
 * @package Lemur
 */

declare(strict_types=1);

get_header();

$page_sections = carbon_get_the_post_meta('page_sections');
$has_sections = !empty($page_sections) && is_array($page_sections);
?>

<main id="main" class="site-main">
    <?php if ($has_sections) : ?>
        <?php lemur_render_page_sections(); ?>
    <?php else : ?>
        <div class="container">
            <?php while (have_posts()) : the_post(); ?>
                <article id="post-<?php the_ID(); ?>" <?php post_class('page-content'); ?>>
                    <?php if (has_post_thumbnail()) : ?>
                        <div class="page-content__thumbnail">
                            <?php the_post_thumbnail('large'); ?>
                        </div>
                    <?php endif; ?>

                    <header class="page-content__header">
                        <h1 class="page-content__title"><?php the_title(); ?></h1>
                    </header>

                    <div class="page-content__body prose">
                        <?php the_content(); ?>
                    </div>
                </article>
            <?php endwhile; ?>
        </div>
    <?php endif; ?>
</main>

<?php
get_footer();
