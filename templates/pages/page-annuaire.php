<?php
/**
 * Template Name: Annuaire Membres
 *
 * RGPD-compliant member directory (first names only).
 *
 * @package Lemur
 */

get_header();
?>

<main id="main-content" class="site-main member-area">
    <?php get_template_part('templates/parts/member-area/directory'); ?>
</main>

<?php
get_footer();
