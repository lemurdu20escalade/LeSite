<?php
/**
 * Block: Equipment / Matériel
 *
 * Displays provided and required equipment in two columns.
 *
 * @package Lemur
 */

declare(strict_types=1);

$data = lemur_get_block_data();
$index = lemur_get_block_index();

$title = $data['title'] ?? __('Matériel', 'lemur');
$provided = $data['provided'] ?? [];
$required = $data['required'] ?? [];

// Skip block if no equipment
if (empty($provided) && empty($required)) {
    return;
}
?>

<section
    class="block-equipment"
    aria-labelledby="equipment-title-<?php echo esc_attr($index); ?>"
>
    <div class="container">
        <?php if ($title) : ?>
            <h2 id="equipment-title-<?php echo esc_attr($index); ?>" class="block-equipment__title">
                <?php echo esc_html($title); ?>
            </h2>
        <?php endif; ?>

        <div class="equipment-columns">
            <?php if (!empty($provided)) : ?>
                <div class="equipment-column equipment-column--provided">
                    <h3 class="equipment-column__title">
                        <span class="equipment-column__icon" aria-hidden="true">&#x2705;</span>
                        <?php esc_html_e('Fourni par le club', 'lemur'); ?>
                    </h3>
                    <ul class="equipment-list">
                        <?php foreach ($provided as $item) : ?>
                            <li class="equipment-list__item">
                                <?php echo esc_html($item['item'] ?? ''); ?>
                                <?php if (!empty($item['note'])) : ?>
                                    <small>(<?php echo esc_html($item['note']); ?>)</small>
                                <?php endif; ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <?php if (!empty($required)) : ?>
                <div class="equipment-column equipment-column--required">
                    <h3 class="equipment-column__title">
                        <span class="equipment-column__icon" aria-hidden="true">&#x1F392;</span>
                        <?php esc_html_e('À apporter', 'lemur'); ?>
                    </h3>
                    <ul class="equipment-list">
                        <?php foreach ($required as $item) : ?>
                            <li class="equipment-list__item">
                                <?php echo esc_html($item['item'] ?? ''); ?>
                                <?php if (!empty($item['note'])) : ?>
                                    <small>(<?php echo esc_html($item['note']); ?>)</small>
                                <?php endif; ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>
