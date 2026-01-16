<?php
/**
 * Block: Process
 *
 * Displays step-by-step process with numbered steps.
 *
 * @package Lemur
 */

declare(strict_types=1);

$data = lemur_get_block_data();

$title = $data['title'] ?? __('Comment adhérer ?', 'lemur');
$steps = $data['steps'] ?? [];

// Default steps if not configured
if (empty($steps)) {
    $steps = [
        [
            'title'       => __('Remplir le formulaire', 'lemur'),
            'description' => __('Remplissez le formulaire d\'inscription. Choisissez votre formule et votre palier de cotisation.', 'lemur'),
        ],
        [
            'title'       => __('Fournir un certificat médical', 'lemur'),
            'description' => __('Un certificat de non contre-indication à la pratique de l\'escalade, daté de moins d\'un an.', 'lemur'),
        ],
        [
            'title'       => __('Venir à une séance', 'lemur'),
            'description' => __('Présentez-vous à l\'un de nos créneaux. Les bénévoles vous accueilleront.', 'lemur'),
        ],
        [
            'title'       => __('Grimper !', 'lemur'),
            'description' => __('Bienvenue chez Lemur ! Profitez des créneaux, sorties et de la communauté.', 'lemur'),
        ],
    ];
}
?>

<section class="block-process">
    <div class="container">
        <?php if ($title) : ?>
            <h2 class="block-process__title"><?php echo esc_html($title); ?></h2>
        <?php endif; ?>

        <ol class="process-steps">
            <?php foreach ($steps as $index => $step) : ?>
                <li class="process-step">
                    <div class="process-step__number" aria-hidden="true"><?php echo esc_html($index + 1); ?></div>
                    <div class="process-step__content">
                        <h3 class="process-step__title"><?php echo esc_html($step['title']); ?></h3>
                        <?php if (!empty($step['description'])) : ?>
                            <p class="process-step__description"><?php echo esc_html($step['description']); ?></p>
                        <?php endif; ?>
                    </div>
                </li>
            <?php endforeach; ?>
        </ol>
    </div>
</section>
