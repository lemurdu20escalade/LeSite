<?php
/**
 * Block: Locations / Lieux de pratique
 *
 * Displays climbing locations with cards.
 *
 * @package Lemur
 */

declare(strict_types=1);

$data = lemur_get_block_data();
$index = lemur_get_block_index();

$title = $data['title'] ?? __('Où grimper ?', 'lemur');
$locations = $data['locations'] ?? [];

// Location type labels
$type_labels = [
    'indoor'  => __('Indoor', 'lemur'),
    'outdoor' => __('Outdoor', 'lemur'),
    'bloc'    => __('Bloc', 'lemur'),
];

// Skip block if no locations
if (empty($locations)) {
    return;
}
?>

<section
    class="block-locations"
    aria-labelledby="locations-title-<?php echo esc_attr($index); ?>"
>
    <div class="container">
        <?php if ($title) : ?>
            <h2 id="locations-title-<?php echo esc_attr($index); ?>" class="block-locations__title">
                <?php echo esc_html($title); ?>
            </h2>
        <?php endif; ?>

        <div class="locations-grid">
            <?php foreach ($locations as $location) : ?>
                <article class="location-card">
                    <?php if (!empty($location['image'])) : ?>
                        <div class="location-card__image">
                            <?php echo wp_get_attachment_image(
                                (int) $location['image'],
                                'lemur-card',
                                false,
                                ['loading' => 'lazy']
                            ); ?>
                        </div>
                    <?php endif; ?>

                    <div class="location-card__content">
                        <span class="location-card__type">
                            <?php
                            $type_key = $location['type'] ?? 'indoor';
                            echo esc_html($type_labels[$type_key] ?? $type_labels['indoor']);
                            ?>
                        </span>

                        <?php if (!empty($location['name'])) : ?>
                            <h3 class="location-card__title">
                                <?php echo esc_html($location['name']); ?>
                            </h3>
                        <?php endif; ?>

                        <?php if (!empty($location['address'])) : ?>
                            <p class="location-card__address">
                                <?php lemur_the_ui_icon('location', ['class' => 'location-card__address-icon']); ?>
                                <?php echo esc_html($location['address']); ?>
                            </p>
                        <?php endif; ?>

                        <?php if (!empty($location['description'])) : ?>
                            <p class="location-card__description">
                                <?php echo esc_html($location['description']); ?>
                            </p>
                        <?php endif; ?>

                        <?php if (!empty($location['map_link'])) : ?>
                            <a
                                href="<?php echo esc_url($location['map_link']); ?>"
                                class="location-card__link"
                                target="_blank"
                                rel="noopener"
                            >
                                <?php esc_html_e('Voir sur la carte', 'lemur'); ?>
                                <?php lemur_the_ui_icon('arrow-right', ['class' => 'location-card__link-icon']); ?>
                                <span class="sr-only">
                                    <?php esc_html_e('(ouvre dans un nouvel onglet)', 'lemur'); ?>
                                </span>
                            </a>
                        <?php endif; ?>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    </div>
</section>
