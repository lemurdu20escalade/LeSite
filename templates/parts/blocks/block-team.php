<?php
/**
 * Block: Team
 *
 * Team members grid from CPT.
 *
 * @package Lemur
 */

declare(strict_types=1);

$data = lemur_get_block_data();

$title = $data['title'] ?? '';
$use_cpt = !empty($data['use_cpt']);
$selected_members = $data['members'] ?? [];

$block_id = 'team-' . lemur_get_block_index();

// Get members - single optimized query instead of N+1
$members = [];

if ($use_cpt && !empty($selected_members)) {
    $post_ids = array_filter(array_column($selected_members, 'id'));

    if (!empty($post_ids)) {
        $members = get_posts([
            'post_type' => 'membre',
            'post_status' => 'publish',
            'include' => $post_ids,
            'orderby' => 'post__in', // Preserve selection order
            'posts_per_page' => -1,
            'update_post_meta_cache' => true, // Pre-fetch meta
            'update_post_term_cache' => false, // Skip terms cache
        ]);
    }
}

if (empty($members)) {
    return;
}
?>

<section id="<?php echo esc_attr($block_id); ?>" class="block-team">
    <div class="block-team__container container">
        <?php if ($title) : ?>
            <h2 class="block-team__title"><?php echo esc_html($title); ?></h2>
        <?php endif; ?>

        <div class="block-team__grid">
            <?php foreach ($members as $member) : ?>
                <?php
                $thumbnail_id = get_post_thumbnail_id($member->ID);
                $role = get_post_meta($member->ID, 'membre_role', true);
                ?>
                <div class="block-team__member">
                    <?php if ($thumbnail_id) : ?>
                        <div class="block-team__photo-wrapper">
                            <?php
                            echo wp_get_attachment_image($thumbnail_id, 'medium', false, [
                                'class' => 'block-team__photo',
                                'loading' => 'lazy',
                            ]);
                            ?>
                        </div>
                    <?php endif; ?>

                    <h3 class="block-team__name"><?php echo esc_html($member->post_title); ?></h3>

                    <?php if ($role) : ?>
                        <p class="block-team__role"><?php echo esc_html($role); ?></p>
                    <?php endif; ?>

                    <?php if ($member->post_excerpt) : ?>
                        <p class="block-team__bio"><?php echo esc_html($member->post_excerpt); ?></p>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
