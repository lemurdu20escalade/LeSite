<?php
/**
 * Header Template Part
 *
 * Displays the site header with sticky behavior and accessible navigation.
 * Uses Alpine.js for interactive functionality.
 *
 * @package Lemur
 */

declare(strict_types=1);

use Lemur\Fields\ThemeOptions;

// Get CTA link
$adhesion_link = lemur_get_option(ThemeOptions::FIELD_ADHESION_LINK);
if (!$adhesion_link) {
    $adhesion_page = get_page_by_path('nous-rejoindre');
    $adhesion_link = $adhesion_page ? get_permalink($adhesion_page) : '#';
}

// Get member area link
$member_page = get_page_by_path('espace-membre');
$member_link = $member_page ? get_permalink($member_page) : home_url('/espace-membre/');
?>

<header
    class="site-header"
    x-data="siteHeader()"
    x-bind="headerBindings"
    role="banner"
>
    <div class="site-header__container container">

        <!-- Logo -->
        <a
            href="<?php echo esc_url(home_url('/')); ?>"
            class="site-header__logo"
            aria-label="<?php echo esc_attr(get_bloginfo('name')); ?> - <?php esc_attr_e('Accueil', 'lemur'); ?>"
        >
            <?php
            $logo_id = lemur_get_logo_id();
            if ($logo_id) :
                echo wp_get_attachment_image($logo_id, 'medium', false, [
                    'class' => 'site-header__logo-img',
                    'alt' => esc_attr(get_bloginfo('name')),
                ]);
            else :
                ?>
                <span class="site-header__logo-text"><?php echo esc_html(get_bloginfo('name')); ?></span>
            <?php endif; ?>
        </a>

        <!-- Navigation principale (desktop) -->
        <nav
            class="site-header__nav"
            aria-label="<?php esc_attr_e('Navigation principale', 'lemur'); ?>"
        >
            <?php
            wp_nav_menu([
                'theme_location' => 'primary',
                'container' => false,
                'menu_class' => 'nav-menu',
                'menu_id' => 'primary-menu',
                'fallback_cb' => false,
                'walker' => new \Lemur\Core\NavigationWalker(),
                'items_wrap' => '<ul id="%1$s" class="%2$s" role="menubar">%3$s</ul>',
            ]);
            ?>
        </nav>

        <!-- Actions : CTA + Espace membre -->
        <div class="site-header__actions">
            <!-- Bouton CTA "Nous rejoindre" - toujours visible -->
            <a
                href="<?php echo esc_url($adhesion_link); ?>"
                class="btn btn--primary site-header__cta"
            >
                <?php esc_html_e('Nous rejoindre', 'lemur'); ?>
            </a>

            <!-- Espace membre (si connecté) -->
            <?php if (is_user_logged_in()) : ?>
                <a
                    href="<?php echo esc_url($member_link); ?>"
                    class="btn btn--secondary btn--sm site-header__member-btn"
                >
                    <?php esc_html_e('Espace membre', 'lemur'); ?>
                </a>
            <?php endif; ?>
        </div>

        <!-- Menu burger (mobile) -->
        <button
            class="site-header__burger"
            type="button"
            x-ref="burger"
            x-on:click="mobileMenuOpen = !mobileMenuOpen"
            x-bind:aria-expanded="mobileMenuOpen.toString()"
            aria-controls="mobile-menu"
            aria-label="<?php esc_attr_e('Menu', 'lemur'); ?>"
        >
            <span class="burger-line" aria-hidden="true"></span>
            <span class="burger-line" aria-hidden="true"></span>
            <span class="burger-line" aria-hidden="true"></span>
        </button>
    </div>

    <!-- Menu mobile -->
    <div
        id="mobile-menu"
        class="site-header__mobile-menu"
        x-ref="mobileMenu"
        x-show="mobileMenuOpen"
        x-transition:enter="mobile-menu-enter"
        x-transition:enter-start="mobile-menu-enter-start"
        x-transition:enter-end="mobile-menu-enter-end"
        x-transition:leave="mobile-menu-leave"
        x-transition:leave-start="mobile-menu-leave-start"
        x-transition:leave-end="mobile-menu-leave-end"
        x-cloak
        role="dialog"
        aria-modal="true"
        aria-label="<?php esc_attr_e('Menu de navigation', 'lemur'); ?>"
    >
        <nav aria-label="<?php esc_attr_e('Menu mobile', 'lemur'); ?>">
            <?php
            wp_nav_menu([
                'theme_location' => 'primary',
                'container' => false,
                'menu_class' => 'mobile-nav-menu',
                'fallback_cb' => false,
                'depth' => 2,
            ]);
            ?>
        </nav>

        <!-- Lien espace membre en mobile -->
        <?php if (is_user_logged_in()) : ?>
            <div class="mobile-nav-member">
                <a
                    href="<?php echo esc_url($member_link); ?>"
                    class="btn btn--secondary btn--block"
                >
                    <?php esc_html_e('Espace membre', 'lemur'); ?>
                </a>
            </div>
        <?php endif; ?>
    </div>
</header>
