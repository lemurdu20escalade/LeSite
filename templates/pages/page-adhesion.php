<?php
/**
 * Template Name: Adhésion / Nous rejoindre
 *
 * Displays membership options with tiered pricing system (prix conscient).
 *
 * @package Lemur
 */

declare(strict_types=1);

use Lemur\Fields\ThemeOptions;

get_header();

$adhesion_link = lemur_get_option(ThemeOptions::FIELD_ADHESION_LINK);
$adhesion_text = lemur_get_option(ThemeOptions::FIELD_ADHESION_TEXT) ?: __('Nous rejoindre', 'lemur');
?>

<main id="main-content" class="site-main">
    <div class="container">
        <header class="page-header page-header--centered">
            <h1 class="page-header__title"><?php the_title(); ?></h1>
            <?php if (has_excerpt()) : ?>
                <p class="page-header__description"><?php echo esc_html(get_the_excerpt()); ?></p>
            <?php endif; ?>
        </header>

        <?php
        // Content managed via page builder
        if (lemur_has_page_sections()) {
            lemur_render_page_sections();
        }
        ?>

        <?php if ($adhesion_link) : ?>
            <section class="cta-adhesion-final">
                <div class="cta-adhesion-final__content">
                    <h2 class="cta-adhesion-final__title"><?php esc_html_e('Prêt·e à nous rejoindre ?', 'lemur'); ?></h2>
                    <p class="cta-adhesion-final__text">
                        <?php esc_html_e('Choisissez votre formule et votre palier de cotisation. Bienvenue dans l\'aventure !', 'lemur'); ?>
                    </p>
                    <a href="<?php echo esc_url($adhesion_link); ?>" class="btn btn--primary btn--lg" target="_blank" rel="noopener">
                        <?php echo esc_html($adhesion_text); ?>
                        <span class="sr-only"><?php esc_html_e('(s\'ouvre dans un nouvel onglet)', 'lemur'); ?></span>
                    </a>
                </div>
            </section>
        <?php endif; ?>
    </div>
</main>

<?php
get_footer();
