<?php
/**
 * Block: HTML Custom
 *
 * Custom HTML block for advanced users.
 * Content is saved by users with unfiltered_html capability.
 * The security restriction is enforced in the admin editor,
 * not at render time.
 *
 * @package Lemur
 */

declare(strict_types=1);

$data = lemur_get_block_data();

$content = $data['content'] ?? '';

$block_id = 'html-' . lemur_get_block_index();

if (empty($content)) {
    return;
}
?>

<div id="<?php echo esc_attr($block_id); ?>" class="block-html-custom">
    <?php
    // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    echo $content;
    ?>
</div>
