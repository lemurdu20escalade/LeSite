<?php
/**
 * Block: Values / Valeurs
 *
 * Displays the association values in a card grid.
 *
 * @package Lemur
 */

declare(strict_types=1);

$data = lemur_get_block_data();
$index = lemur_get_block_index();

$title = $data['title'] ?? __('Nos valeurs', 'lemur');
$intro = $data['intro'] ?? '';
$values = $data['values'] ?? [];

// Skip block if no values
if (empty($values)) {
    return;
}
?>

<section
    class="block-values"
    aria-labelledby="values-title-<?php echo esc_attr($index); ?>"
>
    <div class="container">
        <header class="block-values__header">
            <?php if ($title) : ?>
                <h2 id="values-title-<?php echo esc_attr($index); ?>" class="block-values__title">
                    <?php echo esc_html($title); ?>
                </h2>
            <?php endif; ?>

            <?php if ($intro) : ?>
                <p class="block-values__intro">
                    <?php echo esc_html($intro); ?>
                </p>
            <?php endif; ?>
        </header>

        <div class="block-values__grid">
            <?php foreach ($values as $value) : ?>
                <article class="value-card">
                    <?php if (!empty($value['icon'])) : ?>
                        <div class="value-card__icon" aria-hidden="true">
                            <?php echo esc_html($value['icon']); ?>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($value['title'])) : ?>
                        <h3 class="value-card__title">
                            <?php echo esc_html($value['title']); ?>
                        </h3>
                    <?php endif; ?>

                    <?php if (!empty($value['description'])) : ?>
                        <p class="value-card__description">
                            <?php echo esc_html($value['description']); ?>
                        </p>
                    <?php endif; ?>
                </article>
            <?php endforeach; ?>
        </div>
    </div>
</section>
