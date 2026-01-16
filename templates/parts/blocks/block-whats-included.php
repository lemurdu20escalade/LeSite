<?php
/**
 * Block: What's Included
 *
 * Displays list of membership benefits with icons.
 *
 * @package Lemur
 */

declare(strict_types=1);

$data = lemur_get_block_data();

$title = $data['title'] ?? __('Votre adhésion comprend', 'lemur');
$items = $data['items'] ?? [];
$columns = (int) ($data['columns'] ?? 3);

// Default items if not configured (using existing icons from lemur_ui_icon)
if (empty($items)) {
    $items = [
        [
            'icon'        => 'calendar',
            'title'       => __('Accès aux créneaux', 'lemur'),
            'description' => __('Tous les créneaux d\'escalade en salle, encadrés par des bénévoles formés.', 'lemur'),
        ],
        [
            'icon'        => 'location',
            'title'       => __('Sorties falaise', 'lemur'),
            'description' => __('Participez aux sorties en extérieur. Le covoiturage est organisé, chacun participe aux frais selon ses moyens.', 'lemur'),
        ],
        [
            'icon'        => 'arrow-right',
            'title'       => __('Sorties week-end', 'lemur'),
            'description' => __('Week-ends d\'escalade et activités sportives. Budget estimatif donné, participation libre.', 'lemur'),
        ],
        [
            'icon'        => 'check',
            'title'       => __('Prêt de matériel', 'lemur'),
            'description' => __('Cordes, dégaines et matériel collectif à disposition pour les sorties.', 'lemur'),
        ],
        [
            'icon'        => 'info',
            'title'       => __('Formation', 'lemur'),
            'description' => __('Séances de progression, formations sécurité et autonomie en falaise.', 'lemur'),
        ],
        [
            'icon'        => 'users',
            'title'       => __('Communauté', 'lemur'),
            'description' => __('Rejoignez une communauté conviviale, passionnée et engagée dans les valeurs FSGT.', 'lemur'),
        ],
    ];
}

// Validate columns
$columns = in_array($columns, [2, 3, 4], true) ? $columns : 3;
?>

<section class="block-whats-included">
    <div class="container">
        <?php if ($title) : ?>
            <h2 class="block-whats-included__title"><?php echo esc_html($title); ?></h2>
        <?php endif; ?>

        <div class="included-grid included-grid--<?php echo esc_attr($columns); ?>-cols">
            <?php foreach ($items as $item) : ?>
                <div class="included-item">
                    <?php if (!empty($item['icon'])) : ?>
                        <span class="included-item__icon" aria-hidden="true">
                            <?php lemur_the_ui_icon($item['icon'], ['width' => 32, 'height' => 32]); ?>
                        </span>
                    <?php endif; ?>

                    <div class="included-item__content">
                        <h3 class="included-item__title"><?php echo esc_html($item['title']); ?></h3>
                        <?php if (!empty($item['description'])) : ?>
                            <p class="included-item__description"><?php echo esc_html($item['description']); ?></p>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
