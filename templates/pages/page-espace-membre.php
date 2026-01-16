<?php
/**
 * Template Name: Espace Membre
 *
 * Dashboard/hub page for the member area.
 *
 * @package Lemur
 */

get_header();
?>

<main id="main-content" class="site-main member-area">
    <?php get_template_part('templates/parts/member-area/dashboard'); ?>
</main>

<?php
get_footer();
