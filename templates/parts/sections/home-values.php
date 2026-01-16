<?php
/**
 * Section: Values / Association presentation
 *
 * Displays the association values and FSGT affiliation.
 *
 * @package Lemur
 */

declare(strict_types=1);

$title = carbon_get_theme_option('home_values_title') ?: __('Nos valeurs', 'lemur');
$intro = carbon_get_theme_option('home_values_intro');
$values = carbon_get_theme_option('home_values_list') ?: [];

// Skip section if no content
if (empty($values) && empty($intro)) {
    return;
}
?>

<section class="section section--values" aria-labelledby="values-title">
    <div class="container">
        <header class="section__header section__header--centered">
            <h2 id="values-title" class="section__title"><?php echo esc_html($title); ?></h2>
            <?php if ($intro) : ?>
                <p class="section__intro"><?php echo esc_html($intro); ?></p>
            <?php endif; ?>
        </header>

        <?php if (!empty($values)) : ?>
            <div class="values-grid">
                <?php foreach ($values as $value) : ?>
                    <div class="value-card">
                        <?php if (!empty($value['icon'])) : ?>
                            <div class="value-card__icon" aria-hidden="true">
                                <?php echo esc_html($value['icon']); ?>
                            </div>
                        <?php endif; ?>
                        <h3 class="value-card__title">
                            <?php echo esc_html($value['title'] ?? ''); ?>
                        </h3>
                        <?php if (!empty($value['description'])) : ?>
                            <p class="value-card__description">
                                <?php echo esc_html($value['description']); ?>
                            </p>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <div class="values-fsgt">
            <p class="values-fsgt__text">
                <?php esc_html_e('Association affiliée à la', 'lemur'); ?>
                <a href="https://www.fsgt.org" target="_blank" rel="noopener" class="values-fsgt__link">
                    <strong>FSGT</strong>
                    <span class="sr-only"><?php esc_html_e('(ouvre dans un nouvel onglet)', 'lemur'); ?></span>
                </a>
            </p>
        </div>
    </div>
</section>
