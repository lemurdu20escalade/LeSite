<?php
/**
 * Block: Partners / Partenaires
 *
 * Displays partner logos with optional links.
 *
 * @package Lemur
 */

declare(strict_types=1);

$data = lemur_get_block_data();
$index = lemur_get_block_index();

$title = $data['title'] ?? __('Nos partenaires', 'lemur');
$partners = $data['partners'] ?? [];

// Skip block if no partners
if (empty($partners)) {
    return;
}
?>

<section
    class="block-partners"
    aria-labelledby="partners-title-<?php echo esc_attr($index); ?>"
>
    <div class="container">
        <?php if ($title) : ?>
            <h2 id="partners-title-<?php echo esc_attr($index); ?>" class="block-partners__title">
                <?php echo esc_html($title); ?>
            </h2>
        <?php endif; ?>

        <div class="block-partners__grid">
            <?php foreach ($partners as $partner) : ?>
                <div class="partner-item">
                    <?php
                    $has_link = !empty($partner['url']);
                    $partner_name = $partner['name'] ?? '';

                    if ($has_link) :
                        ?>
                        <a
                            href="<?php echo esc_url($partner['url']); ?>"
                            target="_blank"
                            rel="noopener"
                            class="partner-item__link"
                        >
                    <?php endif; ?>

                    <?php if (!empty($partner['logo'])) : ?>
                        <img
                            src="<?php echo esc_url(wp_get_attachment_url($partner['logo'])); ?>"
                            alt="<?php echo esc_attr($partner_name); ?>"
                            class="partner-item__logo"
                            loading="lazy"
                        >
                    <?php else : ?>
                        <span class="partner-item__name">
                            <?php echo esc_html($partner_name); ?>
                        </span>
                    <?php endif; ?>

                    <?php if ($has_link) : ?>
                        <span class="sr-only">
                            <?php
                            printf(
                                /* translators: %s: partner name */
                                esc_html__('Visiter le site de %s (nouvel onglet)', 'lemur'),
                                esc_html($partner_name)
                            );
                            ?>
                        </span>
                        </a>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
