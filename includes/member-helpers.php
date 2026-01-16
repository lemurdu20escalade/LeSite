<?php
/**
 * Member Area Helper Functions
 *
 * Global helper functions for the member area.
 *
 * @package Lemur
 */

declare(strict_types=1);

use Lemur\MemberArea\Access\Capabilities;
use Lemur\MemberArea\Access\RolesManager;
use Lemur\MemberArea\Access\AccessControl;
use Lemur\MemberArea\Auth\BackupAuth;

/**
 * Check if current user is a Lemur member
 *
 * @param int|null $user_id User ID or null for current user
 */
function is_lemur_member(?int $user_id = null): bool
{
    return Capabilities::canAccessMemberArea($user_id);
}

/**
 * Check if current user is bureau member
 *
 * @param int|null $user_id User ID or null for current user
 */
function is_lemur_bureau(?int $user_id = null): bool
{
    return Capabilities::isBureau($user_id);
}

/**
 * Check if user is in a specific collectif
 *
 * @param string   $collectif Collectif name
 * @param int|null $user_id   User ID or null for current user
 */
function lemur_user_in_collectif(string $collectif, ?int $user_id = null): bool
{
    return RolesManager::userInCollectif($collectif, $user_id);
}

/**
 * Get user's collectifs
 *
 * @param int|null $user_id User ID or null for current user
 * @return array<string>
 */
function lemur_user_collectifs(?int $user_id = null): array
{
    return RolesManager::getUserCollectifs($user_id);
}

/**
 * Get OAuth login URL
 */
function lemur_get_oauth_login_url(): string
{
    // If OAuth2 plugin is configured, return its login URL
    // For now, return the standard login URL
    return AccessControl::getLoginUrl();
}

/**
 * Get member area login URL with redirect
 *
 * @param string|null $redirect_to URL to redirect to after login
 */
function lemur_get_member_login_url(?string $redirect_to = null): string
{
    $login_url = AccessControl::getLoginUrl();

    if ($redirect_to !== null) {
        $login_url = add_query_arg('redirect_to', urlencode($redirect_to), $login_url);
    }

    return $login_url;
}

/**
 * Get member logout URL
 *
 * @param string|null $redirect_to URL to redirect to after logout
 */
function lemur_get_member_logout_url(?string $redirect_to = null): string
{
    return wp_logout_url($redirect_to ?? home_url('/'));
}

/**
 * Check if current authentication mode allows WordPress login
 */
function lemur_allows_wp_login(): bool
{
    return BackupAuth::isBackupModeActive();
}

/**
 * Check if OAuth2 authentication is enabled
 */
function lemur_oauth2_enabled(): bool
{
    return BackupAuth::isOAuth2ModeActive();
}

/**
 * Get current user's display name for member area
 *
 * @param int|null $user_id User ID or null for current user
 */
function lemur_get_member_name(?int $user_id = null): string
{
    $user_id = $user_id ?? get_current_user_id();

    if ($user_id === 0) {
        return '';
    }

    $user = get_user_by('id', $user_id);

    if (!$user instanceof WP_User) {
        return '';
    }

    // Prefer first name for personalization
    $first_name = get_user_meta($user_id, 'first_name', true);

    if (!empty($first_name)) {
        return sanitize_text_field($first_name);
    }

    return sanitize_text_field($user->display_name);
}

/**
 * Get current member's role label
 *
 * @param int|null $user_id User ID or null for current user
 */
function lemur_get_member_role_label(?int $user_id = null): string
{
    $user_id = $user_id ?? get_current_user_id();

    if ($user_id === 0) {
        return '';
    }

    if (Capabilities::isBureau($user_id)) {
        return __('Bureau', 'lemur');
    }

    if (Capabilities::canAccessMemberArea($user_id)) {
        return __('Membre', 'lemur');
    }

    return '';
}

/**
 * Check if member area page should be shown in menu
 */
function lemur_show_member_menu(): bool
{
    // Always show for logged in members
    if (is_lemur_member()) {
        return true;
    }

    // Show login link for non-logged users
    return true;
}

/**
 * Get member area menu items
 *
 * @return array<array{title: string, url: string, icon: string, slug: string, capability: string}>
 */
function lemur_get_member_menu_items(): array
{
    $items = [
        [
            'title'      => __('Tableau de bord', 'lemur'),
            'url'        => home_url('/espace-membre/'),
            'slug'       => 'espace-membre',
            'icon'       => 'dashboard',
            'capability' => Capabilities::CAP_READ_MEMBER_AREA,
        ],
        [
            'title'      => __('Documents', 'lemur'),
            'url'        => home_url('/espace-membre/documents/'),
            'slug'       => 'documents',
            'icon'       => 'file',
            'capability' => Capabilities::CAP_READ_MEMBER_AREA,
        ],
        [
            'title'      => __('Tâches', 'lemur'),
            'url'        => home_url('/espace-membre/todo-list/'),
            'slug'       => 'todo-list',
            'icon'       => 'check',
            'capability' => Capabilities::CAP_READ_MEMBER_AREA,
        ],
        [
            'title'      => __('Calendrier', 'lemur'),
            'url'        => home_url('/espace-membre/calendrier-membres/'),
            'slug'       => 'calendrier-membres',
            'icon'       => 'calendar',
            'capability' => Capabilities::CAP_READ_MEMBER_AREA,
        ],
        [
            'title'      => __('Annuaire', 'lemur'),
            'url'        => home_url('/espace-membre/annuaire/'),
            'slug'       => 'annuaire',
            'icon'       => 'users',
            'capability' => Capabilities::CAP_READ_MEMBER_AREA,
        ],
    ];

    // Filter by capability
    return array_filter($items, function ($item) {
        return current_user_can($item['capability']);
    });
}

/**
 * Render member area navigation
 *
 * @param string $current_page Current page slug for active state
 */
function lemur_render_member_nav(string $current_page = ''): void
{
    $items = lemur_get_member_menu_items();

    if (empty($items)) {
        return;
    }

    echo '<nav class="member-nav" aria-label="' . esc_attr__('Navigation espace membre', 'lemur') . '">';
    echo '<ul class="member-nav__list">';

    foreach ($items as $item) {
        $is_active = !empty($current_page) && ($item['slug'] ?? '') === $current_page;
        $class = 'member-nav__item' . ($is_active ? ' member-nav__item--active' : '');

        printf(
            '<li class="%s"><a href="%s" class="member-nav__link">%s<span>%s</span></a></li>',
            esc_attr($class),
            esc_url($item['url']),
            lemur_ui_icon($item['icon']),
            esc_html($item['title'])
        );
    }

    echo '</ul>';
    echo '</nav>';
}

/**
 * Get upcoming tasks for current user
 *
 * @param int      $limit   Number of tasks to return
 * @param int|null $user_id User ID or null for current user
 * @return array<WP_Post>
 */
function lemur_get_upcoming_tasks(int $limit = 5, ?int $user_id = null): array
{
    if (!class_exists('Lemur\CustomPostTypes\Tasks')) {
        return [];
    }

    $user_id = $user_id ?? get_current_user_id();

    $args = [
        'post_type'      => \Lemur\CustomPostTypes\Tasks::POST_TYPE,
        'posts_per_page' => $limit,
        'meta_query'     => [
            'relation' => 'AND',
            [
                'key'     => '_' . \Lemur\CustomPostTypes\Tasks::FIELD_STATUS,
                'value'   => \Lemur\CustomPostTypes\Tasks::STATUS_DONE,
                'compare' => '!=',
            ],
        ],
        'orderby'        => 'meta_value',
        'meta_key'       => '_' . \Lemur\CustomPostTypes\Tasks::FIELD_DUE_DATE,
        'order'          => 'ASC',
    ];

    return get_posts($args);
}

/**
 * Get recent documents
 *
 * @param int $limit Number of documents to return
 * @return array<WP_Post>
 */
function lemur_get_recent_documents(int $limit = 5): array
{
    if (!class_exists('Lemur\CustomPostTypes\Documents')) {
        return [];
    }

    return get_posts([
        'post_type'      => \Lemur\CustomPostTypes\Documents::POST_TYPE,
        'posts_per_page' => $limit,
        'orderby'        => 'date',
        'order'          => 'DESC',
    ]);
}

/**
 * Format task due date for display
 *
 * @param int $task_id Task post ID
 */
function lemur_format_task_due_date(int $task_id): string
{
    if (!class_exists('Lemur\CustomPostTypes\Tasks')) {
        return '';
    }

    $due_date = carbon_get_post_meta($task_id, \Lemur\CustomPostTypes\Tasks::FIELD_DUE_DATE);

    if (empty($due_date)) {
        return __('Sans échéance', 'lemur');
    }

    $timestamp = strtotime($due_date);

    if ($timestamp === false) {
        return '';
    }

    $now = time();
    $diff_days = (int) floor(($timestamp - $now) / DAY_IN_SECONDS);

    if ($diff_days < 0) {
        return sprintf(
            /* translators: %s: number of days */
            _n('Il y a %s jour', 'Il y a %s jours', abs($diff_days), 'lemur'),
            number_format_i18n(abs($diff_days))
        );
    }

    if ($diff_days === 0) {
        return __('Aujourd\'hui', 'lemur');
    }

    if ($diff_days === 1) {
        return __('Demain', 'lemur');
    }

    if ($diff_days <= 7) {
        return sprintf(
            /* translators: %s: number of days */
            _n('Dans %s jour', 'Dans %s jours', $diff_days, 'lemur'),
            number_format_i18n($diff_days)
        );
    }

    return date_i18n(get_option('date_format'), $timestamp);
}

/**
 * Get events for a specific month (for calendar)
 *
 * @param int $month Month (1-12)
 * @param int $year  Year
 * @return array<string, array<WP_Post>> Events indexed by date (Y-m-d)
 */
function lemur_get_events_for_month(int $month, int $year): array
{
    $start_date = sprintf('%04d-%02d-01', $year, $month);
    $end_date = date('Y-m-t', strtotime($start_date));

    $events = get_posts([
        'post_type'      => \Lemur\CustomPostTypes\Events::POST_TYPE,
        'posts_per_page' => -1,
        'meta_query'     => [
            [
                'key'     => '_' . \Lemur\CustomPostTypes\Events::FIELD_DATE_START,
                'value'   => [$start_date, $end_date],
                'compare' => 'BETWEEN',
                'type'    => 'DATE',
            ],
        ],
    ]);

    $indexed = [];

    foreach ($events as $event) {
        $date = carbon_get_post_meta($event->ID, \Lemur\CustomPostTypes\Events::FIELD_DATE_START);

        if (!empty($date)) {
            $date_key = date('Y-m-d', strtotime($date));

            if (!isset($indexed[$date_key])) {
                $indexed[$date_key] = [];
            }

            $indexed[$date_key][] = $event;
        }
    }

    return $indexed;
}

/**
 * Get tasks for a specific month (for calendar)
 *
 * @param int $month Month (1-12)
 * @param int $year  Year
 * @return array<string, array<WP_Post>> Tasks indexed by date (Y-m-d)
 */
function lemur_get_tasks_for_month(int $month, int $year): array
{
    if (!class_exists('Lemur\CustomPostTypes\Tasks')) {
        return [];
    }

    $start_date = sprintf('%04d-%02d-01', $year, $month);
    $end_date = date('Y-m-t', strtotime($start_date));

    $tasks = get_posts([
        'post_type'      => \Lemur\CustomPostTypes\Tasks::POST_TYPE,
        'posts_per_page' => -1,
        'meta_query'     => [
            [
                'key'     => '_' . \Lemur\CustomPostTypes\Tasks::FIELD_DUE_DATE,
                'value'   => [$start_date, $end_date],
                'compare' => 'BETWEEN',
                'type'    => 'DATE',
            ],
        ],
    ]);

    $indexed = [];

    foreach ($tasks as $task) {
        $date = carbon_get_post_meta($task->ID, \Lemur\CustomPostTypes\Tasks::FIELD_DUE_DATE);

        if (!empty($date)) {
            $date_key = date('Y-m-d', strtotime($date));

            if (!isset($indexed[$date_key])) {
                $indexed[$date_key] = [];
            }

            $indexed[$date_key][] = $task;
        }
    }

    return $indexed;
}

/**
 * Get calendar data for a month (events + tasks combined)
 *
 * @param int $month Month (1-12)
 * @param int $year  Year
 * @return array<string, array{events: array<WP_Post>, tasks: array<WP_Post>}>
 */
function lemur_get_calendar_data(int $month, int $year): array
{
    $events = lemur_get_events_for_month($month, $year);
    $tasks = lemur_get_tasks_for_month($month, $year);

    // Merge into single structure
    $all_dates = array_unique(array_merge(array_keys($events), array_keys($tasks)));
    sort($all_dates);

    $data = [];

    foreach ($all_dates as $date) {
        $data[$date] = [
            'events' => $events[$date] ?? [],
            'tasks'  => $tasks[$date] ?? [],
        ];
    }

    return $data;
}

/**
 * Check if we're on a member area page
 */
function is_member_area_page(): bool
{
    return AccessControl::isProtectedPage();
}

/**
 * Get document download URL
 *
 * @param int $document_id Document post ID
 */
function lemur_get_document_download_url(int $document_id): string
{
    return rest_url('lemur/v1/download/' . $document_id);
}

/**
 * Format document file size for display
 *
 * @param int $document_id Document post ID
 */
function lemur_format_document_size(int $document_id): string
{
    if (!class_exists('Lemur\CustomPostTypes\Documents')) {
        return '';
    }

    $file_id = carbon_get_post_meta($document_id, \Lemur\CustomPostTypes\Documents::FIELD_FILE);

    if (empty($file_id)) {
        return '';
    }

    $file_path = get_attached_file((int) $file_id);

    if (!$file_path || !file_exists($file_path)) {
        return '';
    }

    return size_format(filesize($file_path));
}
