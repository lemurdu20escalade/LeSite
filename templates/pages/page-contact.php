<?php
/**
 * Template Name: Contact
 *
 * Displays contact information with address, map, hours and Schema.org LocalBusiness.
 *
 * @package Lemur
 */

declare(strict_types=1);

use Lemur\Fields\ThemeOptions;

get_header();

// Get contact data from theme options
$address = lemur_get_option(ThemeOptions::FIELD_ADDRESS);
$email = lemur_get_option(ThemeOptions::FIELD_EMAIL);
$phone = lemur_get_option(ThemeOptions::FIELD_PHONE);
$maps_url = lemur_get_option(ThemeOptions::FIELD_MAPS_URL);
$map_embed = lemur_get_option(ThemeOptions::FIELD_MAP_EMBED);
$map_image = lemur_get_option(ThemeOptions::FIELD_MAP_IMAGE);
$transport_lines = lemur_get_option(ThemeOptions::FIELD_TRANSPORT_LINES);
$additional_info = lemur_get_option(ThemeOptions::FIELD_CONTACT_ADDITIONAL);
$schedule = lemur_get_option(ThemeOptions::FIELD_SCHEDULE);
?>

<main id="main-content" class="site-main">
    <div class="container">
        <header class="page-header page-header--centered">
            <h1 class="page-header__title"><?php the_title(); ?></h1>
            <?php if (has_excerpt()) : ?>
                <p class="page-header__description"><?php echo esc_html(get_the_excerpt()); ?></p>
            <?php endif; ?>
        </header>

        <div class="contact-layout">
            <!-- Contact Information -->
            <div class="contact-info">
                <!-- Address -->
                <?php if ($address) : ?>
                    <section class="contact-card">
                        <div class="contact-card__icon" aria-hidden="true">
                            <?php lemur_the_ui_icon('location', ['width' => 24, 'height' => 24]); ?>
                        </div>
                        <div class="contact-card__content">
                            <h2 class="contact-card__title"><?php esc_html_e('Adresse', 'lemur'); ?></h2>
                            <address class="contact-card__address">
                                <?php echo nl2br(esc_html($address)); ?>
                            </address>
                            <?php if ($maps_url) : ?>
                                <a
                                    href="<?php echo esc_url($maps_url); ?>"
                                    class="contact-card__link"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                >
                                    <?php esc_html_e('Voir sur Google Maps', 'lemur'); ?>
                                    <?php lemur_the_ui_icon('external-link', ['width' => 16, 'height' => 16]); ?>
                                </a>
                            <?php endif; ?>
                        </div>
                    </section>
                <?php endif; ?>

                <!-- Email -->
                <?php if ($email) : ?>
                    <section class="contact-card">
                        <div class="contact-card__icon" aria-hidden="true">
                            <?php lemur_the_ui_icon('mail', ['width' => 24, 'height' => 24]); ?>
                        </div>
                        <div class="contact-card__content">
                            <h2 class="contact-card__title"><?php esc_html_e('Email', 'lemur'); ?></h2>
                            <?php lemur_the_email($email, null, ['class' => 'contact-card__email', 'inline' => true]); ?>
                        </div>
                    </section>
                <?php endif; ?>

                <!-- Phone -->
                <?php if ($phone) : ?>
                    <section class="contact-card">
                        <div class="contact-card__icon" aria-hidden="true">
                            <?php lemur_the_ui_icon('phone', ['width' => 24, 'height' => 24]); ?>
                        </div>
                        <div class="contact-card__content">
                            <h2 class="contact-card__title"><?php esc_html_e('Téléphone', 'lemur'); ?></h2>
                            <a href="tel:<?php echo esc_attr(lemur_format_phone_link($phone)); ?>" class="contact-card__phone">
                                <?php echo esc_html($phone); ?>
                            </a>
                        </div>
                    </section>
                <?php endif; ?>

                <!-- Schedule/Hours -->
                <?php if (!empty($schedule) && is_array($schedule)) : ?>
                    <section class="contact-card contact-card--hours">
                        <div class="contact-card__icon" aria-hidden="true">
                            <?php lemur_the_ui_icon('clock', ['width' => 24, 'height' => 24]); ?>
                        </div>
                        <div class="contact-card__content">
                            <h2 class="contact-card__title"><?php esc_html_e('Horaires', 'lemur'); ?></h2>
                            <dl class="contact-hours">
                                <?php foreach ($schedule as $slot) :
                                    $day = $slot['day'] ?? '';
                                    $hours = $slot['hours'] ?? '';
                                    $location = $slot['location'] ?? '';

                                    if (empty($day) || empty($hours)) {
                                        continue;
                                    }
                                ?>
                                    <div class="contact-hours__row">
                                        <dt class="contact-hours__day">
                                            <?php echo esc_html($day); ?>
                                            <?php if ($location) : ?>
                                                <span class="contact-hours__location"><?php echo esc_html($location); ?></span>
                                            <?php endif; ?>
                                        </dt>
                                        <dd class="contact-hours__time"><?php echo esc_html($hours); ?></dd>
                                    </div>
                                <?php endforeach; ?>
                            </dl>
                        </div>
                    </section>
                <?php endif; ?>

                <!-- Transport Lines -->
                <?php if (!empty($transport_lines) && is_array($transport_lines)) : ?>
                    <section class="contact-card contact-card--transport">
                        <div class="contact-card__icon" aria-hidden="true">
                            <?php lemur_the_ui_icon('arrow-right', ['width' => 24, 'height' => 24]); ?>
                        </div>
                        <div class="contact-card__content">
                            <h2 class="contact-card__title"><?php esc_html_e('Accès', 'lemur'); ?></h2>
                            <?php get_template_part('templates/parts/components/transport', null, ['lines' => $transport_lines]); ?>
                        </div>
                    </section>
                <?php endif; ?>

                <!-- Additional Information -->
                <?php if ($additional_info) : ?>
                    <section class="contact-additional">
                        <?php echo wp_kses_post($additional_info); ?>
                    </section>
                <?php endif; ?>
            </div>

            <!-- Map -->
            <div class="contact-map">
                <?php if ($map_embed) : ?>
                    <!-- Embed Google Maps / OpenStreetMap -->
                    <div class="contact-map__embed">
                        <?php
                        // Allow only iframe tags with safe attributes
                        echo wp_kses($map_embed, [
                            'iframe' => [
                                'src'             => true,
                                'width'           => true,
                                'height'          => true,
                                'style'           => true,
                                'frameborder'     => true,
                                'allowfullscreen' => true,
                                'loading'         => true,
                                'referrerpolicy'  => true,
                                'title'           => true,
                            ],
                        ]);
                        ?>
                    </div>
                <?php elseif ($map_image) : ?>
                    <!-- Static Image -->
                    <figure class="contact-map__figure">
                        <?php echo wp_get_attachment_image($map_image, 'large', false, [
                            'class'   => 'contact-map__image',
                            'loading' => 'lazy',
                        ]); ?>
                        <?php if ($maps_url) : ?>
                            <figcaption class="contact-map__caption">
                                <a
                                    href="<?php echo esc_url($maps_url); ?>"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    class="btn btn--outline"
                                >
                                    <?php esc_html_e('Ouvrir dans Google Maps', 'lemur'); ?>
                                </a>
                            </figcaption>
                        <?php endif; ?>
                    </figure>
                <?php else : ?>
                    <!-- Placeholder -->
                    <div class="contact-map__placeholder">
                        <?php lemur_the_ui_icon('location', ['width' => 48, 'height' => 48]); ?>
                        <p><?php esc_html_e('Carte à venir', 'lemur'); ?></p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Page Builder Sections -->
        <?php lemur_render_page_sections(); ?>
    </div>
</main>

<?php
// Schema.org LocalBusiness/SportsClub
lemur_render_contact_schema();

get_footer();
