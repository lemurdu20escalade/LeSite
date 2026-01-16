<?php
/**
 * Template Name: Galerie
 *
 * Displays photo gallery with album filters, responsive grid and accessible lightbox.
 *
 * @package Lemur
 */

declare(strict_types=1);

get_header();

$albums = lemur_get_gallery_albums();
$all_images = lemur_get_all_gallery_images();
?>

<main id="main-content" class="site-main">
    <div class="container">
        <header class="page-header page-header--centered">
            <h1 class="page-header__title"><?php the_title(); ?></h1>
            <?php if (has_excerpt()) : ?>
                <p class="page-header__description"><?php echo esc_html(get_the_excerpt()); ?></p>
            <?php endif; ?>
        </header>

        <!-- Album Filters -->
        <?php if (!empty($albums) && count($albums) > 1) : ?>
            <nav class="gallery-filters" aria-label="<?php esc_attr_e('Filtrer par album', 'lemur'); ?>">
                <button
                    type="button"
                    class="gallery-filter gallery-filter--active"
                    data-filter="all"
                    aria-pressed="true"
                >
                    <?php esc_html_e('Toutes les photos', 'lemur'); ?>
                    <span class="gallery-filter__count">(<?php echo count($all_images); ?>)</span>
                </button>
                <?php foreach ($albums as $album) : ?>
                    <button
                        type="button"
                        class="gallery-filter"
                        data-filter="<?php echo esc_attr($album['slug']); ?>"
                        aria-pressed="false"
                    >
                        <?php echo esc_html($album['name']); ?>
                        <span class="gallery-filter__count">(<?php echo count($album['images']); ?>)</span>
                    </button>
                <?php endforeach; ?>
            </nav>
        <?php endif; ?>

        <!-- Photo Grid -->
        <div class="gallery-grid" x-data="galleryLightbox()">
            <?php if (!empty($all_images)) : ?>
                <?php foreach ($all_images as $index => $image) :
                    $image_id = $image['id'];
                    $album_slug = $image['album_slug'] ?? 'all';
                    $image_data = wp_get_attachment_image_src($image_id, 'lemur-gallery-thumb');
                    $alt = get_post_meta($image_id, '_wp_attachment_image_alt', true);
                    $caption = wp_get_attachment_caption($image_id);

                    if (!$image_data) {
                        continue;
                    }
                ?>
                    <figure
                        class="gallery-item"
                        data-album="<?php echo esc_attr($album_slug); ?>"
                        data-index="<?php echo esc_attr($index); ?>"
                    >
                        <button
                            type="button"
                            class="gallery-item__button"
                            @click="openLightbox(<?php echo esc_attr($index); ?>)"
                            aria-label="<?php printf(esc_attr__('Agrandir l\'image %d', 'lemur'), $index + 1); ?>"
                        >
                            <img
                                src="<?php echo esc_url($image_data[0]); ?>"
                                alt="<?php echo esc_attr($alt); ?>"
                                width="<?php echo esc_attr($image_data[1]); ?>"
                                height="<?php echo esc_attr($image_data[2]); ?>"
                                loading="lazy"
                                class="gallery-item__image"
                            >
                            <span class="gallery-item__overlay" aria-hidden="true">
                                <?php lemur_the_ui_icon('external-link', ['class' => 'gallery-item__zoom', 'width' => 32, 'height' => 32]); ?>
                            </span>
                        </button>
                        <?php if ($caption) : ?>
                            <figcaption class="gallery-item__caption"><?php echo esc_html($caption); ?></figcaption>
                        <?php endif; ?>
                    </figure>
                <?php endforeach; ?>
            <?php else : ?>
                <p class="gallery-empty"><?php esc_html_e('Aucune photo pour le moment.', 'lemur'); ?></p>
            <?php endif; ?>

            <!-- Lightbox Component -->
            <?php get_template_part('templates/parts/components/lightbox'); ?>
        </div>

        <!-- Gallery Data for Lightbox -->
        <?php if (!empty($all_images)) : ?>
            <script type="application/json" id="gallery-data">
                <?php echo wp_json_encode(lemur_prepare_gallery_data($all_images), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>
            </script>
        <?php endif; ?>
    </div>
</main>

<?php
get_footer();
