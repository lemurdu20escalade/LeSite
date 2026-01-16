<?php
/**
 * Block: Activities / Activités
 *
 * Displays climbing activities with alternating layout.
 *
 * @package Lemur
 */

declare(strict_types=1);

$data = lemur_get_block_data();
$index = lemur_get_block_index();

$title = $data['title'] ?? __('Nos activités', 'lemur');
$activities = $data['activities'] ?? [];

// Skip block if no activities
if (empty($activities)) {
    return;
}
?>

<section
    class="block-activities"
    aria-labelledby="activities-title-<?php echo esc_attr($index); ?>"
>
    <div class="container">
        <?php if ($title) : ?>
            <h2 id="activities-title-<?php echo esc_attr($index); ?>" class="block-activities__title">
                <?php echo esc_html($title); ?>
            </h2>
        <?php endif; ?>

        <div class="activities-list">
            <?php foreach ($activities as $activity_index => $activity) : ?>
                <?php
                $is_reversed = ($activity_index % 2 !== 0);
                $modifier = $is_reversed ? 'activity-item--reversed' : '';
                ?>
                <article class="activity-item <?php echo esc_attr($modifier); ?>">
                    <?php if (!empty($activity['image'])) : ?>
                        <div class="activity-item__image">
                            <?php echo wp_get_attachment_image(
                                (int) $activity['image'],
                                'large',
                                false,
                                ['loading' => 'lazy']
                            ); ?>
                        </div>
                    <?php endif; ?>

                    <div class="activity-item__content">
                        <?php if (!empty($activity['icon'])) : ?>
                            <span class="activity-item__icon" aria-hidden="true">
                                <?php echo esc_html($activity['icon']); ?>
                            </span>
                        <?php endif; ?>

                        <?php if (!empty($activity['title'])) : ?>
                            <h3 class="activity-item__title">
                                <?php echo esc_html($activity['title']); ?>
                            </h3>
                        <?php endif; ?>

                        <?php if (!empty($activity['description'])) : ?>
                            <div class="activity-item__description">
                                <?php echo wp_kses_post($activity['description']); ?>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($activity['features'])) : ?>
                            <ul class="activity-item__features">
                                <?php
                                $features = array_filter(
                                    array_map('trim', explode("\n", $activity['features']))
                                );
                                foreach ($features as $feature) :
                                    ?>
                                    <li><?php echo esc_html($feature); ?></li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>

                        <?php if (!empty($activity['level'])) : ?>
                            <span class="activity-item__level">
                                <?php echo esc_html($activity['level']); ?>
                            </span>
                        <?php endif; ?>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    </div>
</section>
