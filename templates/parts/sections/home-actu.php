<?php
/**
 * Section: Actu / Upcoming Events
 *
 * Conditional section - only displays if there are upcoming events.
 *
 * @package Lemur
 */

declare(strict_types=1);

// Get upcoming events
$events = lemur_get_upcoming_events(4);

// Conditional section: only display if there's content
if (empty($events)) {
    return;
}

$events_archive_link = get_post_type_archive_link(\Lemur\CustomPostTypes\Events::POST_TYPE);
?>

<section class="section section--actu section--alt" aria-labelledby="actu-title">
    <div class="container">
        <header class="section__header">
            <h2 id="actu-title" class="section__title"><?php esc_html_e('Actualités', 'lemur'); ?></h2>
            <?php if ($events_archive_link) : ?>
                <a href="<?php echo esc_url($events_archive_link); ?>" class="section__more">
                    <?php esc_html_e('Toutes les actus', 'lemur'); ?>
                    <?php lemur_the_ui_icon('arrow-right'); ?>
                </a>
            <?php endif; ?>
        </header>

        <div class="events-grid">
            <?php
            global $post;
            foreach ($events as $event) :
                $post = $event;
                setup_postdata($post);
                set_query_var('event_id', $event->ID);
                get_template_part('templates/parts/cards/event-card');
            endforeach;
            wp_reset_postdata();
            ?>
        </div>
    </div>
</section>
