<?php
/**
 * Block: Licence Découverte
 *
 * Displays information about trial/discovery membership.
 *
 * @package Lemur
 */

declare(strict_types=1);

$data = lemur_get_block_data();

$title = $data['title'] ?? __('Envie d\'essayer ?', 'lemur');
$text = $data['text'] ?? __('Venez découvrir le club avec une licence découverte. Le coût réel est de 3€ (assurance FSGT), mais vous donnez ce que vous voulez.', 'lemur');
$min_price = $data['min_price'] ?? '3€';
$contact_url = $data['contact_url'] ?? lemur_get_page_permalink('contact') ?? '';
?>

<section class="block-licence-decouverte">
    <div class="container">
        <div class="decouverte-card">
            <div class="decouverte-card__content">
                <h2 class="decouverte-card__title"><?php echo esc_html($title); ?></h2>
                <p class="decouverte-card__text"><?php echo esc_html($text); ?></p>
                <ul class="decouverte-card__list">
                    <li><?php esc_html_e('Valable pour une séance', 'lemur'); ?></li>
                    <li><?php esc_html_e('Assurance incluse', 'lemur'); ?></li>
                    <li>
                        <?php
                        printf(
                            /* translators: %s: minimum price */
                            esc_html__('Prix libre (minimum %s)', 'lemur'),
                            esc_html($min_price)
                        );
                        ?>
                    </li>
                </ul>
            </div>
            <?php if ($contact_url) : ?>
                <div class="decouverte-card__action">
                    <a href="<?php echo esc_url($contact_url); ?>" class="btn btn--outline btn--lg">
                        <?php esc_html_e('Nous contacter', 'lemur'); ?>
                    </a>
                    <p class="decouverte-card__note"><?php esc_html_e('Prévenez-nous de votre venue !', 'lemur'); ?></p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>
