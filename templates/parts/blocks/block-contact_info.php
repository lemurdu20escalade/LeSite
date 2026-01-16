<?php
/**
 * Block: Contact Info
 *
 * Contact information from theme options.
 *
 * @package Lemur
 */

declare(strict_types=1);

$data = lemur_get_block_data();

$title = $data['title'] ?? __('Contact', 'lemur');
$show_map = !empty($data['show_map']);
$show_hours = !empty($data['show_hours']);
$show_transport = !empty($data['show_transport']);
$custom_content = $data['custom_content'] ?? '';

$block_id = 'contact-' . lemur_get_block_index();

// Get contact info from theme options
$address = lemur_get_contact('address');
$phone = lemur_get_contact('phone');
$email = lemur_get_contact('email');
$maps_url = lemur_get_contact('maps_url');
$hours = lemur_get_option(\Lemur\Fields\ThemeOptions::FIELD_OPENING_HOURS);
$transport_lines = lemur_get_transport_lines();
?>

<section id="<?php echo esc_attr($block_id); ?>" class="block-contact">
    <div class="block-contact__container container">
        <?php if ($title) : ?>
            <h2 class="block-contact__title"><?php echo esc_html($title); ?></h2>
        <?php endif; ?>

        <div class="block-contact__grid">
            <div class="block-contact__info">
                <?php if ($address) : ?>
                    <div class="block-contact__item">
                        <svg class="block-contact__icon" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/>
                            <circle cx="12" cy="10" r="3"/>
                        </svg>
                        <div class="block-contact__item-content">
                            <h3 class="block-contact__item-title"><?php esc_html_e('Adresse', 'lemur'); ?></h3>
                            <?php if ($maps_url) : ?>
                                <a href="<?php echo esc_url($maps_url); ?>" target="_blank" rel="noopener noreferrer">
                                    <?php echo nl2br(esc_html($address)); ?>
                                </a>
                            <?php else : ?>
                                <p><?php echo nl2br(esc_html($address)); ?></p>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if ($phone) : ?>
                    <div class="block-contact__item">
                        <svg class="block-contact__icon" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/>
                        </svg>
                        <div class="block-contact__item-content">
                            <h3 class="block-contact__item-title"><?php esc_html_e('Téléphone', 'lemur'); ?></h3>
                            <a href="tel:<?php echo esc_attr(lemur_format_phone_link($phone)); ?>">
                                <?php echo esc_html($phone); ?>
                            </a>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if ($email) : ?>
                    <div class="block-contact__item">
                        <svg class="block-contact__icon" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/>
                            <polyline points="22,6 12,13 2,6"/>
                        </svg>
                        <div class="block-contact__item-content">
                            <h3 class="block-contact__item-title"><?php esc_html_e('Email', 'lemur'); ?></h3>
                            <?php lemur_the_email($email, null, ['inline' => true]); ?>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if ($show_transport && !empty($transport_lines)) : ?>
                    <div class="block-contact__item block-contact__item--transport">
                        <svg class="block-contact__icon" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <rect x="3" y="3" width="18" height="18" rx="2"/>
                            <path d="M3 9h18M9 21V9"/>
                        </svg>
                        <div class="block-contact__item-content">
                            <h3 class="block-contact__item-title"><?php esc_html_e('Transports', 'lemur'); ?></h3>
                            <div class="transport-lines">
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
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <?php if ($show_hours && $hours) : ?>
                <div class="block-contact__hours">
                    <h3 class="block-contact__hours-title"><?php esc_html_e('Horaires', 'lemur'); ?></h3>
                    <div class="block-contact__hours-content prose">
                        <?php echo wp_kses_post($hours); ?>
                    </div>
                </div>
            <?php endif; ?>

            <?php if ($custom_content) : ?>
                <div class="block-contact__custom prose">
                    <?php echo wp_kses_post($custom_content); ?>
                </div>
            <?php endif; ?>
        </div>

        <?php if ($show_map && $maps_url) : ?>
            <div class="block-contact__map">
                <a
                    href="<?php echo esc_url($maps_url); ?>"
                    target="_blank"
                    rel="noopener noreferrer"
                    class="btn btn--outline"
                >
                    <?php esc_html_e('Voir sur la carte', 'lemur'); ?>
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/>
                        <polyline points="15 3 21 3 21 9"/>
                        <line x1="10" y1="14" x2="21" y2="3"/>
                    </svg>
                </a>
            </div>
        <?php endif; ?>
    </div>
</section>
