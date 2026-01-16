<?php
/**
 * Block: Events List
 *
 * Dynamic list of events from CPT.
 *
 * @package Lemur
 */

declare(strict_types=1);

$data = lemur_get_block_data();

$title = $data['title'] ?? '';
$count = (int) ($data['count'] ?? 4);
$show_past = !empty($data['show_past']);
$category_filter = $data['category_filter'] ?? [];

$block_id = 'events-' . lemur_get_block_index();

// Build query args
$args = [
    'post_type' => 'evenement',
    'posts_per_page' => $count,
    'post_status' => 'publish',
    'orderby' => 'meta_value',
    'meta_key' => 'evenement_date',
    'order' => $show_past ? 'DESC' : 'ASC',
];

// Filter by date (future events only)
if (!$show_past) {
    $args['meta_query'] = [
        [
            'key' => 'evenement_date',
            'value' => gmdate('Y-m-d'),
            'compare' => '>=',
            'type' => 'DATE',
        ],
    ];
}

// Filter by category
if (!empty($category_filter)) {
    $term_ids = array_column($category_filter, 'id');
    if (!empty($term_ids)) {
        $args['tax_query'] = [
            [
                'taxonomy' => 'type-evenement',
                'field' => 'term_id',
                'terms' => $term_ids,
            ],
        ];
    }
}

$events = new WP_Query($args);

if (!$events->have_posts()) {
    wp_reset_postdata();
    return;
}
?>

<section id="<?php echo esc_attr($block_id); ?>" class="block-events">
    <div class="block-events__container container">
        <?php if ($title) : ?>
            <h2 class="block-events__title"><?php echo esc_html($title); ?></h2>
        <?php endif; ?>

        <div class="block-events__grid">
            <?php while ($events->have_posts()) : $events->the_post(); ?>
                <?php
                $event_date = get_post_meta(get_the_ID(), 'evenement_date', true);
                $event_location = get_post_meta(get_the_ID(), 'evenement_lieu', true);
                ?>
                <article class="block-events__item">
                    <?php if (has_post_thumbnail()) : ?>
                        <a href="<?php the_permalink(); ?>" class="block-events__image-link">
                            <?php the_post_thumbnail('medium', ['class' => 'block-events__image', 'loading' => 'lazy']); ?>
                        </a>
                    <?php endif; ?>

                    <div class="block-events__content">
                        <?php if ($event_date) : ?>
                            <time class="block-events__date" datetime="<?php echo esc_attr($event_date); ?>">
                                <?php echo esc_html(lemur_format_date($event_date, 'j F Y')); ?>
                            </time>
                        <?php endif; ?>

                        <h3 class="block-events__item-title">
                            <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
                        </h3>

                        <?php if ($event_location) : ?>
                            <p class="block-events__location">
                                <svg class="block-events__location-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                    <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/>
                                    <circle cx="12" cy="10" r="3"/>
                                </svg>
                                <?php echo esc_html($event_location); ?>
                            </p>
                        <?php endif; ?>

                        <?php if (has_excerpt()) : ?>
                            <p class="block-events__excerpt"><?php echo esc_html(get_the_excerpt()); ?></p>
                        <?php endif; ?>
                    </div>
                </article>
            <?php endwhile; ?>
        </div>
    </div>
</section>

<?php wp_reset_postdata(); ?>
