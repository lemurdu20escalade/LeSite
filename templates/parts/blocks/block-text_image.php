<?php
/**
 * Block: Text + Image
 *
 * Two-column layout with rich text and image.
 *
 * @package Lemur
 */

declare(strict_types=1);

$data = lemur_get_block_data();

$title = $data['title'] ?? '';
$content = $data['content'] ?? '';
$image_id = $data['image'] ?? 0;
$layout = $data['layout'] ?? 'image_right';

$block_id = 'text-image-' . lemur_get_block_index();
$classes = [
    'block-text-image',
    "block-text-image--{$layout}",
];

// Sanitize background color
$bg_color = lemur_sanitize_css_color($data['background_color'] ?? '');
$style = $bg_color ? "background-color: {$bg_color};" : '';
?>

<section
    id="<?php echo esc_attr($block_id); ?>"
    class="<?php echo esc_attr(implode(' ', $classes)); ?>"
    <?php echo $style ? 'style="' . esc_attr($style) . '"' : ''; ?>
>
    <div class="block-text-image__container container">
        <div class="block-text-image__content">
            <?php if ($title) : ?>
                <h2 class="block-text-image__title"><?php echo esc_html($title); ?></h2>
            <?php endif; ?>

            <?php if ($content) : ?>
                <div class="block-text-image__text prose">
                    <?php echo wp_kses_post($content); ?>
                </div>
            <?php endif; ?>
        </div>

        <?php if ($image_id) : ?>
            <div class="block-text-image__image">
                <?php
                echo wp_get_attachment_image($image_id, 'large', false, [
                    'class' => 'block-text-image__img',
                    'loading' => 'lazy',
                ]);
                ?>
            </div>
        <?php endif; ?>
    </div>
</section>
