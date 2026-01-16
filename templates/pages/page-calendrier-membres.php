<?php
/**
 * Template Name: Calendrier Membres
 *
 * Monthly calendar with events and tasks.
 *
 * @package Lemur
 */

get_header();
?>

<main id="main-content" class="site-main member-area">
    <?php get_template_part('templates/parts/member-area/calendar'); ?>
</main>

<?php
get_footer();
