<?php
/**
 * 404 Not Found Template
 *
 * A distinctive error page for Lemur Escalade climbing gym.
 * Features an asymmetric layout with climbing-inspired visual metaphor.
 *
 * @package Lemur
 */

declare(strict_types=1);

get_header();
?>

<main id="main" class="site-main" role="main">
    <section class="error-404" aria-labelledby="error-title">
        <!-- Decorative climbing holds scattered in background -->
        <div class="error-404__holds" aria-hidden="true">
            <span class="error-404__hold error-404__hold--1"></span>
            <span class="error-404__hold error-404__hold--2"></span>
            <span class="error-404__hold error-404__hold--3"></span>
            <span class="error-404__hold error-404__hold--4"></span>
            <span class="error-404__hold error-404__hold--5"></span>
        </div>

        <div class="container">
            <div class="error-404__grid">
                <!-- Left: Bold typographic statement -->
                <div class="error-404__statement">
                    <span class="error-404__code" aria-hidden="true">404</span>
                    <div class="error-404__line" aria-hidden="true"></div>
                </div>

                <!-- Right: Message and action -->
                <article class="error-404__content">
                    <p class="error-404__eyebrow"><?php esc_html_e('Impasse', 'lemur'); ?></p>
                    <h1 id="error-title" class="error-404__title">
                        <?php esc_html_e('Cette voie ne mene nulle part.', 'lemur'); ?>
                    </h1>
                    <p class="error-404__description">
                        <?php esc_html_e('La page que vous cherchez a ete deplacee ou n\'existe plus. Redescendez et trouvez une nouvelle voie.', 'lemur'); ?>
                    </p>
                    <nav class="error-404__nav" aria-label="<?php esc_attr_e('Navigation de secours', 'lemur'); ?>">
                        <a href="<?php echo esc_url(home_url('/')); ?>" class="error-404__cta">
                            <span class="error-404__cta-text"><?php esc_html_e('Retour a l\'accueil', 'lemur'); ?></span>
                            <span class="error-404__cta-icon" aria-hidden="true">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M5 12h14"/>
                                    <path d="m12 5 7 7-7 7"/>
                                </svg>
                            </span>
                        </a>
                    </nav>
                </article>
            </div>
        </div>

        <!-- Vertical accent line -->
        <div class="error-404__accent" aria-hidden="true"></div>
    </section>
</main>

<?php
get_footer();
