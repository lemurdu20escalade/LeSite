<?php
/**
 * Section: Grimper / Where to climb
 *
 * Displays climbing schedules for main gym and partner gyms.
 *
 * @package Lemur
 */

declare(strict_types=1);

$title = carbon_get_theme_option('home_grimper_title') ?: __('Où grimper ?', 'lemur');
$intro = carbon_get_theme_option('home_grimper_intro');
$main_gym_data = carbon_get_theme_option('home_grimper_main_gym') ?: [];
$main_gym = !empty($main_gym_data) ? $main_gym_data[0] : null;
$other_gyms = carbon_get_theme_option('home_grimper_other_gyms') ?: [];
$grimper_link = lemur_get_page_permalink('grimper');

// Skip section if no content
if (empty($main_gym) && empty($other_gyms)) {
    return;
}
?>

<section class="section section--grimper section--alt" aria-labelledby="grimper-title">
    <div class="container">
        <header class="section__header">
            <div class="section__header-content">
                <h2 id="grimper-title" class="section__title"><?php echo esc_html($title); ?></h2>
                <?php if ($intro) : ?>
                    <p class="section__intro"><?php echo esc_html($intro); ?></p>
                <?php endif; ?>
            </div>
            <?php if ($grimper_link) : ?>
                <a href="<?php echo esc_url($grimper_link); ?>" class="section__more">
                    <?php esc_html_e('Tous les créneaux', 'lemur'); ?>
                    <?php lemur_the_ui_icon('arrow-right'); ?>
                </a>
            <?php endif; ?>
        </header>

        <div class="grimper-grid">
            <?php if ($main_gym) : ?>
                <div class="gym-card gym-card--main">
                    <h3 class="gym-card__title">
                        <?php echo esc_html($main_gym['name'] ?? ''); ?>
                        <span class="gym-card__badge"><?php esc_html_e('Salle principale', 'lemur'); ?></span>
                    </h3>

                    <?php if (!empty($main_gym['schedule'])) : ?>
                        <table class="gym-card__schedule">
                            <thead>
                                <tr>
                                    <th scope="col"><?php esc_html_e('Jour', 'lemur'); ?></th>
                                    <th scope="col"><?php esc_html_e('Horaire', 'lemur'); ?></th>
                                    <th scope="col"><?php esc_html_e('Public', 'lemur'); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($main_gym['schedule'] as $slot) : ?>
                                    <tr>
                                        <td><?php echo esc_html($slot['day'] ?? ''); ?></td>
                                        <td><?php echo esc_html($slot['time'] ?? ''); ?></td>
                                        <td><?php echo esc_html($slot['audience'] ?? ''); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>

                    <?php if (!empty($main_gym['address'])) : ?>
                        <p class="gym-card__address">
                            <span class="gym-card__address-icon" aria-hidden="true">
                                <?php lemur_the_ui_icon('location', ['width' => 16, 'height' => 16]); ?>
                            </span>
                            <?php echo esc_html($main_gym['address']); ?>
                        </p>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($other_gyms)) : ?>
                <div class="gym-card gym-card--others">
                    <h3 class="gym-card__title"><?php esc_html_e('Salles partenaires', 'lemur'); ?></h3>

                    <ul class="gym-list">
                        <?php foreach ($other_gyms as $gym) : ?>
                            <li class="gym-list__item">
                                <strong><?php echo esc_html($gym['name'] ?? ''); ?></strong>
                                <?php if (!empty($gym['info'])) : ?>
                                    <span class="gym-list__info"><?php echo esc_html($gym['info']); ?></span>
                                <?php endif; ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>
