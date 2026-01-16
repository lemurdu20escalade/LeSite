<?php
/**
 * Block: Pricing
 *
 * Pricing table with multiple plans.
 *
 * @package Lemur
 */

declare(strict_types=1);

$data = lemur_get_block_data();

$title = $data['title'] ?? '';
$plans = $data['plans'] ?? [];

$block_id = 'pricing-' . lemur_get_block_index();

if (empty($plans)) {
    return;
}
?>

<section id="<?php echo esc_attr($block_id); ?>" class="block-pricing">
    <div class="block-pricing__container container">
        <?php if ($title) : ?>
            <h2 class="block-pricing__title"><?php echo esc_html($title); ?></h2>
        <?php endif; ?>

        <div class="block-pricing__grid">
            <?php foreach ($plans as $plan) : ?>
                <?php
                $name = $plan['name'] ?? '';
                $price = $plan['price'] ?? '';
                $period = $plan['period'] ?? '';
                $features_text = $plan['features'] ?? '';
                $cta_text = $plan['cta_text'] ?? '';
                $cta_link = $plan['cta_link'] ?? '';
                $highlighted = !empty($plan['highlighted']);

                $features = array_filter(array_map('trim', explode("\n", $features_text)));

                if (empty($name)) {
                    continue;
                }

                $card_classes = ['block-pricing__card'];
                if ($highlighted) {
                    $card_classes[] = 'block-pricing__card--highlighted';
                }
                ?>
                <div class="<?php echo esc_attr(implode(' ', $card_classes)); ?>">
                    <?php if ($highlighted) : ?>
                        <span class="block-pricing__badge"><?php esc_html_e('Populaire', 'lemur'); ?></span>
                    <?php endif; ?>

                    <h3 class="block-pricing__plan-name"><?php echo esc_html($name); ?></h3>

                    <div class="block-pricing__price">
                        <span class="block-pricing__amount"><?php echo esc_html($price); ?></span>
                        <?php if ($period) : ?>
                            <span class="block-pricing__period"><?php echo esc_html($period); ?></span>
                        <?php endif; ?>
                    </div>

                    <?php if (!empty($features)) : ?>
                        <ul class="block-pricing__features">
                            <?php foreach ($features as $feature) : ?>
                                <li class="block-pricing__feature">
                                    <svg class="block-pricing__check" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" aria-hidden="true">
                                        <polyline points="20 6 9 17 4 12"/>
                                    </svg>
                                    <?php echo esc_html($feature); ?>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>

                    <?php if ($cta_text && $cta_link) : ?>
                        <a
                            href="<?php echo esc_url($cta_link); ?>"
                            class="btn <?php echo $highlighted ? 'btn--primary' : 'btn--outline'; ?> block-pricing__cta"
                        >
                            <?php echo esc_html($cta_text); ?>
                        </a>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
