<?php
/**
 * Template Name: Le Club
 *
 * Template for the "About Us / Le Club" page with page builder support.
 *
 * @package Lemur
 */

get_header();
?>

<main id="main-content" class="site-main">
    <?php
    // Page builder for modular sections
    if (lemur_has_page_sections()) {
        lemur_render_page_sections();
    } else {
        // Fallback to default content
        while (have_posts()) :
            the_post();
            ?>
            <article class="page-content">
                <div class="container">
                    <?php if (has_post_thumbnail()) : ?>
                        <div class="page-content__hero">
                            <?php the_post_thumbnail('lemur-hero', ['class' => 'page-content__image']); ?>
                        </div>
                    <?php endif; ?>

                    <header class="page-content__header">
                        <h1 class="page-content__title"><?php the_title(); ?></h1>
                    </header>

                    <div class="page-content__body">
                        <?php the_content(); ?>
                    </div>
                </div>
            </article>
            <?php
        endwhile;
    }
    ?>
</main>

<?php
get_footer();
