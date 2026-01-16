<?php
/**
 * Kanban Board - Tasks
 *
 * Drag and drop task management for bureau members.
 * Read-only for standard members.
 *
 * @package Lemur
 */

declare(strict_types=1);

defined('ABSPATH') || exit;

use Lemur\CustomPostTypes\Tasks;
use Lemur\MemberArea\Access\Capabilities;

$can_edit = Capabilities::canEditTodos();
$current_season = Tasks::getCurrentSeason();

// Get season from query or use current
$season = isset($_GET['season']) ? sanitize_text_field($_GET['season']) : $current_season;

// Get tasks grouped by status
$tasks_by_status = Tasks::getTasksByStatus($season);

$columns = [
    Tasks::STATUS_TODO => [
        'title' => __('À faire', 'lemur'),
        'color' => '#6c757d',
    ],
    Tasks::STATUS_IN_PROGRESS => [
        'title' => __('En cours', 'lemur'),
        'color' => '#0d6efd',
    ],
    Tasks::STATUS_DONE => [
        'title' => __('Terminé', 'lemur'),
        'color' => '#198754',
    ],
];

$priority_labels = [
    Tasks::PRIORITY_LOW => __('Basse', 'lemur'),
    Tasks::PRIORITY_MEDIUM => __('Moyenne', 'lemur'),
    Tasks::PRIORITY_HIGH => __('Haute', 'lemur'),
];
?>

<div class="member-kanban" data-can-edit="<?php echo $can_edit ? 'true' : 'false'; ?>">
    <!-- Header -->
    <header class="kanban__header">
        <div class="kanban__title-group">
            <h1 class="kanban__title"><?php esc_html_e('Tâches du club', 'lemur'); ?></h1>
            <span class="kanban__season"><?php echo esc_html($season); ?></span>
        </div>

        <?php if ($can_edit): ?>
            <a href="<?php echo esc_url(admin_url('post-new.php?post_type=' . Tasks::POST_TYPE)); ?>"
               class="kanban__add-btn">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <line x1="12" y1="5" x2="12" y2="19"/>
                    <line x1="5" y1="12" x2="19" y2="12"/>
                </svg>
                <?php esc_html_e('Nouvelle tâche', 'lemur'); ?>
            </a>
        <?php endif; ?>
    </header>

    <!-- Navigation -->
    <?php lemur_render_member_nav('todo-list'); ?>

    <!-- Season filter -->
    <div class="kanban__filters">
        <label for="season-select" class="kanban__filter-label">
            <?php esc_html_e('Saison :', 'lemur'); ?>
        </label>
        <select id="season-select" class="kanban__filter-select" onchange="window.location.href=this.value">
            <?php
            // Generate last 3 seasons
            $current_year = (int) date('Y');
            $current_month = (int) date('m');
            $start_year = $current_month >= 9 ? $current_year : $current_year - 1;

            for ($i = 0; $i < 3; $i++):
                $s_year = $start_year - $i;
                $s_label = $s_year . '-' . ($s_year + 1);
                $s_url = add_query_arg('season', $s_label);
            ?>
                <option value="<?php echo esc_url($s_url); ?>" <?php selected($season, $s_label); ?>>
                    <?php echo esc_html($s_label); ?>
                </option>
            <?php endfor; ?>
        </select>

        <?php if (!$can_edit): ?>
            <p class="kanban__readonly-notice">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/>
                    <path d="M7 11V7a5 5 0 0110 0v4"/>
                </svg>
                <?php esc_html_e('Mode lecture seule', 'lemur'); ?>
            </p>
        <?php endif; ?>
    </div>

    <!-- Kanban Board -->
    <div class="kanban__board">
        <?php foreach ($columns as $status => $column): ?>
            <div class="kanban__column" data-status="<?php echo esc_attr($status); ?>">
                <header class="kanban__column-header" style="--column-color: <?php echo esc_attr($column['color']); ?>">
                    <h2 class="kanban__column-title"><?php echo esc_html($column['title']); ?></h2>
                    <span class="kanban__column-count">
                        <?php echo count($tasks_by_status[$status] ?? []); ?>
                    </span>
                </header>

                <div class="kanban__items" data-status="<?php echo esc_attr($status); ?>">
                    <?php
                    $tasks = $tasks_by_status[$status] ?? [];
                    foreach ($tasks as $task):
                        $priority = carbon_get_post_meta($task->ID, Tasks::FIELD_PRIORITY) ?: Tasks::PRIORITY_MEDIUM;
                        $due_date = carbon_get_post_meta($task->ID, Tasks::FIELD_DUE_DATE);
                        $assigned = carbon_get_post_meta($task->ID, Tasks::FIELD_ASSIGNED_TO);
                        $checklist = carbon_get_post_meta($task->ID, Tasks::FIELD_CHECKLIST);
                        $is_recurring = carbon_get_post_meta($task->ID, Tasks::FIELD_IS_RECURRING) === 'yes';

                        $is_overdue = $due_date && strtotime($due_date) < time() && $status !== Tasks::STATUS_DONE;

                        $checklist_done = 0;
                        $checklist_total = 0;
                        $checklist_json = [];
                        if (!empty($checklist) && is_array($checklist)) {
                            $checklist_total = count($checklist);
                            foreach ($checklist as $index => $item) {
                                if (!empty($item['done'])) {
                                    $checklist_done++;
                                }
                                $checklist_json[] = [
                                    'index' => $index,
                                    'item' => $item['item'] ?? '',
                                    'done' => !empty($item['done']),
                                ];
                            }
                        }

                        // Prepare assigned users data
                        $assigned_users = [];
                        if (!empty($assigned) && is_array($assigned)) {
                            foreach ($assigned as $assignment) {
                                if (isset($assignment['id'])) {
                                    $user = get_user_by('id', $assignment['id']);
                                    if ($user) {
                                        $assigned_users[] = [
                                            'id' => $user->ID,
                                            'name' => $user->display_name,
                                            'avatar' => get_avatar_url($user->ID, ['size' => 32]),
                                        ];
                                    }
                                }
                            }
                        }
                    ?>
                        <article class="kanban__card"
                                 data-task-id="<?php echo esc_attr($task->ID); ?>"
                                 data-priority="<?php echo esc_attr($priority); ?>"
                                 data-title="<?php echo esc_attr(get_the_title($task)); ?>"
                                 data-description="<?php echo esc_attr($task->post_content); ?>"
                                 data-due-date="<?php echo esc_attr($due_date); ?>"
                                 data-due-date-formatted="<?php echo $due_date ? esc_attr(date_i18n('j F Y', strtotime($due_date))) : ''; ?>"
                                 data-is-overdue="<?php echo $is_overdue ? 'true' : 'false'; ?>"
                                 data-is-recurring="<?php echo $is_recurring ? 'true' : 'false'; ?>"
                                 data-checklist="<?php echo esc_attr(wp_json_encode($checklist_json)); ?>"
                                 data-assigned="<?php echo esc_attr(wp_json_encode($assigned_users)); ?>"
                                 data-priority-label="<?php echo esc_attr($priority_labels[$priority] ?? ''); ?>"
                                 data-status="<?php echo esc_attr($status); ?>"
                                 data-status-label="<?php echo esc_attr($column['title']); ?>"
                                 <?php if ($can_edit): ?>draggable="true"<?php endif; ?>>

                            <header class="kanban__card-header">
                                <span class="kanban__card-priority kanban__card-priority--<?php echo esc_attr($priority); ?>"
                                      title="<?php echo esc_attr($priority_labels[$priority] ?? ''); ?>">
                                </span>
                                <h3 class="kanban__card-title">
                                    <?php echo esc_html(get_the_title($task)); ?>
                                </h3>
                                <?php if ($is_recurring): ?>
                                    <span class="kanban__card-recurring" title="<?php esc_attr_e('Tâche récurrente', 'lemur'); ?>">
                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                            <path d="M23 4v6h-6"/>
                                            <path d="M1 20v-6h6"/>
                                            <path d="M3.51 9a9 9 0 0114.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0020.49 15"/>
                                        </svg>
                                    </span>
                                <?php endif; ?>
                            </header>

                            <?php if ($task->post_content): ?>
                                <p class="kanban__card-excerpt">
                                    <?php echo esc_html(wp_trim_words($task->post_content, 10, '...')); ?>
                                </p>
                            <?php endif; ?>

                            <footer class="kanban__card-footer">
                                <?php if ($due_date): ?>
                                    <span class="kanban__card-due <?php echo $is_overdue ? 'kanban__card-due--overdue' : ''; ?>">
                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                            <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/>
                                            <line x1="16" y1="2" x2="16" y2="6"/>
                                            <line x1="8" y1="2" x2="8" y2="6"/>
                                            <line x1="3" y1="10" x2="21" y2="10"/>
                                        </svg>
                                        <?php echo esc_html(date_i18n('j M', strtotime($due_date))); ?>
                                    </span>
                                <?php endif; ?>

                                <?php if ($checklist_total > 0): ?>
                                    <span class="kanban__card-checklist" title="<?php echo esc_attr($checklist_done . '/' . $checklist_total); ?>">
                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                            <path d="M9 11l3 3L22 4"/>
                                            <path d="M21 12v7a2 2 0 01-2 2H5a2 2 0 01-2-2V5a2 2 0 012-2h11"/>
                                        </svg>
                                        <span class="kanban__card-checklist-count"><?php echo esc_html($checklist_done . '/' . $checklist_total); ?></span>
                                    </span>
                                <?php endif; ?>

                                <?php if (!empty($assigned) && is_array($assigned)): ?>
                                    <span class="kanban__card-assigned">
                                        <?php
                                        foreach (array_slice($assigned, 0, 2) as $assignment):
                                            if (isset($assignment['id'])):
                                                $user = get_user_by('id', $assignment['id']);
                                                if ($user):
                                        ?>
                                            <img src="<?php echo esc_url(get_avatar_url($user->ID, ['size' => 24])); ?>"
                                                 alt="<?php echo esc_attr($user->display_name); ?>"
                                                 class="kanban__card-avatar"
                                                 title="<?php echo esc_attr($user->display_name); ?>"
                                                 width="24"
                                                 height="24">
                                        <?php
                                                endif;
                                            endif;
                                        endforeach;
                                        ?>
                                    </span>
                                <?php endif; ?>
                            </footer>

                            <?php if ($can_edit): ?>
                                <a href="<?php echo esc_url(admin_url('post.php?post=' . $task->ID . '&action=edit')); ?>"
                                   class="kanban__card-edit"
                                   title="<?php esc_attr_e('Modifier', 'lemur'); ?>">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                        <path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"/>
                                        <path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/>
                                    </svg>
                                </a>
                            <?php endif; ?>
                        </article>
                    <?php endforeach; ?>

                    <?php if (empty($tasks)): ?>
                        <p class="kanban__empty">
                            <?php esc_html_e('Aucune tâche', 'lemur'); ?>
                        </p>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<!-- Task Detail Modal -->
<div class="task-modal" id="taskModal" role="dialog" aria-modal="true" aria-labelledby="taskModalTitle" hidden>
    <div class="task-modal__backdrop"></div>
    <div class="task-modal__container">
        <div class="task-modal__content">
            <header class="task-modal__header">
                <div class="task-modal__header-info">
                    <span class="task-modal__priority" data-priority=""></span>
                    <span class="task-modal__status"></span>
                    <span class="task-modal__recurring" hidden>
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <path d="M23 4v6h-6"/>
                            <path d="M1 20v-6h6"/>
                            <path d="M3.51 9a9 9 0 0114.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0020.49 15"/>
                        </svg>
                        <?php esc_html_e('Récurrente', 'lemur'); ?>
                    </span>
                </div>
                <button type="button" class="task-modal__close" aria-label="<?php esc_attr_e('Fermer', 'lemur'); ?>">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <line x1="18" y1="6" x2="6" y2="18"/>
                        <line x1="6" y1="6" x2="18" y2="18"/>
                    </svg>
                </button>
            </header>

            <h2 class="task-modal__title" id="taskModalTitle"></h2>

            <div class="task-modal__meta">
                <div class="task-modal__due-date" hidden>
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/>
                        <line x1="16" y1="2" x2="16" y2="6"/>
                        <line x1="8" y1="2" x2="8" y2="6"/>
                        <line x1="3" y1="10" x2="21" y2="10"/>
                    </svg>
                    <span class="task-modal__due-date-text"></span>
                </div>
                <div class="task-modal__assigned" hidden>
                    <span class="task-modal__assigned-avatars"></span>
                    <span class="task-modal__assigned-names"></span>
                </div>
            </div>

            <div class="task-modal__description" hidden>
                <h3 class="task-modal__section-title"><?php esc_html_e('Description', 'lemur'); ?></h3>
                <p class="task-modal__description-text"></p>
            </div>

            <div class="task-modal__checklist" hidden>
                <h3 class="task-modal__section-title">
                    <?php esc_html_e('Sous-tâches', 'lemur'); ?>
                    <span class="task-modal__checklist-progress"></span>
                </h3>
                <ul class="task-modal__checklist-items"></ul>
            </div>

            <?php if ($can_edit): ?>
            <footer class="task-modal__footer">
                <a href="#" class="task-modal__edit-btn" target="_blank">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"/>
                        <path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/>
                    </svg>
                    <?php esc_html_e('Modifier dans l\'admin', 'lemur'); ?>
                </a>
            </footer>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
// Kanban configuration
window.lemurKanban = {
    restUrl: '<?php echo esc_js(rest_url('lemur/v1/')); ?>',
    nonce: '<?php echo esc_js(wp_create_nonce('wp_rest')); ?>',
    canEdit: <?php echo $can_edit ? 'true' : 'false'; ?>,
    editUrl: '<?php echo esc_js(admin_url('post.php?post=')); ?>',
    i18n: {
        overdue: '<?php echo esc_js(__('En retard', 'lemur')); ?>'
    }
};
</script>
