<?php
/**
 * Template Name: Todo List (Kanban)
 *
 * Task management with Kanban board for bureau members.
 *
 * @package Lemur
 */

get_header();
?>

<main id="main-content" class="site-main member-area">
    <?php get_template_part('templates/parts/member-area/tasks/kanban'); ?>
</main>

<?php
get_footer();
