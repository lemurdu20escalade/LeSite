<?php
/**
 * Section: Hero Banner
 *
 * Displays the homepage hero with logo, background image and slogan.
 *
 * @package Lemur
 */

declare(strict_types=1);

$logo_url = lemur_get_logo_url();
$hero_image = carbon_get_theme_option('home_hero_image');
$hero_slogan = carbon_get_theme_option('home_hero_slogan') ?: __('L\'escalade pour tous', 'lemur');
$hero_subtitle = carbon_get_theme_option('home_hero_subtitle');
$hero_text_color = carbon_get_theme_option('home_hero_text_color') ?: 'light';
$cta_link = lemur_get_adhesion_link() ?: lemur_get_page_permalink('nous-rejoindre');
$discover_link = lemur_get_page_permalink('grimper');

$hero_classes = ['hero-banner'];
if ($hero_text_color === 'dark') {
    $hero_classes[] = 'hero-banner--dark';
}
?>

<section class="<?php echo esc_attr(implode(' ', $hero_classes)); ?>" aria-label="<?php esc_attr_e('Accueil', 'lemur'); ?>">
    <?php if ($hero_image) : ?>
        <div class="hero-banner__background">
            <?php
            lemur_picture((int) $hero_image, [
                'mobile' => [
                    'size' => 'lemur-hero-mobile',
                    'media' => '(max-width: 767px)',
                ],
                'desktop' => [
                    'size' => 'lemur-hero',
                    'media' => '(min-width: 768px)',
                ],
            ], ['class' => 'hero-banner__image'], false);
            ?>
            <div class="hero-banner__overlay" aria-hidden="true"></div>
        </div>
    <?php endif; ?>

    <div class="hero-banner__content container">
        <?php if ($logo_url) : ?>
            <img
                src="<?php echo esc_url($logo_url); ?>"
                alt=""
                class="hero-banner__logo"
                aria-hidden="true"
                width="200"
                height="100"
            >
        <?php endif; ?>

        <h1 class="hero-banner__title"><?php echo esc_html($hero_slogan); ?></h1>

        <?php if ($hero_subtitle) : ?>
            <p class="hero-banner__subtitle"><?php echo esc_html($hero_subtitle); ?></p>
        <?php endif; ?>

        <div class="hero-banner__cta">
            <a href="<?php echo esc_url($cta_link); ?>" class="btn btn--primary btn--lg">
                <?php echo esc_html(lemur_get_adhesion_text()); ?>
            </a>
            <?php if ($discover_link) : ?>
                <a href="<?php echo esc_url($discover_link); ?>" class="btn btn--outline-light btn--lg">
                    <?php esc_html_e('Découvrir', 'lemur'); ?>
                </a>
            <?php endif; ?>
        </div>
    </div>
</section>
