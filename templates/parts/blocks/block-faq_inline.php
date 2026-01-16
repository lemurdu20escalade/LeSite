<?php
/**
 * Block: FAQ Inline
 *
 * Accordion FAQ section.
 *
 * @package Lemur
 */

declare(strict_types=1);

$data = lemur_get_block_data();

$title = $data['title'] ?? '';
$use_cpt = !empty($data['use_cpt']);
$faq_items = $data['faq_items'] ?? [];
$custom_questions = $data['custom_questions'] ?? [];

$block_id = 'faq-' . lemur_get_block_index();

// Build questions array
$questions = [];

if ($use_cpt && !empty($faq_items)) {
    // Get questions from CPT - single optimized query
    $post_ids = array_filter(array_column($faq_items, 'id'));

    if (!empty($post_ids)) {
        $faq_posts = get_posts([
            'post_type' => 'faq',
            'post_status' => 'publish',
            'include' => $post_ids,
            'orderby' => 'post__in', // Preserve selection order
            'posts_per_page' => -1,
        ]);

        foreach ($faq_posts as $post) {
            $questions[] = [
                'question' => $post->post_title,
                'answer' => apply_filters('the_content', $post->post_content),
            ];
        }
    }
} elseif (!$use_cpt && !empty($custom_questions)) {
    // Use custom questions
    foreach ($custom_questions as $item) {
        $question = $item['question'] ?? '';
        $answer = $item['answer'] ?? '';

        if (empty($question)) {
            continue;
        }

        $questions[] = [
            'question' => $question,
            'answer' => wp_kses_post($answer),
        ];
    }
}

if (empty($questions)) {
    return;
}
?>

<section id="<?php echo esc_attr($block_id); ?>" class="block-faq">
    <div class="block-faq__container container">
        <?php if ($title) : ?>
            <h2 class="block-faq__title"><?php echo esc_html($title); ?></h2>
        <?php endif; ?>

        <div class="block-faq__list" x-data="{ openItem: null }">
            <?php foreach ($questions as $index => $item) : ?>
                <?php $item_id = "{$block_id}-item-{$index}"; ?>
                <div class="block-faq__item">
                    <button
                        type="button"
                        class="block-faq__question"
                        :class="{ 'is-open': openItem === <?php echo $index; ?> }"
                        @click="openItem = openItem === <?php echo $index; ?> ? null : <?php echo $index; ?>"
                        aria-expanded="false"
                        :aria-expanded="openItem === <?php echo $index; ?>"
                        aria-controls="<?php echo esc_attr($item_id); ?>"
                    >
                        <span class="block-faq__question-text"><?php echo esc_html($item['question']); ?></span>
                        <svg
                            class="block-faq__icon"
                            :class="{ 'is-rotated': openItem === <?php echo $index; ?> }"
                            width="24"
                            height="24"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2"
                            aria-hidden="true"
                        >
                            <polyline points="6 9 12 15 18 9"/>
                        </svg>
                    </button>

                    <div
                        id="<?php echo esc_attr($item_id); ?>"
                        class="block-faq__answer"
                        x-show="openItem === <?php echo $index; ?>"
                        x-cloak
                        x-transition:enter="faq-transition-enter"
                        x-transition:leave="faq-transition-leave"
                    >
                        <div class="block-faq__answer-content prose">
                            <?php
                            // Safe to echo: content is already sanitized via apply_filters('the_content')
                            // for CPT items, or wp_kses_post() for custom questions (see lines 40, 56)
                            echo $item['answer'];
                            ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
