<?php
/**
 * Section: Prefooter FSGT
 *
 * Displays FSGT affiliation before the footer.
 *
 * @package Lemur
 */

declare(strict_types=1);

$fsgt_text = carbon_get_theme_option('fsgt_text') ?: __('Club affilié à la Fédération Sportive et Gymnique du Travail', 'lemur');
$fsgt_logo = carbon_get_theme_option('fsgt_logo');
?>

<section class="section section--prefooter-fsgt" aria-label="<?php esc_attr_e('Partenaire fédéral', 'lemur'); ?>">
    <div class="container">
        <div class="prefooter-fsgt">
            <?php if ($fsgt_logo) : ?>
                <img
                    src="<?php echo esc_url(wp_get_attachment_url($fsgt_logo)); ?>"
                    alt="<?php esc_attr_e('Logo FSGT', 'lemur'); ?>"
                    class="prefooter-fsgt__logo"
                    width="120"
                    height="60"
                    loading="lazy"
                >
            <?php endif; ?>

            <p class="prefooter-fsgt__text">
                <?php echo esc_html($fsgt_text); ?>
            </p>

            <a href="https://www.fsgt.org" target="_blank" rel="noopener" class="prefooter-fsgt__link">
                <?php esc_html_e('En savoir plus sur la FSGT', 'lemur'); ?>
                <span class="sr-only"><?php esc_html_e('(ouvre dans un nouvel onglet)', 'lemur'); ?></span>
            </a>
        </div>
    </div>
</section>
