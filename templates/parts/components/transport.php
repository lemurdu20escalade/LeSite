<?php
/**
 * Transport lines component
 *
 * Displays public transport access information (metro, RER, bus, tram).
 *
 * @package Lemur
 *
 * @var array $args {
 *     @type array $lines Array of transport lines with 'type', 'line', 'station' keys
 * }
 */

declare(strict_types=1);

defined('ABSPATH') || exit;

$lines = $args['lines'] ?? [];

if (empty($lines) || !is_array($lines)) {
    return;
}

$type_labels = [
    'metro' => __('Métro', 'lemur'),
    'rer'   => __('RER', 'lemur'),
    'bus'   => __('Bus', 'lemur'),
    'tram'  => __('Tram', 'lemur'),
];

$type_icons = [
    'metro' => 'M',
    'rer'   => 'RER',
    'bus'   => 'Bus',
    'tram'  => 'T',
];
?>

<ul class="transport-lines" role="list">
    <?php foreach ($lines as $line) :
        $type = sanitize_text_field($line['type'] ?? '');
        $line_number = sanitize_text_field($line['line'] ?? '');
        $station = sanitize_text_field($line['station'] ?? '');

        if ($type === '' || $line_number === '') {
            continue;
        }

        $type_label = $type_labels[$type] ?? $type;
        $type_icon = $type_icons[$type] ?? $type;
    ?>
        <li class="transport-lines__item transport-lines__item--<?php echo esc_attr($type); ?>">
            <span class="transport-lines__badge" aria-hidden="true">
                <span class="transport-lines__type"><?php echo esc_html($type_icon); ?></span>
                <span class="transport-lines__number"><?php echo esc_html($line_number); ?></span>
            </span>
            <span class="transport-lines__info">
                <span class="sr-only"><?php echo esc_html($type_label); ?> <?php echo esc_html($line_number); ?> :</span>
                <?php if ($station !== '') : ?>
                    <span class="transport-lines__station"><?php echo esc_html($station); ?></span>
                <?php endif; ?>
            </span>
        </li>
    <?php endforeach; ?>
</ul>
