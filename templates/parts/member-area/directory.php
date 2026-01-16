<?php
/**
 * Annuaire des membres
 *
 * RGPD compliant: displays first names only.
 *
 * @package Lemur
 */

declare(strict_types=1);

defined('ABSPATH') || exit;

use Lemur\Rest\MembersEndpoint;

// Get all members
$members = MembersEndpoint::getAllMembers();
$available_letters = MembersEndpoint::getAvailableLetters();

// Get current filter
$current_letter = isset($_GET['letter']) ? strtoupper(sanitize_text_field($_GET['letter'])) : '';
$search_query = isset($_GET['search']) ? sanitize_text_field($_GET['search']) : '';

// Filter members if needed
if (!empty($current_letter)) {
    $members = array_filter($members, function ($member) use ($current_letter) {
        return ($member['initial'] ?? '') === $current_letter;
    });
}

if (!empty($search_query)) {
    $members = array_filter($members, function ($member) use ($search_query) {
        return stripos($member['first_name'] ?? '', $search_query) !== false;
    });
}

// Group by letter
$grouped = [];
foreach ($members as $member) {
    $letter = $member['initial'] ?? '#';
    if (!isset($grouped[$letter])) {
        $grouped[$letter] = [];
    }
    $grouped[$letter][] = $member;
}
ksort($grouped);
?>

<div class="member-directory">
    <!-- Header -->
    <header class="directory__header">
        <h1 class="directory__title"><?php esc_html_e('Annuaire des membres', 'lemur'); ?></h1>
        <p class="directory__count">
            <?php
            printf(
                /* translators: %d: number of members */
                esc_html(_n('%d membre', '%d membres', count($members), 'lemur')),
                count($members)
            );
            ?>
        </p>
    </header>

    <!-- Navigation -->
    <?php lemur_render_member_nav('annuaire'); ?>

    <!-- Search & Filter -->
    <div class="directory__filters">
        <!-- Search -->
        <form method="get" class="directory__search" role="search">
            <label for="member-search" class="screen-reader-text">
                <?php esc_html_e('Rechercher un membre', 'lemur'); ?>
            </label>
            <input
                type="search"
                id="member-search"
                name="search"
                value="<?php echo esc_attr($search_query); ?>"
                placeholder="<?php esc_attr_e('Rechercher par prénom...', 'lemur'); ?>"
                class="directory__search-input"
            >
            <button type="submit" class="directory__search-btn">
                <span class="screen-reader-text"><?php esc_html_e('Rechercher', 'lemur'); ?></span>
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <circle cx="11" cy="11" r="8"/>
                    <line x1="21" y1="21" x2="16.65" y2="16.65"/>
                </svg>
            </button>
        </form>

        <!-- Alphabet Index -->
        <nav class="directory__alphabet" aria-label="<?php esc_attr_e('Index alphabétique', 'lemur'); ?>">
            <a href="<?php echo esc_url(remove_query_arg(['letter', 'search'])); ?>"
               class="directory__letter <?php echo empty($current_letter) ? 'directory__letter--active' : ''; ?>">
                <?php esc_html_e('Tous', 'lemur'); ?>
            </a>
            <?php foreach (range('A', 'Z') as $letter): ?>
                <?php
                $has_members = in_array($letter, $available_letters, true);
                $is_active = $current_letter === $letter;
                $class = 'directory__letter';
                if ($is_active) $class .= ' directory__letter--active';
                if (!$has_members) $class .= ' directory__letter--empty';
                ?>
                <a href="<?php echo $has_members ? esc_url(add_query_arg('letter', $letter)) : '#'; ?>"
                   class="<?php echo esc_attr($class); ?>"
                   <?php echo !$has_members ? 'aria-disabled="true"' : ''; ?>>
                    <?php echo esc_html($letter); ?>
                </a>
            <?php endforeach; ?>
        </nav>
    </div>

    <!-- Members List -->
    <?php if (!empty($grouped)): ?>
        <div class="directory__list">
            <?php foreach ($grouped as $letter => $letter_members): ?>
                <section class="directory__group" id="group-<?php echo esc_attr($letter); ?>">
                    <h2 class="directory__group-letter"><?php echo esc_html($letter); ?></h2>

                    <ul class="directory__members">
                        <?php foreach ($letter_members as $member): ?>
                            <li class="directory__member member-card">
                                <div class="member-card__avatar">
                                    <img src="<?php echo esc_url($member['avatar']); ?>"
                                         alt=""
                                         class="member-card__image"
                                         width="80"
                                         height="80"
                                         loading="lazy">
                                </div>

                                <div class="member-card__info">
                                    <span class="member-card__name">
                                        <?php echo esc_html($member['first_name']); ?>
                                    </span>

                                    <?php if (!empty($member['role'])): ?>
                                        <span class="member-card__role">
                                            <?php echo esc_html($member['role']); ?>
                                        </span>
                                    <?php endif; ?>

                                    <?php if (!empty($member['collectifs'])): ?>
                                        <span class="member-card__collectifs">
                                            <?php
                                            $collectif_names = array_map(function ($c) {
                                                return ucfirst(str_replace('collectif-', '', $c));
                                            }, $member['collectifs']);
                                            echo esc_html(implode(', ', $collectif_names));
                                            ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </section>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="directory__empty">
            <p><?php esc_html_e('Aucun membre trouvé.', 'lemur'); ?></p>
            <?php if (!empty($search_query) || !empty($current_letter)): ?>
                <a href="<?php echo esc_url(remove_query_arg(['letter', 'search'])); ?>" class="directory__reset">
                    <?php esc_html_e('Voir tous les membres', 'lemur'); ?>
                </a>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <!-- RGPD Notice -->
    <footer class="directory__footer">
        <p class="directory__privacy">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/>
                <path d="M7 11V7a5 5 0 0110 0v4"/>
            </svg>
            <?php esc_html_e('Conformément au RGPD, seuls les prénoms sont affichés. Les données ne sont accessibles qu\'aux membres du club.', 'lemur'); ?>
        </p>
    </footer>
</div>
