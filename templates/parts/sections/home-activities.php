<?php
/**
 * Section: Activities
 *
 * Displays the list of club activities.
 *
 * @package Lemur
 */

declare(strict_types=1);

$title = carbon_get_theme_option('home_activities_title') ?: __('Nos activités', 'lemur');
$intro = carbon_get_theme_option('home_activities_intro');
$activities = carbon_get_theme_option('home_activities_list') ?: [];

// Default activities if not configured
if (empty($activities)) {
    $activities = [
        ['icon' => '🧗', 'title' => __('Sorties grimpe', 'lemur'), 'description' => __('Falaises et blocs en extérieur', 'lemur')],
        ['icon' => '❄️', 'title' => __('Sorties neige', 'lemur'), 'description' => __('Cascade de glace, ski de rando', 'lemur')],
        ['icon' => '📈', 'title' => __('Séances progression', 'lemur'), 'description' => __('Améliorer sa technique', 'lemur')],
        ['icon' => '👨‍👩‍👧', 'title' => __('Séances famille', 'lemur'), 'description' => __('Grimper en famille', 'lemur')],
        ['icon' => '🤝', 'title' => __('Interclub', 'lemur'), 'description' => __('Rencontres avec d\'autres clubs', 'lemur')],
    ];
}
?>

<section class="section section--activities" aria-labelledby="activities-title">
    <div class="container">
        <header class="section__header section__header--centered">
            <h2 id="activities-title" class="section__title"><?php echo esc_html($title); ?></h2>
            <?php if ($intro) : ?>
                <p class="section__intro"><?php echo esc_html($intro); ?></p>
            <?php endif; ?>
        </header>

        <div class="activities-grid">
            <?php foreach ($activities as $activity) : ?>
                <article class="activity-card">
                    <?php if (!empty($activity['icon'])) : ?>
                        <div class="activity-card__icon" aria-hidden="true">
                            <?php echo esc_html($activity['icon']); ?>
                        </div>
                    <?php endif; ?>
                    <h3 class="activity-card__title">
                        <?php echo esc_html($activity['title'] ?? ''); ?>
                    </h3>
                    <?php if (!empty($activity['description'])) : ?>
                        <p class="activity-card__description">
                            <?php echo esc_html($activity['description']); ?>
                        </p>
                    <?php endif; ?>
                </article>
            <?php endforeach; ?>
        </div>
    </div>
</section>
