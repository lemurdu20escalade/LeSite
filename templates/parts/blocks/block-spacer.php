<?php
/**
 * Block: Spacer
 *
 * Adds vertical spacing between sections.
 *
 * @package Lemur
 */

declare(strict_types=1);

$data = lemur_get_block_data();

$height = $data['height'] ?? 'md';

$block_id = 'spacer-' . lemur_get_block_index();
?>

<div
    id="<?php echo esc_attr($block_id); ?>"
    class="block-spacer block-spacer--<?php echo esc_attr($height); ?>"
    aria-hidden="true"
></div>
