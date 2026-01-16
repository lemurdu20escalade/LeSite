<?php
/**
 * Block: Gallery
 *
 * Image gallery with optional lightbox.
 *
 * @package Lemur
 */

declare(strict_types=1);

$data = lemur_get_block_data();

$title = $data['title'] ?? '';
$images = $data['images'] ?? [];
$columns = $data['columns'] ?? '3';
$lightbox = !empty($data['lightbox_enabled']);

$block_id = 'gallery-' . lemur_get_block_index();

if (empty($images)) {
    return;
}
?>

<section id="<?php echo esc_attr($block_id); ?>" class="block-gallery">
    <div class="block-gallery__container container">
        <?php if ($title) : ?>
            <h2 class="block-gallery__title"><?php echo esc_html($title); ?></h2>
        <?php endif; ?>

        <div
            class="block-gallery__grid block-gallery__grid--<?php echo esc_attr($columns); ?>"
            <?php echo $lightbox ? 'x-data="lightbox"' : ''; ?>
        >
            <?php foreach ($images as $index => $image_id) : ?>
                <?php
                $image_url = wp_get_attachment_image_url($image_id, 'large');
                $image_full = wp_get_attachment_image_url($image_id, 'full');
                $image_alt = get_post_meta($image_id, '_wp_attachment_image_alt', true);

                if (!$image_url) {
                    continue;
                }
                ?>
                <?php if ($lightbox) : ?>
                    <button
                        type="button"
                        class="block-gallery__item"
                        @click="open('<?php echo esc_url($image_full); ?>', '<?php echo esc_attr($image_alt); ?>')"
                        aria-label="<?php echo esc_attr(sprintf(__('Agrandir l\'image %d', 'lemur'), $index + 1)); ?>"
                    >
                        <?php
                        echo wp_get_attachment_image($image_id, 'medium_large', false, [
                            'class' => 'block-gallery__img',
                            'loading' => 'lazy',
                        ]);
                        ?>
                    </button>
                <?php else : ?>
                    <div class="block-gallery__item">
                        <?php
                        echo wp_get_attachment_image($image_id, 'medium_large', false, [
                            'class' => 'block-gallery__img',
                            'loading' => 'lazy',
                        ]);
                        ?>
                    </div>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>
    </div>
</section>
