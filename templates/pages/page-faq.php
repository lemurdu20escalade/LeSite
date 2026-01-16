<?php
/**
 * Template Name: FAQ
 *
 * Displays FAQ with search, category filters and accessible accordion.
 *
 * @package Lemur
 */

declare(strict_types=1);

get_header();

$categories = get_terms([
    'taxonomy'   => \Lemur\CustomPostTypes\FAQ::TAXONOMY,
    'hide_empty' => true,
]);

$faqs_grouped = lemur_get_faq_grouped_by_category();
$all_faqs = lemur_get_faq();
?>

<main id="main-content" class="site-main">
    <div class="container">
        <header class="page-header page-header--centered">
            <h1 class="page-header__title"><?php the_title(); ?></h1>
            <?php if (has_excerpt()) : ?>
                <p class="page-header__description"><?php echo esc_html(get_the_excerpt()); ?></p>
            <?php endif; ?>
        </header>

        <!-- Search FAQ -->
        <div class="faq-search" x-data="faqSearch()">
            <div class="faq-search__input-wrapper">
                <label for="faq-search-input" class="sr-only"><?php esc_html_e('Rechercher dans la FAQ', 'lemur'); ?></label>
                <?php lemur_the_ui_icon('info', ['class' => 'faq-search__icon']); ?>
                <input
                    type="search"
                    id="faq-search-input"
                    class="faq-search__input"
                    placeholder="<?php esc_attr_e('Rechercher une question...', 'lemur'); ?>"
                    x-model="searchQuery"
                    @input.debounce.300ms="filterFaqs()"
                >
            </div>

            <div class="faq-search__results" x-show="searchQuery.length > 2" x-cloak>
                <p x-show="filteredCount > 0" class="faq-search__count">
                    <span x-text="filteredCount"></span> <?php esc_html_e('résultat(s) trouvé(s)', 'lemur'); ?>
                </p>
                <p x-show="filteredCount === 0" class="faq-search__no-results">
                    <?php esc_html_e('Aucun résultat trouvé', 'lemur'); ?>
                </p>
            </div>
        </div>

        <!-- Category Filters -->
        <?php if (!empty($categories) && !is_wp_error($categories)) : ?>
            <nav class="faq-filters" aria-label="<?php esc_attr_e('Filtrer par catégorie', 'lemur'); ?>">
                <button
                    type="button"
                    class="faq-filter faq-filter--active"
                    data-filter="all"
                    aria-pressed="true"
                >
                    <?php esc_html_e('Toutes', 'lemur'); ?>
                </button>
                <?php foreach ($categories as $category) : ?>
                    <button
                        type="button"
                        class="faq-filter"
                        data-filter="<?php echo esc_attr($category->slug); ?>"
                        aria-pressed="false"
                    >
                        <?php echo esc_html($category->name); ?>
                        <span class="faq-filter__count">(<?php echo esc_html($category->count); ?>)</span>
                    </button>
                <?php endforeach; ?>
            </nav>
        <?php endif; ?>

        <!-- FAQ Content -->
        <div class="faq-content">
            <?php if (!empty($faqs_grouped)) : ?>
                <?php foreach ($faqs_grouped as $cat_slug => $cat_data) : ?>
                    <section
                        class="faq-category"
                        data-category="<?php echo esc_attr($cat_slug); ?>"
                    >
                        <h2 class="faq-category__title"><?php echo esc_html($cat_data['term']->name); ?></h2>

                        <div class="faq-list">
                            <?php foreach ($cat_data['questions'] as $faq) :
                                $faq_id = $faq->ID;
                                $question = get_the_title($faq);
                                $answer = lemur_get_faq_answer($faq_id);
                                $item_id = 'faq-' . $faq_id;
                            ?>
                                <article
                                    class="faq-item"
                                    data-faq-id="<?php echo esc_attr($faq_id); ?>"
                                    x-data="{ open: false }"
                                >
                                    <h3 class="faq-item__question">
                                        <button
                                            type="button"
                                            class="faq-item__toggle"
                                            :aria-expanded="open.toString()"
                                            aria-controls="<?php echo esc_attr($item_id); ?>-answer"
                                            @click="open = !open"
                                        >
                                            <span class="faq-item__question-text"><?php echo esc_html($question); ?></span>
                                            <span class="faq-item__icon" :class="{ 'faq-item__icon--open': open }" aria-hidden="true">
                                                <?php lemur_the_ui_icon('chevron-down'); ?>
                                            </span>
                                        </button>
                                    </h3>

                                    <div
                                        id="<?php echo esc_attr($item_id); ?>-answer"
                                        class="faq-item__answer"
                                        x-show="open"
                                        x-collapse
                                        x-cloak
                                    >
                                        <div class="faq-item__answer-content">
                                            <?php echo wp_kses_post($answer); ?>
                                        </div>
                                    </div>
                                </article>
                            <?php endforeach; ?>
                        </div>
                    </section>
                <?php endforeach; ?>
            <?php else : ?>
                <p class="faq-empty"><?php esc_html_e('Aucune question dans la FAQ pour le moment.', 'lemur'); ?></p>
            <?php endif; ?>
        </div>

        <!-- CTA Contact -->
        <section class="faq-cta">
            <h2 class="faq-cta__title"><?php esc_html_e('Vous n\'avez pas trouvé votre réponse ?', 'lemur'); ?></h2>
            <p class="faq-cta__text"><?php esc_html_e('N\'hésitez pas à nous contacter, nous vous répondrons rapidement.', 'lemur'); ?></p>
            <?php $contact_url = lemur_get_page_permalink('contact'); ?>
            <?php if ($contact_url) : ?>
                <a href="<?php echo esc_url($contact_url); ?>" class="btn btn--primary">
                    <?php esc_html_e('Nous contacter', 'lemur'); ?>
                </a>
            <?php endif; ?>
        </section>
    </div>
</main>

<?php
// Schema.org FAQPage
lemur_output_faq_schema($all_faqs);

get_footer();
