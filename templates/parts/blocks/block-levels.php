<?php
/**
 * Block: Levels / Niveaux de pratique
 *
 * Displays practice levels with colored cards.
 *
 * @package Lemur
 */

declare(strict_types=1);

$data = lemur_get_block_data();
$index = lemur_get_block_index();

$title = $data['title'] ?? __('Niveaux de pratique', 'lemur');
$intro = $data['intro'] ?? '';
$levels = $data['levels'] ?? [];

// Skip block if no levels
if (empty($levels)) {
    return;
}
?>

<section
    class="block-levels"
    aria-labelledby="levels-title-<?php echo esc_attr($index); ?>"
>
    <div class="container">
        <header class="block-levels__header">
            <?php if ($title) : ?>
                <h2 id="levels-title-<?php echo esc_attr($index); ?>" class="block-levels__title">
                    <?php echo esc_html($title); ?>
                </h2>
            <?php endif; ?>

            <?php if ($intro) : ?>
                <p class="block-levels__intro">
                    <?php echo esc_html($intro); ?>
                </p>
            <?php endif; ?>
        </header>

        <div class="levels-grid">
            <?php foreach ($levels as $level) : ?>
                <?php
                $border_style = !empty($level['color'])
                    ? sprintf('border-color: %s;', esc_attr($level['color']))
                    : '';
                ?>
                <div class="level-card" <?php echo $border_style ? sprintf('style="%s"', $border_style) : ''; ?>>
                    <div class="level-card__header">
                        <?php if (!empty($level['icon'])) : ?>
                            <span class="level-card__icon" aria-hidden="true">
                                <?php echo esc_html($level['icon']); ?>
                            </span>
                        <?php endif; ?>

                        <?php if (!empty($level['title'])) : ?>
                            <h3 class="level-card__title">
                                <?php echo esc_html($level['title']); ?>
                            </h3>
                        <?php endif; ?>
                    </div>

                    <?php if (!empty($level['description'])) : ?>
                        <p class="level-card__description">
                            <?php echo esc_html($level['description']); ?>
                        </p>
                    <?php endif; ?>

                    <?php if (!empty($level['requirements'])) : ?>
                        <div class="level-card__requirements">
                            <strong><?php esc_html_e('Prérequis :', 'lemur'); ?></strong>
                            <?php echo esc_html($level['requirements']); ?>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
