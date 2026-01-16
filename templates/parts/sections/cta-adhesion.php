<?php
/**
 * Section: CTA Adhesion
 *
 * Call-to-action section for joining the club.
 *
 * @package Lemur
 */

declare(strict_types=1);

$adhesion_link = lemur_get_adhesion_link();
$adhesion_text = lemur_get_adhesion_text();
$adhesion_title = carbon_get_theme_option('home_cta_title') ?: __('Envie de grimper avec nous ?', 'lemur');
$adhesion_description = carbon_get_theme_option('home_cta_description') ?: __('Rejoignez notre association et découvrez l\'escalade dans une ambiance conviviale.', 'lemur');

// Fallback to page if no link configured
if (empty($adhesion_link)) {
    $adhesion_link = lemur_get_page_permalink('nous-rejoindre') ?: home_url('/');
}
?>

<section class="section section--cta-adhesion" aria-labelledby="cta-title">
    <div class="container">
        <div class="cta-adhesion">
            <div class="cta-adhesion__content">
                <h2 id="cta-title" class="cta-adhesion__title">
                    <?php echo esc_html($adhesion_title); ?>
                </h2>
                <p class="cta-adhesion__text">
                    <?php echo esc_html($adhesion_description); ?>
                </p>
            </div>

            <a href="<?php echo esc_url($adhesion_link); ?>" class="btn btn--lg cta-adhesion__button">
                <?php echo esc_html($adhesion_text); ?>
            </a>
        </div>
    </div>
</section>
