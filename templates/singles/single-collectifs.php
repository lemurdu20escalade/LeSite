<?php
/**
 * Single collectif
 *
 * @package Lemur
 */

declare(strict_types=1);

use Lemur\CustomPostTypes\Collectives;

get_header();

$collective_id = get_the_ID();
$description = carbon_get_post_meta($collective_id, Collectives::FIELD_DESCRIPTION);
$objectives_raw = carbon_get_post_meta($collective_id, Collectives::FIELD_OBJECTIVES);
$email = carbon_get_post_meta($collective_id, Collectives::FIELD_EMAIL);
$mailing_list = carbon_get_post_meta($collective_id, Collectives::FIELD_MAILING_LIST);
$meeting_frequency = carbon_get_post_meta($collective_id, Collectives::FIELD_MEETING_FREQUENCY);
$color = carbon_get_post_meta($collective_id, Collectives::FIELD_COLOR);

$objectives = [];
if ($objectives_raw) {
    $objectives = array_filter(array_map('trim', explode("\n", $objectives_raw)));
}

$categories = get_the_terms($collective_id, Collectives::TAXONOMY);
?>

<main id="main-content" class="site-main site-main--flush" style="--collective-color: <?php echo esc_attr($color ?: '#64748b'); ?>">
    <article class="collective-single">
        <header class="collective-single__hero">
            <!-- Decorative accent element -->
            <div class="collective-single__hero-accent" aria-hidden="true"></div>

            <div class="container">
                <div class="collective-single__hero-content">
                    <?php if ($categories && !is_wp_error($categories)) : ?>
                        <span class="collective-single__category">
                            <span class="collective-single__category-dot" aria-hidden="true"></span>
                            <?php echo esc_html($categories[0]->name); ?>
                        </span>
                    <?php endif; ?>

                    <h1 class="collective-single__title"><?php the_title(); ?></h1>

                    <?php if ($description) : ?>
                        <p class="collective-single__excerpt">
                            <?php echo esc_html(wp_trim_words(wp_strip_all_tags($description), 30, '...')); ?>
                        </p>
                    <?php endif; ?>

                    <!-- Visual color indicator bar -->
                    <div class="collective-single__color-bar" aria-hidden="true">
                        <span class="collective-single__color-bar-fill"></span>
                    </div>
                </div>
            </div>
        </header>

        <div class="container">
            <div class="collective-single__layout">
                <div class="collective-single__main">
                    <?php if ($description) : ?>
                        <section class="collective-single__section">
                            <h2><?php esc_html_e('Présentation', 'lemur'); ?></h2>
                            <div class="collective-single__description prose">
                                <?php echo wp_kses_post($description); ?>
                            </div>
                        </section>
                    <?php endif; ?>

                    <?php if (!empty($objectives)) : ?>
                        <section class="collective-single__section">
                            <h2><?php esc_html_e('Objectifs', 'lemur'); ?></h2>
                            <ul class="collective-single__objectives">
                                <?php foreach ($objectives as $objective) : ?>
                                    <li><?php echo esc_html($objective); ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </section>
                    <?php endif; ?>
                </div>

                <aside class="collective-single__sidebar">
                    <div class="collective-info-card">
                        <div class="collective-info-card__header">
                            <span class="collective-info-card__accent" aria-hidden="true"></span>
                            <h2 class="collective-info-card__title"><?php esc_html_e('Informations', 'lemur'); ?></h2>
                        </div>

                        <div class="collective-info-card__body">
                            <?php if ($meeting_frequency) : ?>
                                <div class="collective-info-card__item">
                                    <span class="collective-info-card__icon" aria-hidden="true">
                                        <?php echo lemur_ui_icon('calendar', ['width' => 20, 'height' => 20]); ?>
                                    </span>
                                    <div class="collective-info-card__content">
                                        <dt class="collective-info-card__label"><?php esc_html_e('Reunions', 'lemur'); ?></dt>
                                        <dd class="collective-info-card__value"><?php echo esc_html($meeting_frequency); ?></dd>
                                    </div>
                                </div>
                            <?php endif; ?>

                            <?php if ($mailing_list) : ?>
                                <div class="collective-info-card__item">
                                    <span class="collective-info-card__icon" aria-hidden="true">
                                        <?php echo lemur_ui_icon('mail', ['width' => 20, 'height' => 20]); ?>
                                    </span>
                                    <div class="collective-info-card__content">
                                        <dt class="collective-info-card__label"><?php esc_html_e('Liste de diffusion', 'lemur'); ?></dt>
                                        <dd class="collective-info-card__value"><?php echo esc_html($mailing_list); ?></dd>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>

                        <?php if ($email) : ?>
                            <div class="collective-info-card__cta">
                                <?php lemur_the_email($email, __('Contacter le collectif', 'lemur'), ['button_style' => 'primary', 'class' => 'collective-info-card__button']); ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </aside>
            </div>

            <nav class="collective-single__nav">
                <a href="<?php echo esc_url(home_url('/les-collectifs/')); ?>" class="collective-single__back">
                    &larr; <?php esc_html_e('Tous les collectifs', 'lemur'); ?>
                </a>
            </nav>
        </div>
    </article>
</main>

<?php
get_footer();
