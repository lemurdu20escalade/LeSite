<?php
/**
 * Template: Page d'accueil
 *
 * @package Lemur
 */

get_header();
?>

<main id="main-content" class="site-main">

    <?php // 1. BANNER - Logo + Photo + Slogan ?>
    <?php get_template_part('templates/parts/sections/hero-banner'); ?>

    <?php // 2. VALEURS / L'ASSO (mention FSGT) ?>
    <?php get_template_part('templates/parts/sections/home-values'); ?>

    <?php // 3. GRIMPER - Tableaux créneaux ?>
    <?php get_template_part('templates/parts/sections/home-grimper'); ?>

    <?php // 4. ACTIVITÉS - Ce qu'on fait ?>
    <?php get_template_part('templates/parts/sections/home-activities'); ?>

    <?php // 5. ACTU - Section conditionnelle ?>
    <?php get_template_part('templates/parts/sections/home-actu'); ?>

    <?php // 6. NOUS REJOINDRE - CTA Adhésion ?>
    <?php get_template_part('templates/parts/sections/cta-adhesion'); ?>

    <?php // 7. FSGT - Préfooter ?>
    <?php get_template_part('templates/parts/sections/prefooter-fsgt'); ?>

</main>

<?php
get_footer();
