<?php
/**
 * Template Name: Les Collectifs
 * Template Post Type: page
 *
 * @package Lemur
 */

declare(strict_types=1);

use Lemur\CustomPostTypes\Collectives;

get_header();

$categories_order = ['Organisation', 'Formation', 'Activités', 'Communication', 'Logistique'];
?>

<main id="main-content" class="site-main site-main--flush">
    <!-- Hero minimal -->
    <header class="collectifs-hero">
        <div class="container">
            <h1 class="collectifs-hero__title">Les Collectifs</h1>
            <p class="collectifs-hero__lead">Le club fonctionne par collectifs. Groupes de travail bénévoles, ouverts à tous les adhérents.</p>
        </div>
    </header>

    <!-- Intro -->
    <section class="collectifs-intro">
        <div class="container">
            <div class="collectifs-intro__grid">
                <div class="collectifs-intro__text">
                    <h2>Comment ça marche</h2>
                    <p>Chaque collectif gère un aspect de la vie du club. Pour participer, inscrivez-vous sur la liste de diffusion du collectif qui vous intéresse.</p>
                </div>
                <div class="collectifs-intro__stats">
                    <div class="stat-item">
                        <span class="stat-item__number">15</span>
                        <span class="stat-item__label">collectifs</span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-item__number">5</span>
                        <span class="stat-item__label">domaines</span>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Navigation par catégorie -->
    <nav class="collectifs-nav">
        <div class="container">
            <ul class="collectifs-nav__list">
                <?php foreach ($categories_order as $cat_name) : ?>
                    <li>
                        <a href="#<?php echo esc_attr(sanitize_title($cat_name)); ?>" class="collectifs-nav__link">
                            <?php echo esc_html($cat_name); ?>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
    </nav>

    <!-- Collectifs par catégorie -->
    <section class="collectifs-list">
        <div class="container">
            <?php foreach ($categories_order as $cat_name) : ?>
                <?php
                $term = get_term_by('name', $cat_name, Collectives::TAXONOMY);
                if (!$term) continue;

                $collectifs = get_posts([
                    'post_type' => Collectives::POST_TYPE,
                    'posts_per_page' => -1,
                    'orderby' => 'title',
                    'order' => 'ASC',
                    'update_post_meta_cache' => true,
                    'tax_query' => [[
                        'taxonomy' => Collectives::TAXONOMY,
                        'field' => 'term_id',
                        'terms' => $term->term_id,
                    ]],
                ]);

                if (empty($collectifs)) continue;
                ?>
                <div id="<?php echo esc_attr(sanitize_title($cat_name)); ?>" class="collectifs-group">
                    <header class="collectifs-group__header">
                        <h2 class="collectifs-group__title"><?php echo esc_html($cat_name); ?></h2>
                        <span class="collectifs-group__count"><?php echo count($collectifs); ?></span>
                    </header>

                    <div class="collectifs-group__grid">
                        <?php foreach ($collectifs as $post) : setup_postdata($post); ?>
                            <?php
                            $id = get_the_ID();
                            $description = carbon_get_post_meta($id, Collectives::FIELD_DESCRIPTION);
                            $mailing = carbon_get_post_meta($id, Collectives::FIELD_MAILING_LIST);
                            $color = carbon_get_post_meta($id, Collectives::FIELD_COLOR);
                            ?>
                            <a href="<?php the_permalink(); ?>" class="collective-item" style="--collective-color: <?php echo esc_attr($color ?: '#64748b'); ?>">
                                <div class="collective-item__color"></div>
                                <div class="collective-item__content">
                                    <h3 class="collective-item__title"><?php the_title(); ?></h3>
                                    <?php if ($description) : ?>
                                        <p class="collective-item__desc"><?php echo esc_html(wp_trim_words(wp_strip_all_tags($description), 15)); ?></p>
                                    <?php endif; ?>
                                    <?php if ($mailing) : ?>
                                        <span class="collective-item__list"><?php echo esc_html($mailing); ?>@</span>
                                    <?php endif; ?>
                                </div>
                                <span class="collective-item__arrow">&rarr;</span>
                            </a>
                        <?php endforeach; wp_reset_postdata(); ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </section>

    <!-- CTA -->
    <section class="collectifs-cta">
        <div class="container">
            <p class="collectifs-cta__text">Envie de vous impliquer ?</p>
            <?php
            $contact_email = lemur_get_option(\Lemur\Fields\ThemeOptions::FIELD_EMAIL);
            if ($contact_email) {
                lemur_the_email($contact_email, 'Contactez le COOL →', ['class' => 'collectifs-cta__link']);
            }
            ?>
        </div>
    </section>
</main>

<?php
get_footer();
