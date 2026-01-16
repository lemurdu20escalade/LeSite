<?php
/**
 * Footer Template Part
 *
 * Displays the site footer with contact info, hours, and navigation.
 * Uses theme options for dynamic content.
 *
 * @package Lemur
 */

declare(strict_types=1);

use Lemur\Fields\ThemeOptions;

// Get theme options
$address = lemur_get_contact('address');
$phone = lemur_get_contact('phone');
$email = lemur_get_contact('email');
$maps_url = lemur_get_contact('maps_url');
$hours = lemur_get_option(ThemeOptions::FIELD_OPENING_HOURS);
$adhesion_link = lemur_get_option(ThemeOptions::FIELD_ADHESION_LINK);
$social_urls = lemur_get_social_urls();
$transport_lines = lemur_get_transport_lines();

// Get logo
$logo_id = lemur_get_logo_id('footer') ?: lemur_get_logo_id();
?>

<footer class="site-footer">
    <div class="site-footer__container container">

        <!-- Column 1: Association info -->
        <div class="site-footer__col site-footer__col--info">
            <?php if ($logo_id) : ?>
                <?php
                echo wp_get_attachment_image($logo_id, 'medium', false, [
                    'class' => 'site-footer__logo',
                    'alt' => get_bloginfo('name'),
                    'loading' => 'lazy',
                ]);
                ?>
            <?php else : ?>
                <span class="site-footer__logo-text"><?php echo esc_html(get_bloginfo('name')); ?></span>
            <?php endif; ?>

            <?php if (get_bloginfo('description')) : ?>
                <p class="site-footer__tagline">
                    <?php echo esc_html(get_bloginfo('description')); ?>
                </p>
            <?php endif; ?>

            <p class="site-footer__affiliation">
                <?php esc_html_e('Association affiliée à la', 'lemur'); ?>
                <strong>FSGT</strong>
            </p>

            <?php if (!empty($social_urls)) : ?>
                <div class="site-footer__social">
                    <?php foreach ($social_urls as $network => $url) : ?>
                        <?php
                        $social_labels = [
                            'facebook'  => __('Suivez-nous sur Facebook (nouvelle fenêtre)', 'lemur'),
                            'instagram' => __('Suivez-nous sur Instagram (nouvelle fenêtre)', 'lemur'),
                            'youtube'   => __('Regardez nos vidéos sur YouTube (nouvelle fenêtre)', 'lemur'),
                        ];
                        ?>
                        <a
                            href="<?php echo esc_url($url); ?>"
                            class="site-footer__social-link site-footer__social-link--<?php echo esc_attr($network); ?>"
                            target="_blank"
                            rel="noopener noreferrer"
                            aria-label="<?php echo esc_attr($social_labels[$network] ?? ucfirst($network)); ?>"
                        >
                            <?php if ($network === 'facebook') : ?>
                                <svg class="site-footer__social-icon" width="24" height="24" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                                    <path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z"/>
                                </svg>
                            <?php elseif ($network === 'instagram') : ?>
                                <svg class="site-footer__social-icon" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                    <rect x="2" y="2" width="20" height="20" rx="5" ry="5"/>
                                    <path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z"/>
                                    <line x1="17.5" y1="6.5" x2="17.51" y2="6.5"/>
                                </svg>
                            <?php elseif ($network === 'youtube') : ?>
                                <svg class="site-footer__social-icon" width="24" height="24" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                                    <path d="M22.54 6.42a2.78 2.78 0 0 0-1.94-2C18.88 4 12 4 12 4s-6.88 0-8.6.46a2.78 2.78 0 0 0-1.94 2A29 29 0 0 0 1 11.75a29 29 0 0 0 .46 5.33A2.78 2.78 0 0 0 3.4 19c1.72.46 8.6.46 8.6.46s6.88 0 8.6-.46a2.78 2.78 0 0 0 1.94-2 29 29 0 0 0 .46-5.25 29 29 0 0 0-.46-5.33z"/>
                                    <polygon class="site-footer__youtube-play" points="9.75 15.02 15.5 11.75 9.75 8.48 9.75 15.02"/>
                                </svg>
                            <?php endif; ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Column 2: Contact -->
        <div class="site-footer__col site-footer__col--contact">
            <h3 class="site-footer__title"><?php esc_html_e('Contact', 'lemur'); ?></h3>

            <address class="site-footer__address">
                <?php if ($address) : ?>
                    <p class="site-footer__address-item">
                        <svg class="site-footer__icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/>
                            <circle cx="12" cy="10" r="3"/>
                        </svg>
                        <span>
                            <?php if ($maps_url) : ?>
                                <a href="<?php echo esc_url($maps_url); ?>" target="_blank" rel="noopener noreferrer">
                                    <?php echo nl2br(esc_html($address)); ?>
                                </a>
                            <?php else : ?>
                                <?php echo nl2br(esc_html($address)); ?>
                            <?php endif; ?>
                        </span>
                    </p>
                <?php endif; ?>

                <?php if ($phone) : ?>
                    <p class="site-footer__address-item">
                        <svg class="site-footer__icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/>
                        </svg>
                        <a href="tel:<?php echo esc_attr(lemur_format_phone_link($phone)); ?>">
                            <?php echo esc_html($phone); ?>
                        </a>
                    </p>
                <?php endif; ?>

                <?php if ($email) : ?>
                    <p class="site-footer__address-item site-footer__address-item--email">
                        <?php lemur_the_email($email, null, ['compact' => true, 'copy_button' => false, 'show_icon' => true]); ?>
                    </p>
                <?php endif; ?>
            </address>

            <?php if (!empty($transport_lines)) : ?>
                <div class="transport-lines" aria-label="<?php esc_attr_e('Transports en commun à proximité', 'lemur'); ?>">
                    <?php foreach ($transport_lines as $line) : ?>
                        <div class="transport-line">
                            <span class="transport-badge transport-badge--<?php echo esc_attr($line['type']); ?>">
                                <span class="transport-badge__icon"><?php echo lemur_transport_icon($line['type']); ?></span>
                                <span class="transport-badge__line" data-line="<?php echo esc_attr($line['line']); ?>">
                                    <?php echo esc_html($line['line']); ?>
                                </span>
                            </span>
                            <?php if (!empty($line['station'])) : ?>
                                <span class="transport-line__station"><?php echo esc_html($line['station']); ?></span>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Column 3: Opening hours -->
        <div class="site-footer__col site-footer__col--hours">
            <h3 class="site-footer__title"><?php esc_html_e('Horaires', 'lemur'); ?></h3>

            <?php if ($hours) : ?>
                <div class="site-footer__hours">
                    <?php echo wp_kses_post($hours); ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Column 4: Quick links -->
        <div class="site-footer__col site-footer__col--links">
            <h3 class="site-footer__title"><?php esc_html_e('Liens rapides', 'lemur'); ?></h3>

            <?php
            wp_nav_menu([
                'theme_location' => 'footer',
                'container' => false,
                'menu_class' => 'site-footer__menu',
                'fallback_cb' => false,
                'depth' => 1,
            ]);
            ?>

            <?php if ($adhesion_link) : ?>
                <a
                    href="<?php echo esc_url($adhesion_link); ?>"
                    class="btn btn--primary btn--sm site-footer__cta"
                    target="_blank"
                    rel="noopener noreferrer"
                >
                    <?php esc_html_e('Nous rejoindre', 'lemur'); ?>
                </a>
            <?php endif; ?>
        </div>
    </div>

    <!-- Copyright bar -->
    <div class="site-footer__bottom">
        <div class="site-footer__bottom-container container">
            <p class="site-footer__copyright">
                &copy; <?php echo esc_html(gmdate('Y')); ?> <?php echo esc_html(get_bloginfo('name')); ?>.
                <?php esc_html_e('Tous droits réservés.', 'lemur'); ?>
            </p>

            <nav class="site-footer__legal" aria-label="<?php esc_attr_e('Liens légaux', 'lemur'); ?>">
                <?php if (get_privacy_policy_url()) : ?>
                    <a href="<?php echo esc_url(get_privacy_policy_url()); ?>">
                        <?php esc_html_e('Politique de confidentialité', 'lemur'); ?>
                    </a>
                <?php endif; ?>

                <?php
                $mentions_page = lemur_get_page_by_path('mentions-legales');
                if ($mentions_page) :
                    ?>
                    <a href="<?php echo esc_url(get_permalink($mentions_page)); ?>">
                        <?php esc_html_e('Mentions légales', 'lemur'); ?>
                    </a>
                <?php endif; ?>
            </nav>
        </div>
    </div>
</footer>
