<?php
/**
 * Block: Features
 *
 * Grid of feature cards with icons.
 *
 * @package Lemur
 */

declare(strict_types=1);

$data = lemur_get_block_data();

$title = $data['title'] ?? '';
$subtitle = $data['subtitle'] ?? '';
$columns = $data['columns'] ?? '3';
$items = $data['items'] ?? [];

$block_id = 'features-' . lemur_get_block_index();

if (empty($items)) {
    return;
}
?>

<section id="<?php echo esc_attr($block_id); ?>" class="block-features">
    <div class="block-features__container container">
        <?php if ($title || $subtitle) : ?>
            <header class="block-features__header">
                <?php if ($title) : ?>
                    <h2 class="block-features__title"><?php echo esc_html($title); ?></h2>
                <?php endif; ?>

                <?php if ($subtitle) : ?>
                    <p class="block-features__subtitle"><?php echo esc_html($subtitle); ?></p>
                <?php endif; ?>
            </header>
        <?php endif; ?>

        <div class="block-features__grid block-features__grid--<?php echo esc_attr($columns); ?>">
            <?php foreach ($items as $item) : ?>
                <?php
                $icon = $item['icon'] ?? '';
                $item_title = $item['title'] ?? '';
                $description = $item['description'] ?? '';

                if (empty($item_title)) {
                    continue;
                }
                ?>
                <div class="block-features__item">
                    <?php if ($icon) : ?>
                        <div class="block-features__icon" aria-hidden="true">
                            <?php echo esc_html($icon); ?>
                        </div>
                    <?php endif; ?>

                    <h3 class="block-features__item-title"><?php echo esc_html($item_title); ?></h3>

                    <?php if ($description) : ?>
                        <p class="block-features__description"><?php echo esc_html($description); ?></p>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
