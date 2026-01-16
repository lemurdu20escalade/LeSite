<?php
/**
 * Single: Événement
 *
 * Displays a single event with all details.
 *
 * @package Lemur
 */

declare(strict_types=1);

use Lemur\CustomPostTypes\Events;

get_header();

$event_id = get_the_ID();
$meta = Events::getEventMeta($event_id);

// Formatted data
$formatted_date = lemur_format_event_date($event_id);
$formatted_time = lemur_format_event_time($event_id);
$difficulty_label = lemur_get_event_difficulty_label($event_id);

// Status
$is_past = lemur_event_is_past($event_id);
$registrations_open = lemur_event_registrations_open($event_id);
$remaining_spots = lemur_event_remaining_spots($event_id);

// Event type
$event_types = get_the_terms($event_id, Events::TAXONOMY);
$type_name = ($event_types && !is_wp_error($event_types))
    ? $event_types[0]->name
    : __('Événement', 'lemur');
?>

<main id="main-content" class="site-main">
    <article class="event-single<?php echo $is_past ? ' event-single--past' : ''; ?>">
        <?php if (has_post_thumbnail()) : ?>
            <div class="event-single__hero">
                <?php the_post_thumbnail('lemur-hero', ['class' => 'event-single__hero-image']); ?>
                <div class="event-single__hero-overlay"></div>
            </div>
        <?php endif; ?>

        <div class="container">
            <div class="event-single__layout">
                <div class="event-single__main">
                    <div class="event-single__badges">
                        <span class="event-type-badge">
                            <?php echo esc_html($type_name); ?>
                        </span>

                        <?php if ($is_past) : ?>
                            <span class="event-status-badge event-status-badge--past">
                                <?php esc_html_e('Terminé', 'lemur'); ?>
                            </span>
                        <?php endif; ?>
                    </div>

                    <h1 class="event-single__title"><?php the_title(); ?></h1>

                    <?php if ($formatted_date || !empty($meta['location'])) : ?>
                        <div class="event-single__quick-info">
                            <?php if ($formatted_date) : ?>
                                <div class="event-single__info-item">
                                    <?php lemur_the_ui_icon('calendar'); ?>
                                    <span>
                                        <?php echo esc_html($formatted_date); ?>
                                        <?php if ($formatted_time) : ?>
                                            <span class="event-single__time"><?php echo esc_html($formatted_time); ?></span>
                                        <?php endif; ?>
                                    </span>
                                </div>
                            <?php endif; ?>

                            <?php if (!empty($meta['location'])) : ?>
                                <div class="event-single__info-item">
                                    <?php lemur_the_ui_icon('location'); ?>
                                    <span><?php echo esc_html($meta['location']); ?></span>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>

                    <div class="event-single__content">
                        <?php the_content(); ?>
                    </div>

                    <?php if (!empty($meta['equipment'])) : ?>
                        <div class="event-single__section">
                            <h2><?php esc_html_e('Matériel à prévoir', 'lemur'); ?></h2>
                            <?php echo wp_kses_post(wpautop($meta['equipment'])); ?>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($meta['organizer']) && is_array($meta['organizer'])) : ?>
                        <div class="event-single__section">
                            <h2><?php esc_html_e('Organisé par', 'lemur'); ?></h2>
                            <div class="organizers-list">
                                <?php foreach ($meta['organizer'] as $org) : ?>
                                    <?php
                                    $org_id = $org['id'] ?? $org;
                                    if (!$org_id) {
                                        continue;
                                    }
                                    $org_name = get_the_title($org_id);
                                    $org_photo = get_post_thumbnail_id($org_id);
                                    ?>
                                    <div class="organizer-item">
                                        <?php if ($org_photo) : ?>
                                            <?php lemur_responsive_image($org_photo, 'thumbnail', ['class' => 'organizer-item__photo']); ?>
                                        <?php else : ?>
                                            <div class="organizer-item__photo organizer-item__photo--placeholder" aria-hidden="true">
                                                <?php echo esc_html(mb_substr($org_name, 0, 1)); ?>
                                            </div>
                                        <?php endif; ?>
                                        <span class="organizer-item__name"><?php echo esc_html($org_name); ?></span>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>

                <aside class="event-single__sidebar">
                    <div class="event-sidebar-card">
                        <h2 class="event-sidebar-card__title">
                            <?php esc_html_e('Informations', 'lemur'); ?>
                        </h2>

                        <dl class="event-sidebar-card__details">
                            <?php if ($difficulty_label) : ?>
                                <dt><?php esc_html_e('Niveau', 'lemur'); ?></dt>
                                <dd>
                                    <span class="difficulty-badge difficulty-badge--<?php echo esc_attr($meta['difficulty'] ?: 'all'); ?>">
                                        <?php echo esc_html($difficulty_label); ?>
                                    </span>
                                </dd>
                            <?php endif; ?>

                            <?php if (!empty($meta['price'])) : ?>
                                <dt><?php esc_html_e('Tarif', 'lemur'); ?></dt>
                                <dd><?php echo esc_html($meta['price']); ?></dd>
                            <?php endif; ?>

                            <?php if (!empty($meta['max_participants'])) : ?>
                                <dt><?php esc_html_e('Places', 'lemur'); ?></dt>
                                <dd>
                                    <?php
                                    $current = (int) ($meta['current_participants'] ?: 0);
                                    $max = (int) $meta['max_participants'];
                                    printf('%d / %d', $current, $max);
                                    ?>
                                    <?php if ($remaining_spots !== null) : ?>
                                        <?php if ($remaining_spots > 0) : ?>
                                            <small>
                                                (<?php
                                                printf(
                                                    esc_html(_n('%d place restante', '%d places restantes', $remaining_spots, 'lemur')),
                                                    $remaining_spots
                                                );
                                                ?>)
                                            </small>
                                        <?php else : ?>
                                            <small class="text-error">
                                                (<?php esc_html_e('Complet', 'lemur'); ?>)
                                            </small>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </dd>
                            <?php endif; ?>

                            <?php if (!empty($meta['registration_deadline'])) : ?>
                                <dt><?php esc_html_e('Date limite', 'lemur'); ?></dt>
                                <dd>
                                    <?php echo esc_html(date_i18n('j F Y', strtotime($meta['registration_deadline']))); ?>
                                </dd>
                            <?php endif; ?>

                            <?php if (!empty($meta['address'])) : ?>
                                <dt><?php esc_html_e('Adresse', 'lemur'); ?></dt>
                                <dd>
                                    <?php echo esc_html($meta['address']); ?>
                                    <?php if (!empty($meta['map_link'])) : ?>
                                        <a
                                            href="<?php echo esc_url($meta['map_link']); ?>"
                                            class="map-link"
                                            target="_blank"
                                            rel="noopener"
                                        >
                                            <?php esc_html_e('Voir sur la carte', 'lemur'); ?>
                                            <span class="sr-only"><?php esc_html_e('(nouvel onglet)', 'lemur'); ?></span>
                                        </a>
                                    <?php endif; ?>
                                </dd>
                            <?php endif; ?>
                        </dl>

                        <?php if (!empty($meta['registration_link']) && !$is_past) : ?>
                            <div class="event-sidebar-card__action">
                                <?php if ($registrations_open) : ?>
                                    <a
                                        href="<?php echo esc_url($meta['registration_link']); ?>"
                                        class="btn btn--primary btn--lg btn--full"
                                        target="_blank"
                                        rel="noopener"
                                    >
                                        <?php esc_html_e('S\'inscrire', 'lemur'); ?>
                                        <span class="sr-only"><?php esc_html_e('(nouvel onglet)', 'lemur'); ?></span>
                                    </a>
                                <?php elseif ($remaining_spots === 0) : ?>
                                    <button class="btn btn--disabled btn--lg btn--full" disabled>
                                        <?php esc_html_e('Complet', 'lemur'); ?>
                                    </button>
                                <?php else : ?>
                                    <button class="btn btn--disabled btn--lg btn--full" disabled>
                                        <?php esc_html_e('Inscriptions fermées', 'lemur'); ?>
                                    </button>
                                <?php endif; ?>
                            </div>
                        <?php elseif ($is_past) : ?>
                            <div class="event-sidebar-card__past">
                                <p><?php esc_html_e('Cet événement est terminé.', 'lemur'); ?></p>
                            </div>
                        <?php endif; ?>
                    </div>
                </aside>
            </div>

            <?php
            // Related events
            $related_events = lemur_get_related_events($event_id, 3);

            if (!empty($related_events)) :
            ?>
                <section class="event-single__related">
                    <h2><?php esc_html_e('Autres événements', 'lemur'); ?></h2>
                    <div class="events-grid events-grid--related">
                        <?php
                        global $post;
                        foreach ($related_events as $event) :
                            $post = $event;
                            setup_postdata($post);
                            get_template_part('templates/parts/cards/event-card');
                        endforeach;
                        wp_reset_postdata();
                        ?>
                    </div>
                </section>
            <?php endif; ?>

            <nav class="event-single__nav" aria-label="<?php esc_attr_e('Navigation événements', 'lemur'); ?>">
                <a href="<?php echo esc_url(get_post_type_archive_link(Events::POST_TYPE)); ?>" class="btn btn--outline">
                    <?php lemur_the_ui_icon('chevron-right', ['class' => 'btn__icon btn__icon--left btn__icon--flip']); ?>
                    <?php esc_html_e('Tous les événements', 'lemur'); ?>
                </a>
            </nav>
        </div>
    </article>
</main>

<?php
get_footer();
