<?php
/**
 * Block: Adhesion Formules
 *
 * Displays membership formulas with tiered pricing (prix conscient).
 *
 * @package Lemur
 */

declare(strict_types=1);

use Lemur\Fields\ThemeOptions;

$data = lemur_get_block_data();

$title = $data['title'] ?? __('Choisissez votre adhésion', 'lemur');
$intro = $data['intro'] ?? '';
$show_banner = !empty($data['show_prix_conscient_banner']);

// Get configuration from theme options
$licence_fsgt = (int) (lemur_get_option(ThemeOptions::FIELD_ADHESION_LICENCE_FSGT) ?: 40);
$adhesion_link = lemur_get_option(ThemeOptions::FIELD_ADHESION_LINK) ?: '';

// Parse tiers from theme options (CSV format)
$adulte_paliers = lemur_parse_paliers(lemur_get_option(ThemeOptions::FIELD_ADHESION_ADULTE_PALIERS) ?: '50,80,110,140,170,200');
$famille_paliers = lemur_parse_paliers(lemur_get_option(ThemeOptions::FIELD_ADHESION_FAMILLE_PALIERS) ?: '80,110,140,170,200,230');
$double_paliers = lemur_parse_paliers(lemur_get_option(ThemeOptions::FIELD_ADHESION_DOUBLE_PALIERS) ?: '10,40,70,100,130,160');

$formules = [
    'adulte' => [
        'name'        => __('Adulte', 'lemur'),
        'description' => __('Adhésion individuelle standard', 'lemur'),
        'paliers'     => $adulte_paliers,
        'highlighted' => true,
        'details'     => [
            sprintf(__('Licence FSGT incluse (%d€)', 'lemur'), $licence_fsgt),
            __('Accès à tous les créneaux', 'lemur'),
            __('Sorties et événements', 'lemur'),
        ],
    ],
    'famille' => [
        'name'        => __('Famille', 'lemur'),
        'description' => __('Pour les familles avec enfants', 'lemur'),
        'paliers'     => $famille_paliers,
        'highlighted' => false,
        'details'     => [
            __('Enfants illimités', 'lemur'),
            __('1 licence adulte incluse', 'lemur'),
            __('Licences familiales FSGT', 'lemur'),
        ],
    ],
    'double' => [
        'name'        => __('Double adhésion', 'lemur'),
        'description' => __('Déjà licencié FSGT dans un autre club', 'lemur'),
        'paliers'     => $double_paliers,
        'highlighted' => false,
        'details'     => [
            __('Licence FSGT déjà payée ailleurs', 'lemur'),
            __('Cotisation club uniquement', 'lemur'),
            __('Mêmes avantages', 'lemur'),
        ],
    ],
];
?>

<section class="block-adhesion-formules">
    <div class="container">
        <header class="block-adhesion-formules__header">
            <?php if ($title) : ?>
                <h2 class="block-adhesion-formules__title"><?php echo esc_html($title); ?></h2>
            <?php endif; ?>

            <?php if ($intro) : ?>
                <p class="block-adhesion-formules__intro"><?php echo esc_html($intro); ?></p>
            <?php endif; ?>
        </header>

        <?php if ($show_banner) : ?>
            <div class="prix-conscient-banner">
                <div class="prix-conscient-banner__icon" aria-hidden="true">
                    <?php lemur_the_ui_icon('info', ['width' => 48, 'height' => 48]); ?>
                </div>
                <div class="prix-conscient-banner__content">
                    <h3><?php esc_html_e('Prix conscient : choisissez votre palier', 'lemur'); ?></h3>
                    <p><?php esc_html_e('Chaque palier permet au club de fonctionner. Les cotisations plus élevées compensent les plus basses, dans un esprit de solidarité.', 'lemur'); ?></p>
                </div>
            </div>
        <?php endif; ?>

        <div class="formules-grid">
            <?php foreach ($formules as $key => $formule) :
                $modifier = $formule['highlighted'] ? 'formule-card--highlighted' : '';
                $paliers = $formule['paliers'];
                $min_palier = !empty($paliers) ? min($paliers) : 0;
                $max_palier = !empty($paliers) ? max($paliers) : 0;
            ?>
                <article class="formule-card <?php echo esc_attr($modifier); ?>">
                    <?php if ($formule['highlighted']) : ?>
                        <div class="formule-card__badge"><?php esc_html_e('Populaire', 'lemur'); ?></div>
                    <?php endif; ?>

                    <header class="formule-card__header">
                        <h3 class="formule-card__name"><?php echo esc_html($formule['name']); ?></h3>
                        <p class="formule-card__description"><?php echo esc_html($formule['description']); ?></p>
                    </header>

                    <div class="formule-card__price-range">
                        <span class="formule-card__price-from"><?php esc_html_e('de', 'lemur'); ?></span>
                        <span class="formule-card__price-min"><?php echo esc_html($min_palier); ?>€</span>
                        <span class="formule-card__price-to"><?php esc_html_e('à', 'lemur'); ?></span>
                        <span class="formule-card__price-max"><?php echo esc_html($max_palier); ?>€</span>
                        <span class="formule-card__price-period"><?php esc_html_e('/an', 'lemur'); ?></span>
                    </div>

                    <div class="formule-card__paliers">
                        <span class="formule-card__paliers-label"><?php esc_html_e('Paliers disponibles :', 'lemur'); ?></span>
                        <div class="paliers-chips">
                            <?php foreach ($paliers as $palier) : ?>
                                <span class="palier-chip"><?php echo esc_html($palier); ?>€</span>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <ul class="formule-card__details">
                        <?php foreach ($formule['details'] as $detail) : ?>
                            <li>
                                <?php lemur_the_ui_icon('check', ['class' => 'check-icon', 'aria-hidden' => 'true']); ?>
                                <?php echo esc_html($detail); ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>

                    <?php if ($adhesion_link) : ?>
                        <a href="<?php echo esc_url($adhesion_link); ?>"
                           class="btn <?php echo $formule['highlighted'] ? 'btn--primary' : 'btn--outline'; ?> btn--full formule-card__cta"
                           target="_blank"
                           rel="noopener">
                            <?php esc_html_e('Choisir cette formule', 'lemur'); ?>
                            <span class="sr-only"><?php esc_html_e('(s\'ouvre dans un nouvel onglet)', 'lemur'); ?></span>
                        </a>
                    <?php endif; ?>
                </article>
            <?php endforeach; ?>
        </div>

        <p class="block-adhesion-formules__note">
            <?php esc_html_e('Vous choisirez votre palier lors de l\'inscription. Aucune justification n\'est demandée.', 'lemur'); ?>
        </p>
    </div>
</section>
