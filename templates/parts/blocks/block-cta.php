<?php
/**
 * Block: Call to Action
 *
 * Attention-grabbing CTA section.
 *
 * @package Lemur
 */

declare(strict_types=1);

$data = lemur_get_block_data();

$title = $data['title'] ?? '';
$description = $data['description'] ?? '';
$button_text = $data['button_text'] ?? '';
$button_link = $data['button_link'] ?? '';
$block_id = 'cta-' . lemur_get_block_index();
$classes = ['block-cta'];
$style_parts = [];

// Sanitize background color
$bg_color = lemur_sanitize_css_color($data['background_color'] ?? '');
if ($bg_color) {
    $style_parts[] = "background-color: {$bg_color}";
}

// Sanitize background image URL
$bg_image = lemur_sanitize_css_url($data['background_image'] ?? '');
if ($bg_image) {
    $style_parts[] = "background-image: url('{$bg_image}')";
    $classes[] = 'block-cta--has-bg-image';
}

$style = implode('; ', $style_parts);
?>

<section
    id="<?php echo esc_attr($block_id); ?>"
    class="<?php echo esc_attr(implode(' ', $classes)); ?>"
    <?php echo $style ? 'style="' . esc_attr($style) . '"' : ''; ?>
>
    <div class="block-cta__container container">
        <?php if ($title) : ?>
            <h2 class="block-cta__title"><?php echo esc_html($title); ?></h2>
        <?php endif; ?>

        <?php if ($description) : ?>
            <p class="block-cta__description"><?php echo esc_html($description); ?></p>
        <?php endif; ?>

        <?php if ($button_text && $button_link) : ?>
            <a href="<?php echo esc_url($button_link); ?>" class="btn btn--primary btn--lg block-cta__button">
                <?php echo esc_html($button_text); ?>
            </a>
        <?php endif; ?>
    </div>
</section>
