<?php
/**
 * Block: Testimonials
 *
 * Customer/member testimonials grid or slider.
 *
 * @package Lemur
 */

declare(strict_types=1);

$data = lemur_get_block_data();

$title = $data['title'] ?? '';
$layout = $data['layout'] ?? 'grid';
$testimonials = $data['testimonials'] ?? [];

$block_id = 'testimonials-' . lemur_get_block_index();

if (empty($testimonials)) {
    return;
}
?>

<section id="<?php echo esc_attr($block_id); ?>" class="block-testimonials block-testimonials--<?php echo esc_attr($layout); ?>">
    <div class="block-testimonials__container container">
        <?php if ($title) : ?>
            <h2 class="block-testimonials__title"><?php echo esc_html($title); ?></h2>
        <?php endif; ?>

        <div class="block-testimonials__grid">
            <?php foreach ($testimonials as $testimonial) : ?>
                <?php
                $quote = $testimonial['quote'] ?? '';
                $author = $testimonial['author'] ?? '';
                $role = $testimonial['role'] ?? '';
                $photo_id = $testimonial['photo'] ?? 0;

                if (empty($quote)) {
                    continue;
                }
                ?>
                <blockquote class="block-testimonials__item">
                    <svg class="block-testimonials__quote-icon" width="40" height="40" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                        <path d="M14.017 21v-7.391c0-5.704 3.731-9.57 8.983-10.609l.995 2.151c-2.432.917-3.995 3.638-3.995 5.849h4v10h-9.983zm-14.017 0v-7.391c0-5.704 3.748-9.57 9-10.609l.996 2.151c-2.433.917-3.996 3.638-3.996 5.849h3.983v10h-9.983z"/>
                    </svg>

                    <p class="block-testimonials__quote"><?php echo esc_html($quote); ?></p>

                    <footer class="block-testimonials__footer">
                        <?php if ($photo_id) : ?>
                            <?php
                            echo wp_get_attachment_image($photo_id, 'thumbnail', false, [
                                'class' => 'block-testimonials__photo',
                                'loading' => 'lazy',
                            ]);
                            ?>
                        <?php endif; ?>

                        <div class="block-testimonials__author-info">
                            <?php if ($author) : ?>
                                <cite class="block-testimonials__author"><?php echo esc_html($author); ?></cite>
                            <?php endif; ?>

                            <?php if ($role) : ?>
                                <span class="block-testimonials__role"><?php echo esc_html($role); ?></span>
                            <?php endif; ?>
                        </div>
                    </footer>
                </blockquote>
            <?php endforeach; ?>
        </div>
    </div>
</section>
