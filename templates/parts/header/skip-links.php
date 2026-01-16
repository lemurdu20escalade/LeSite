<?php
/**
 * Skip Links for Accessibility
 *
 * @package Lemur
 */

?>
<div class="skip-links">
    <a href="#main-content" class="skip-link">
        <?php esc_html_e('Aller au contenu principal', 'lemur'); ?>
    </a>
    <a href="#main-navigation" class="skip-link">
        <?php esc_html_e('Aller à la navigation', 'lemur'); ?>
    </a>
    <?php if (is_active_sidebar('sidebar-1')) : ?>
    <a href="#sidebar" class="skip-link">
        <?php esc_html_e('Aller à la sidebar', 'lemur'); ?>
    </a>
    <?php endif; ?>
</div>
