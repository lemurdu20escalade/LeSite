<?php
/**
 * Single Post Template
 *
 * Displays single blog posts with gallery support.
 *
 * @package Lemur
 */

declare(strict_types=1);

use Lemur\Fields\PostFields;

get_header();

while (have_posts()) :
    the_post();

    $post_meta = PostFields::getPostMeta(get_the_ID());
    $gallery = $post_meta['gallery'];
    $location = $post_meta['location'];
    $participants = $post_meta['participants'];
?>

<main id="main" class="site-main">
    <article id="post-<?php the_ID(); ?>" <?php post_class('single-post'); ?>>

        <?php if (has_post_thumbnail()) : ?>
            <div class="single-post__hero">
                <?php the_post_thumbnail('lemur-hero'); ?>
            </div>
        <?php endif; ?>

        <div class="container">
            <header class="single-post__header">
                <div class="single-post__meta">
                    <time class="single-post__date" datetime="<?php echo esc_attr(get_the_date('c')); ?>">
                        <?php echo esc_html(get_the_date('j F Y')); ?>
                    </time>

                    <?php if ($location) : ?>
                        <span class="single-post__location">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path>
                                <circle cx="12" cy="10" r="3"></circle>
                            </svg>
                            <?php echo esc_html($location); ?>
                        </span>
                    <?php endif; ?>

                    <?php if ($participants) : ?>
                        <span class="single-post__participants">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                                <circle cx="9" cy="7" r="4"></circle>
                                <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                                <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                            </svg>
                            <?php echo esc_html($participants); ?>
                        </span>
                    <?php endif; ?>
                </div>

                <h1 class="single-post__title"><?php the_title(); ?></h1>
            </header>

            <div class="single-post__content prose">
                <?php the_content(); ?>
            </div>

            <?php if (!empty($gallery)) : ?>
                <section class="single-post__gallery">
                    <h2 class="single-post__gallery-title"><?php esc_html_e('Photos', 'lemur'); ?></h2>
                    <div class="gallery-grid gallery-grid--3">
                        <?php foreach ($gallery as $image_id) :
                            $image_url = wp_get_attachment_image_url($image_id, 'lemur-gallery-full');
                            $image_thumb = wp_get_attachment_image_url($image_id, 'lemur-gallery-thumb');
                            $image_alt = get_post_meta($image_id, '_wp_attachment_image_alt', true);
                            $image_caption = wp_get_attachment_caption($image_id);
                        ?>
                            <a href="<?php echo esc_url($image_url); ?>"
                               class="gallery-grid__item glightbox"
                               data-lightbox="post-gallery"
                               data-gallery="post-gallery"
                               data-title="<?php echo esc_attr($image_caption ?: $image_alt); ?>">
                                <img src="<?php echo esc_url($image_thumb); ?>"
                                     alt="<?php echo esc_attr($image_alt); ?>"
                                     loading="lazy">
                            </a>
                        <?php endforeach; ?>
                    </div>
                </section>
            <?php endif; ?>

            <footer class="single-post__footer">
                <a href="<?php echo esc_url(get_permalink(get_page_by_path('actu'))); ?>" class="single-post__back">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="19" y1="12" x2="5" y2="12"></line>
                        <polyline points="12 19 5 12 12 5"></polyline>
                    </svg>
                    <?php esc_html_e('Retour aux actualités', 'lemur'); ?>
                </a>
            </footer>
        </div>
    </article>
</main>

<?php
endwhile;

get_footer();
