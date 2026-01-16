<?php
/**
 * Block: Timeline
 *
 * Chronological timeline of events.
 *
 * @package Lemur
 */

declare(strict_types=1);

$data = lemur_get_block_data();

$title = $data['title'] ?? '';
$events = $data['events'] ?? [];

$block_id = 'timeline-' . lemur_get_block_index();

if (empty($events)) {
    return;
}
?>

<section id="<?php echo esc_attr($block_id); ?>" class="block-timeline">
    <div class="block-timeline__container container">
        <?php if ($title) : ?>
            <h2 class="block-timeline__title"><?php echo esc_html($title); ?></h2>
        <?php endif; ?>

        <div class="block-timeline__list">
            <?php foreach ($events as $index => $event) : ?>
                <?php
                $date = $event['date'] ?? '';
                $event_title = $event['title'] ?? '';
                $description = $event['description'] ?? '';
                $image_id = $event['image'] ?? 0;

                if (empty($date) && empty($event_title)) {
                    continue;
                }
                ?>
                <div class="block-timeline__item <?php echo $index % 2 === 0 ? 'block-timeline__item--left' : 'block-timeline__item--right'; ?>">
                    <div class="block-timeline__marker"></div>

                    <div class="block-timeline__content">
                        <?php if ($date) : ?>
                            <span class="block-timeline__date"><?php echo esc_html($date); ?></span>
                        <?php endif; ?>

                        <?php if ($event_title) : ?>
                            <h3 class="block-timeline__event-title"><?php echo esc_html($event_title); ?></h3>
                        <?php endif; ?>

                        <?php if ($description) : ?>
                            <p class="block-timeline__description"><?php echo esc_html($description); ?></p>
                        <?php endif; ?>

                        <?php if ($image_id) : ?>
                            <?php
                            echo wp_get_attachment_image($image_id, 'medium', false, [
                                'class' => 'block-timeline__image',
                                'loading' => 'lazy',
                            ]);
                            ?>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
