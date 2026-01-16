<?php
/**
 * Card: Event
 *
 * Displays an event card with date, title, location and registration info.
 *
 * @package Lemur
 */

declare(strict_types=1);

use Lemur\CustomPostTypes\Events;

$event_id = get_query_var('event_id', get_the_ID());

if (!$event_id) {
    return;
}

$meta = Events::getEventMeta($event_id);
$permalink = get_permalink($event_id);
$title = get_the_title($event_id);
$thumbnail_id = get_post_thumbnail_id($event_id);
$event_types = get_the_terms($event_id, Events::TAXONOMY);

// Format date
$formatted_date = lemur_format_event_date($event_id);
$formatted_time = lemur_format_event_time($event_id);

// Registration status
$registrations_open = lemur_event_registrations_open($event_id);
$remaining_spots = lemur_event_remaining_spots($event_id);
$is_past = lemur_event_is_past($event_id);
?>

<article class="event-card<?php echo $is_past ? ' event-card--past' : ''; ?>">
    <?php if ($thumbnail_id) : ?>
        <div class="event-card__image">
            <a href="<?php echo esc_url($permalink); ?>" tabindex="-1" aria-hidden="true">
                <?php lemur_responsive_image($thumbnail_id, 'lemur-card', ['class' => 'event-card__thumbnail']); ?>
            </a>
        </div>
    <?php endif; ?>

    <div class="event-card__content">
        <?php if ($formatted_date) : ?>
            <div class="event-card__date">
                <?php lemur_the_ui_icon('calendar', ['width' => 16, 'height' => 16]); ?>
                <time datetime="<?php echo esc_attr($meta['date_start']); ?>">
                    <?php echo esc_html($formatted_date); ?>
                </time>
                <?php if ($formatted_time) : ?>
                    <span class="event-card__time">
                        <?php echo esc_html($formatted_time); ?>
                    </span>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <?php if ($event_types && !is_wp_error($event_types)) : ?>
            <div class="event-card__types">
                <?php foreach ($event_types as $type) : ?>
                    <span class="event-card__type"><?php echo esc_html($type->name); ?></span>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <h3 class="event-card__title">
            <a href="<?php echo esc_url($permalink); ?>">
                <?php echo esc_html($title); ?>
            </a>
        </h3>

        <?php if (!empty($meta['location'])) : ?>
            <p class="event-card__location">
                <?php lemur_the_ui_icon('location', ['width' => 14, 'height' => 14]); ?>
                <?php echo esc_html($meta['location']); ?>
            </p>
        <?php endif; ?>

        <footer class="event-card__footer">
            <?php if ($is_past) : ?>
                <span class="event-card__status event-card__status--past">
                    <?php esc_html_e('Terminé', 'lemur'); ?>
                </span>
            <?php elseif ($registrations_open) : ?>
                <span class="event-card__status event-card__status--open">
                    <?php esc_html_e('Inscriptions ouvertes', 'lemur'); ?>
                </span>
                <?php if ($remaining_spots !== null && $remaining_spots <= 5 && $remaining_spots > 0) : ?>
                    <span class="event-card__spots event-card__spots--limited">
                        <?php
                        printf(
                            esc_html(_n('%d place restante', '%d places restantes', $remaining_spots, 'lemur')),
                            $remaining_spots
                        );
                        ?>
                    </span>
                <?php elseif ($remaining_spots === 0) : ?>
                    <span class="event-card__spots event-card__spots--full">
                        <?php esc_html_e('Complet', 'lemur'); ?>
                    </span>
                <?php endif; ?>
            <?php elseif ($remaining_spots === 0) : ?>
                <span class="event-card__status event-card__status--full">
                    <?php esc_html_e('Complet', 'lemur'); ?>
                </span>
            <?php endif; ?>
        </footer>
    </div>
</article>
