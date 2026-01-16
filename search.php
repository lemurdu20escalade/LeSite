<?php
/**
 * Search Results Template
 *
 * Modern search results with filters and animations.
 *
 * @package Lemur
 */

declare(strict_types=1);

get_header();

$search_query = get_search_query();
$results_count = $wp_query->found_posts;
?>

<main id="main" class="site-main">
    <section class="search-page">
        <div class="container">
            <?php get_template_part('templates/parts/breadcrumb'); ?>

            <!-- Search header -->
            <header class="search-page__header">
                <div class="search-page__title-wrapper">
                    <h1 class="search-page__title">
                        <?php if ($search_query) : ?>
                            <?php esc_html_e('Résultats pour', 'lemur'); ?>
                            <span class="search-page__query"><?php echo esc_html($search_query); ?></span>
                        <?php else : ?>
                            <?php esc_html_e('Recherche', 'lemur'); ?>
                        <?php endif; ?>
                    </h1>
                    <?php if ($results_count > 0) : ?>
                        <p class="search-page__count">
                            <span class="search-page__count-number"><?php echo esc_html(number_format_i18n($results_count)); ?></span>
                            <?php echo esc_html(_n('résultat', 'résultats', $results_count, 'lemur')); ?>
                        </p>
                    <?php endif; ?>
                </div>

                <!-- Search form -->
                <form role="search" method="get" class="search-page__form" action="<?php echo esc_url(home_url('/')); ?>">
                    <div class="search-page__input-group">
                        <svg class="search-page__input-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <circle cx="11" cy="11" r="8"></circle>
                            <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                        </svg>
                        <input type="search"
                               class="search-page__input"
                               placeholder="<?php esc_attr_e('Rechercher...', 'lemur'); ?>"
                               value="<?php echo esc_attr($search_query); ?>"
                               name="s"
                               autocomplete="off">
                        <?php if ($search_query) : ?>
                            <button type="button" class="search-page__clear" onclick="this.previousElementSibling.value='';this.previousElementSibling.focus();" aria-label="<?php esc_attr_e('Effacer', 'lemur'); ?>">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <line x1="18" y1="6" x2="6" y2="18"></line>
                                    <line x1="6" y1="6" x2="18" y2="18"></line>
                                </svg>
                            </button>
                        <?php endif; ?>
                    </div>
                    <button type="submit" class="btn btn--primary">
                        <?php esc_html_e('Rechercher', 'lemur'); ?>
                    </button>
                </form>
            </header>

            <?php if (have_posts()) : ?>
                <!-- Results grid -->
                <div class="search-page__results">
                    <?php
                    $index = 0;
                    while (have_posts()) : the_post();
                        $post_type = get_post_type();
                        $post_type_obj = get_post_type_object($post_type);
                        $type_label = $post_type_obj ? $post_type_obj->labels->singular_name : '';
                        $index++;
                    ?>
                        <article class="search-card" style="--animation-order: <?php echo esc_attr((string) $index); ?>">
                            <?php if (has_post_thumbnail()) : ?>
                                <a href="<?php the_permalink(); ?>" class="search-card__image" aria-hidden="true" tabindex="-1">
                                    <?php the_post_thumbnail('lemur-card'); ?>
                                    <div class="search-card__image-overlay"></div>
                                </a>
                            <?php else : ?>
                                <a href="<?php the_permalink(); ?>" class="search-card__image search-card__image--placeholder" aria-hidden="true" tabindex="-1">
                                    <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1" aria-hidden="true">
                                        <?php if ($post_type === 'evenements') : ?>
                                            <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                                            <line x1="16" y1="2" x2="16" y2="6"></line>
                                            <line x1="8" y1="2" x2="8" y2="6"></line>
                                        <?php elseif ($post_type === 'faq') : ?>
                                            <circle cx="12" cy="12" r="10"></circle>
                                            <path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"></path>
                                            <line x1="12" y1="17" x2="12.01" y2="17"></line>
                                        <?php else : ?>
                                            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                                            <polyline points="14 2 14 8 20 8"></polyline>
                                        <?php endif; ?>
                                    </svg>
                                </a>
                            <?php endif; ?>

                            <div class="search-card__content">
                                <div class="search-card__meta">
                                    <?php if ($type_label) : ?>
                                        <span class="search-card__type"><?php echo esc_html($type_label); ?></span>
                                    <?php endif; ?>
                                    <time class="search-card__date" datetime="<?php echo esc_attr(get_the_date('c')); ?>">
                                        <?php echo esc_html(get_the_date('j M Y')); ?>
                                    </time>
                                </div>

                                <h2 class="search-card__title">
                                    <a href="<?php the_permalink(); ?>">
                                        <?php
                                        $title = get_the_title();
                                        if ($search_query) {
                                            $title = preg_replace(
                                                '/(' . preg_quote($search_query, '/') . ')/i',
                                                '<mark>$1</mark>',
                                                $title
                                            );
                                        }
                                        echo wp_kses($title, ['mark' => []]);
                                        ?>
                                    </a>
                                </h2>

                                <p class="search-card__excerpt">
                                    <?php
                                    $excerpt = wp_trim_words(get_the_excerpt(), 20, '...');
                                    if ($search_query) {
                                        $excerpt = preg_replace(
                                            '/(' . preg_quote($search_query, '/') . ')/i',
                                            '<mark>$1</mark>',
                                            $excerpt
                                        );
                                    }
                                    echo wp_kses($excerpt, ['mark' => []]);
                                    ?>
                                </p>

                                <a href="<?php the_permalink(); ?>" class="search-card__link">
                                    <?php esc_html_e('Voir plus', 'lemur'); ?>
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                        <line x1="5" y1="12" x2="19" y2="12"></line>
                                        <polyline points="12 5 19 12 12 19"></polyline>
                                    </svg>
                                </a>
                            </div>
                        </article>
                    <?php endwhile; ?>
                </div>

                <!-- Pagination -->
                <?php
                $pagination = paginate_links([
                    'type' => 'array',
                    'mid_size' => 1,
                    'prev_text' => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="15 18 9 12 15 6"></polyline></svg><span class="sr-only">' . esc_html__('Précédent', 'lemur') . '</span>',
                    'next_text' => '<span class="sr-only">' . esc_html__('Suivant', 'lemur') . '</span><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"></polyline></svg>',
                ]);

                if ($pagination) : ?>
                    <nav class="search-page__pagination" aria-label="<?php esc_attr_e('Pagination', 'lemur'); ?>">
                        <ul class="search-page__pagination-list">
                            <?php foreach ($pagination as $page_link) : ?>
                                <li><?php echo $page_link; ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </nav>
                <?php endif; ?>

            <?php else : ?>
                <!-- Empty state -->
                <div class="search-page__empty">
                    <div class="search-page__empty-icon" aria-hidden="true">
                        <svg viewBox="0 0 100 100">
                            <circle cx="40" cy="40" r="25" fill="none" stroke="currentColor" stroke-width="4" opacity="0.3"/>
                            <line x1="58" y1="58" x2="80" y2="80" stroke="currentColor" stroke-width="4" stroke-linecap="round" opacity="0.3"/>
                            <path d="M30 42 L38 50 L50 35" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" class="search-page__empty-x"/>
                        </svg>
                    </div>

                    <h2 class="search-page__empty-title">
                        <?php esc_html_e('Aucun résultat trouvé', 'lemur'); ?>
                    </h2>
                    <p class="search-page__empty-text">
                        <?php
                        printf(
                            /* translators: %s: search query */
                            esc_html__('Nous n\'avons rien trouvé pour « %s ». Essayez avec d\'autres termes.', 'lemur'),
                            '<strong>' . esc_html($search_query) . '</strong>'
                        );
                        ?>
                    </p>

                    <div class="search-page__empty-tips">
                        <h3><?php esc_html_e('Suggestions', 'lemur'); ?></h3>
                        <ul>
                            <li>
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                    <polyline points="20 6 9 17 4 12"></polyline>
                                </svg>
                                <?php esc_html_e('Vérifiez l\'orthographe', 'lemur'); ?>
                            </li>
                            <li>
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                    <polyline points="20 6 9 17 4 12"></polyline>
                                </svg>
                                <?php esc_html_e('Utilisez des termes plus généraux', 'lemur'); ?>
                            </li>
                            <li>
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                    <polyline points="20 6 9 17 4 12"></polyline>
                                </svg>
                                <?php esc_html_e('Essayez avec moins de mots-clés', 'lemur'); ?>
                            </li>
                        </ul>
                    </div>

                    <div class="search-page__empty-actions">
                        <a href="<?php echo esc_url(home_url('/')); ?>" class="btn btn--primary">
                            <?php esc_html_e('Retour à l\'accueil', 'lemur'); ?>
                        </a>
                        <?php
                        $contact_page = get_page_by_path('contact');
                        if ($contact_page) : ?>
                            <a href="<?php echo esc_url(get_permalink($contact_page)); ?>" class="btn btn--secondary">
                                <?php esc_html_e('Nous contacter', 'lemur'); ?>
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </section>
</main>

<?php
get_footer();
