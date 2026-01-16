<?php
/**
 * Collective Card
 *
 * @package Lemur
 */

declare(strict_types=1);

use Lemur\CustomPostTypes\Collectives;

$collective_id = get_the_ID();
$description = carbon_get_post_meta($collective_id, Collectives::FIELD_DESCRIPTION);
$mailing_list = carbon_get_post_meta($collective_id, Collectives::FIELD_MAILING_LIST);
$color = carbon_get_post_meta($collective_id, Collectives::FIELD_COLOR);
$categories = get_the_terms($collective_id, Collectives::TAXONOMY);

$border_style = $color ? "border-top-color: {$color};" : '';
?>

<article class="collective-card" <?php echo $border_style ? 'style="' . esc_attr($border_style) . '"' : ''; ?>>
    <div class="collective-card__content">
        <header class="collective-card__header">
            <h3 class="collective-card__title">
                <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
            </h3>

            <?php if ($categories && !is_wp_error($categories)) : ?>
                <span class="collective-card__category">
                    <?php echo esc_html($categories[0]->name); ?>
                </span>
            <?php endif; ?>
        </header>

        <?php if ($description) : ?>
            <p class="collective-card__description">
                <?php echo esc_html(wp_trim_words(wp_strip_all_tags($description), 20)); ?>
            </p>
        <?php endif; ?>

        <footer class="collective-card__footer">
            <?php if ($mailing_list) : ?>
                <span class="collective-card__list"><?php echo esc_html($mailing_list); ?></span>
            <?php endif; ?>

            <a href="<?php the_permalink(); ?>" class="collective-card__link">
                <?php esc_html_e('En savoir plus', 'lemur'); ?> &rarr;
            </a>
        </footer>
    </div>
</article>
