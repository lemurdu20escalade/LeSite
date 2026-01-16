<?php
/**
 * Block: Hero
 *
 * Full-width hero section with background image/video and CTA.
 *
 * @package Lemur
 */

declare(strict_types=1);

$data = lemur_get_block_data();

$title = $data['title'] ?? '';
$subtitle = $data['subtitle'] ?? '';
$bg_image = $data['background_image'] ?? '';
$bg_video = $data['background_video'] ?? '';
$cta_text = $data['cta_text'] ?? '';
$cta_link = $data['cta_link'] ?? '';
$overlay = lemur_sanitize_css_color($data['overlay_color'] ?? '', 'rgba(0,0,0,0.4)');
$height = $data['height'] ?? 'large';
$text_align = $data['text_align'] ?? 'center';

$block_id = 'hero-' . lemur_get_block_index();
$classes = [
    'block-hero',
    "block-hero--{$height}",
    "block-hero--{$text_align}",
];
?>

<section id="<?php echo esc_attr($block_id); ?>" class="<?php echo esc_attr(implode(' ', $classes)); ?>">
    <?php if ($bg_video) : ?>
        <video
            class="block-hero__media"
            autoplay muted loop playsinline
            aria-label="<?php echo esc_attr($title ? sprintf(__('Vidéo de fond : %s', 'lemur'), $title) : __('Vidéo de fond décorative', 'lemur')); ?>"
        >
            <source src="<?php echo esc_url($bg_video); ?>" type="video/mp4">
        </video>
    <?php elseif ($bg_image) : ?>
        <div
            class="block-hero__media block-hero__media--image"
            style="background-image: url('<?php echo esc_url($bg_image); ?>')"
            role="img"
            aria-label="<?php echo esc_attr($title); ?>"
        ></div>
    <?php endif; ?>

    <div class="block-hero__overlay" style="background-color: <?php echo esc_attr($overlay); ?>"></div>

    <div class="block-hero__content container">
        <?php if ($title) : ?>
            <h1 class="block-hero__title"><?php echo esc_html($title); ?></h1>
        <?php endif; ?>

        <?php if ($subtitle) : ?>
            <p class="block-hero__subtitle"><?php echo esc_html($subtitle); ?></p>
        <?php endif; ?>

        <?php if ($cta_text && $cta_link) : ?>
            <a href="<?php echo esc_url($cta_link); ?>" class="btn btn--primary btn--lg block-hero__cta">
                <?php echo esc_html($cta_text); ?>
            </a>
        <?php endif; ?>
    </div>
</section>
