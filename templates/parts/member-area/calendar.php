<?php
/**
 * Calendrier Espace Membre
 *
 * Monthly calendar view with events and tasks.
 *
 * @package Lemur
 */

declare(strict_types=1);

defined('ABSPATH') || exit;

// Get current month/year or from query params
$month = isset($_GET['month']) ? max(1, min(12, (int) $_GET['month'])) : (int) date('n');
$year = isset($_GET['year']) ? max(2020, min(2030, (int) $_GET['year'])) : (int) date('Y');

// Calculate previous/next month
$prev_month = $month - 1;
$prev_year = $year;
if ($prev_month < 1) {
    $prev_month = 12;
    $prev_year--;
}

$next_month = $month + 1;
$next_year = $year;
if ($next_month > 12) {
    $next_month = 1;
    $next_year++;
}

// Get calendar data
$calendar_data = lemur_get_calendar_data($month, $year);

// Month info
$first_day_timestamp = mktime(0, 0, 0, $month, 1, $year);
$days_in_month = (int) date('t', $first_day_timestamp);
$first_day_of_week = (int) date('N', $first_day_timestamp); // 1 = Monday
$month_name = date_i18n('F Y', $first_day_timestamp);

// Weekday names
$weekdays = [
    __('Lun', 'lemur'),
    __('Mar', 'lemur'),
    __('Mer', 'lemur'),
    __('Jeu', 'lemur'),
    __('Ven', 'lemur'),
    __('Sam', 'lemur'),
    __('Dim', 'lemur'),
];

$today = date('Y-m-d');
?>

<div class="member-calendar" data-month="<?php echo esc_attr($month); ?>" data-year="<?php echo esc_attr($year); ?>">
    <!-- Header -->
    <header class="calendar__header">
        <h1 class="calendar__page-title"><?php esc_html_e('Calendrier', 'lemur'); ?></h1>
    </header>

    <!-- Navigation -->
    <?php lemur_render_member_nav('calendrier-membres'); ?>

    <!-- Calendar Navigation -->
    <div class="calendar__nav-bar">
        <a href="<?php echo esc_url(add_query_arg(['month' => $prev_month, 'year' => $prev_year])); ?>"
           class="calendar__nav-btn calendar__nav-btn--prev"
           aria-label="<?php esc_attr_e('Mois précédent', 'lemur'); ?>">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                <polyline points="15 18 9 12 15 6"/>
            </svg>
        </a>

        <h2 class="calendar__month-title"><?php echo esc_html($month_name); ?></h2>

        <a href="<?php echo esc_url(add_query_arg(['month' => $next_month, 'year' => $next_year])); ?>"
           class="calendar__nav-btn calendar__nav-btn--next"
           aria-label="<?php esc_attr_e('Mois suivant', 'lemur'); ?>">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                <polyline points="9 18 15 12 9 6"/>
            </svg>
        </a>
    </div>

    <!-- Legend -->
    <div class="calendar__legend">
        <span class="calendar__legend-item calendar__legend-item--event">
            <span class="calendar__legend-dot" aria-hidden="true"></span>
            <?php esc_html_e('Événement', 'lemur'); ?>
        </span>
        <span class="calendar__legend-item calendar__legend-item--task">
            <span class="calendar__legend-dot" aria-hidden="true"></span>
            <?php esc_html_e('Tâche', 'lemur'); ?>
        </span>
    </div>

    <!-- Calendar Grid -->
    <div class="calendar__grid" role="grid" aria-label="<?php echo esc_attr($month_name); ?>">
        <!-- Weekday Headers -->
        <div class="calendar__weekdays" role="row">
            <?php foreach ($weekdays as $day): ?>
                <div class="calendar__weekday" role="columnheader">
                    <?php echo esc_html($day); ?>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Days Grid -->
        <div class="calendar__days" role="rowgroup">
            <?php
            // Empty cells before first day
            for ($i = 1; $i < $first_day_of_week; $i++):
            ?>
                <div class="calendar__day calendar__day--empty" role="gridcell" aria-hidden="true"></div>
            <?php endfor; ?>

            <?php
            // Days of month
            for ($day = 1; $day <= $days_in_month; $day++):
                $date_key = sprintf('%04d-%02d-%02d', $year, $month, $day);
                $day_data = $calendar_data[$date_key] ?? ['events' => [], 'tasks' => []];
                $events = $day_data['events'];
                $tasks = $day_data['tasks'];
                $has_items = !empty($events) || !empty($tasks);
                $is_today = $date_key === $today;

                $day_classes = ['calendar__day'];
                if ($is_today) $day_classes[] = 'calendar__day--today';
                if ($has_items) $day_classes[] = 'calendar__day--has-items';
            ?>
                <div class="<?php echo esc_attr(implode(' ', $day_classes)); ?>"
                     role="gridcell"
                     data-date="<?php echo esc_attr($date_key); ?>"
                     <?php if ($is_today): ?>aria-current="date"<?php endif; ?>>

                    <span class="calendar__day-number"><?php echo esc_html($day); ?></span>

                    <?php if ($has_items): ?>
                        <ul class="calendar__items">
                            <?php foreach ($events as $event): ?>
                                <li class="calendar__item calendar__item--event">
                                    <a href="<?php echo esc_url(get_permalink($event)); ?>" class="calendar__item-link">
                                        <?php echo esc_html(wp_trim_words(get_the_title($event), 3, '...')); ?>
                                    </a>
                                </li>
                            <?php endforeach; ?>

                            <?php foreach ($tasks as $task): ?>
                                <?php
                                $task_status = carbon_get_post_meta($task->ID, \Lemur\CustomPostTypes\Tasks::FIELD_STATUS);
                                $status_class = 'calendar__item--task';
                                if ($task_status === \Lemur\CustomPostTypes\Tasks::STATUS_DONE) {
                                    $status_class .= ' calendar__item--done';
                                }
                                ?>
                                <li class="calendar__item <?php echo esc_attr($status_class); ?>">
                                    <span class="calendar__item-text">
                                        <?php echo esc_html(wp_trim_words(get_the_title($task), 3, '...')); ?>
                                    </span>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>

                    <?php if ($has_items && (count($events) + count($tasks)) > 2): ?>
                        <span class="calendar__day-more">
                            +<?php echo esc_html(count($events) + count($tasks) - 2); ?>
                        </span>
                    <?php endif; ?>
                </div>
            <?php endfor; ?>

            <?php
            // Empty cells after last day
            $last_day_of_week = (int) date('N', mktime(0, 0, 0, $month, $days_in_month, $year));
            for ($i = $last_day_of_week; $i < 7; $i++):
            ?>
                <div class="calendar__day calendar__day--empty" role="gridcell" aria-hidden="true"></div>
            <?php endfor; ?>
        </div>
    </div>

    <!-- Upcoming items list (mobile-friendly) -->
    <section class="calendar__upcoming" aria-labelledby="upcoming-title">
        <h3 id="upcoming-title" class="calendar__upcoming-title">
            <?php esc_html_e('Ce mois-ci', 'lemur'); ?>
        </h3>

        <?php if (!empty($calendar_data)): ?>
            <ul class="calendar__upcoming-list">
                <?php
                foreach ($calendar_data as $date => $data):
                    $date_formatted = date_i18n('j M', strtotime($date));
                    foreach ($data['events'] as $event):
                ?>
                    <li class="calendar__upcoming-item calendar__upcoming-item--event">
                        <span class="calendar__upcoming-date"><?php echo esc_html($date_formatted); ?></span>
                        <a href="<?php echo esc_url(get_permalink($event)); ?>" class="calendar__upcoming-link">
                            <?php echo esc_html(get_the_title($event)); ?>
                        </a>
                        <span class="calendar__upcoming-type"><?php esc_html_e('Événement', 'lemur'); ?></span>
                    </li>
                <?php
                    endforeach;
                    foreach ($data['tasks'] as $task):
                        $status = carbon_get_post_meta($task->ID, \Lemur\CustomPostTypes\Tasks::FIELD_STATUS);
                ?>
                    <li class="calendar__upcoming-item calendar__upcoming-item--task <?php echo $status === \Lemur\CustomPostTypes\Tasks::STATUS_DONE ? 'calendar__upcoming-item--done' : ''; ?>">
                        <span class="calendar__upcoming-date"><?php echo esc_html($date_formatted); ?></span>
                        <span class="calendar__upcoming-link"><?php echo esc_html(get_the_title($task)); ?></span>
                        <span class="calendar__upcoming-type"><?php esc_html_e('Tâche', 'lemur'); ?></span>
                    </li>
                <?php
                    endforeach;
                endforeach;
                ?>
            </ul>
        <?php else: ?>
            <p class="calendar__upcoming-empty">
                <?php esc_html_e('Aucun événement ou tâche ce mois-ci.', 'lemur'); ?>
            </p>
        <?php endif; ?>
    </section>

    <!-- Quick navigation -->
    <footer class="calendar__footer">
        <a href="<?php echo esc_url(add_query_arg(['month' => (int) date('n'), 'year' => (int) date('Y')])); ?>"
           class="calendar__today-btn">
            <?php esc_html_e('Aujourd\'hui', 'lemur'); ?>
        </a>
    </footer>
</div>
