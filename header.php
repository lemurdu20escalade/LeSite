<?php
/**
 * Header Template
 *
 * Displays the <head> section and includes the site header.
 *
 * @package Lemur
 */

declare(strict_types=1);
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php wp_head(); ?>
</head>

<body <?php body_class(); ?>>
<?php wp_body_open(); ?>

<?php get_template_part('templates/parts/header/skip-links'); ?>

<?php get_template_part('templates/parts/header'); ?>
