<?php
/**
 * Block: Transparence
 *
 * Displays breakdown of how membership fees are used.
 *
 * @package Lemur
 */

declare(strict_types=1);

$data = lemur_get_block_data();

$title = $data['title'] ?? __('Où va votre cotisation ?', 'lemur');
$items = $data['items'] ?? [];

// Default items if not configured
if (empty($items)) {
    $items = [
        [
            'label'       => __('Licence & assurance FSGT', 'lemur'),
            'percentage'  => 30,
            'color'       => 'primary',
            'description' => __('Obligatoire pour pratiquer', 'lemur'),
        ],
        [
            'label'       => __('Location des salles', 'lemur'),
            'percentage'  => 35,
            'color'       => 'secondary',
            'description' => __('Créneaux en gymnase', 'lemur'),
        ],
        [
            'label'       => __('Matériel collectif', 'lemur'),
            'percentage'  => 20,
            'color'       => 'success',
            'description' => __('Cordes, dégaines, etc.', 'lemur'),
        ],
        [
            'label'       => __('Sorties & événements', 'lemur'),
            'percentage'  => 10,
            'color'       => 'warning',
            'description' => __('Organisation, transport', 'lemur'),
        ],
        [
            'label'       => __('Fonctionnement', 'lemur'),
            'percentage'  => 5,
            'color'       => 'neutral',
            'description' => __('Frais divers', 'lemur'),
        ],
    ];
}
?>

<section class="block-transparence">
    <div class="container">
        <?php if ($title) : ?>
            <h2 class="block-transparence__title"><?php echo esc_html($title); ?></h2>
        <?php endif; ?>

        <div class="transparence-chart">
            <div class="transparence-bars">
                <?php foreach ($items as $item) :
                    $percentage = (int) ($item['percentage'] ?? 0);
                    $color = $item['color'] ?? 'primary';
                ?>
                    <div class="transparence-bar">
                        <div class="transparence-bar__header">
                            <span class="transparence-bar__name"><?php echo esc_html($item['label']); ?></span>
                            <span class="transparence-bar__percentage"><?php echo esc_html($percentage); ?>%</span>
                        </div>
                        <div class="transparence-bar__track">
                            <div class="transparence-bar__fill transparence-bar__fill--<?php echo esc_attr($color); ?>"
                                 style="width: <?php echo esc_attr($percentage); ?>%"
                                 role="progressbar"
                                 aria-valuenow="<?php echo esc_attr($percentage); ?>"
                                 aria-valuemin="0"
                                 aria-valuemax="100"
                                 aria-label="<?php echo esc_attr($item['label']); ?>"></div>
                        </div>
                        <?php if (!empty($item['description'])) : ?>
                            <span class="transparence-bar__description"><?php echo esc_html($item['description']); ?></span>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <p class="block-transparence__note">
            <?php esc_html_e('Ces chiffres sont des moyennes. Les comptes détaillés sont présentés lors de l\'Assemblée Générale annuelle.', 'lemur'); ?>
        </p>
    </div>
</section>
