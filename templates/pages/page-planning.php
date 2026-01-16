<?php
/**
 * Template Name: Planning / Horaires
 *
 * Displays the schedule via embedded Google Sheet.
 *
 * @package Lemur
 */

declare(strict_types=1);

use Lemur\Fields\ThemeOptions;

get_header();

$sheet_url = lemur_get_option(ThemeOptions::FIELD_PLANNING_SHEET_URL);
$hours_note = lemur_get_hours_note();
?>

<main id="main-content" class="site-main">
    <div class="container">
        <header class="page-header">
            <h1 class="page-header__title"><?php the_title(); ?></h1>
            <?php if (has_excerpt()) : ?>
                <p class="page-header__description"><?php echo esc_html(get_the_excerpt()); ?></p>
            <?php endif; ?>
        </header>

        <section class="planning-section">
            <?php if ($sheet_url) : ?>
                <div class="planning-embed">
                    <iframe
                        src="<?php echo esc_url($sheet_url); ?>"
                        class="planning-embed__iframe"
                        title="<?php esc_attr_e('Planning des créneaux', 'lemur'); ?>"
                        loading="lazy"
                    ></iframe>
                </div>
            <?php else : ?>
                <div class="planning-placeholder">
                    <p><?php esc_html_e('Le planning sera bientôt disponible.', 'lemur'); ?></p>
                </div>
            <?php endif; ?>

            <?php if ($hours_note) : ?>
                <div class="planning-note">
                    <?php lemur_the_ui_icon('info', ['class' => 'planning-note__icon']); ?>
                    <p><?php echo esc_html($hours_note); ?></p>
                </div>
            <?php endif; ?>
        </section>

        <?php
        // Additional content via page builder
        if (lemur_has_page_sections()) {
            lemur_render_page_sections();
        }
        ?>
    </div>
</main>

<?php
get_footer();
