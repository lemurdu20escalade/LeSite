<?php
/**
 * Block: Statistics / Chiffres clés
 *
 * Displays key statistics in a grid layout.
 *
 * @package Lemur
 */

declare(strict_types=1);

$data = lemur_get_block_data();
$index = lemur_get_block_index();

$title = $data['title'] ?? __('Le club en chiffres', 'lemur');
$stats = $data['stats'] ?? [];

// Skip block if no stats
if (empty($stats)) {
    return;
}
?>

<section
    class="block-stats"
    aria-labelledby="stats-title-<?php echo esc_attr($index); ?>"
>
    <div class="container">
        <?php if ($title) : ?>
            <h2 id="stats-title-<?php echo esc_attr($index); ?>" class="block-stats__title">
                <?php echo esc_html($title); ?>
            </h2>
        <?php endif; ?>

        <div class="block-stats__grid" role="list">
            <?php foreach ($stats as $stat) : ?>
                <div class="stat-item" role="listitem">
                    <?php if (!empty($stat['icon'])) : ?>
                        <span class="stat-item__icon" aria-hidden="true">
                            <?php echo esc_html($stat['icon']); ?>
                        </span>
                    <?php endif; ?>

                    <span class="stat-item__value">
                        <span
                            class="stat-item__number"
                            data-count="<?php echo esc_attr($stat['number'] ?? '0'); ?>"
                        >
                            <?php echo esc_html($stat['number'] ?? '0'); ?>
                        </span>
                        <?php if (!empty($stat['suffix'])) : ?>
                            <span class="stat-item__suffix">
                                <?php echo esc_html($stat['suffix']); ?>
                            </span>
                        <?php endif; ?>
                    </span>

                    <?php if (!empty($stat['label'])) : ?>
                        <span class="stat-item__label">
                            <?php echo esc_html($stat['label']); ?>
                        </span>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
