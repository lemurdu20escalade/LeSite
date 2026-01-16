<?php
/**
 * Tasks Custom Post Type
 *
 * Manages annual tasks/todos for the club.
 * Bureau can edit, members can view.
 *
 * @package Lemur\CustomPostTypes
 */

declare(strict_types=1);

namespace Lemur\CustomPostTypes;

use Carbon_Fields\Container;
use Carbon_Fields\Field;
use Lemur\MemberArea\Access\Capabilities;

/**
 * Tasks CPT for member area
 */
class Tasks
{
    /**
     * Post type slug
     */
    public const POST_TYPE = 'lemur_taches';

    /**
     * Taxonomy slug
     */
    public const TAXONOMY = 'categorie-tache';

    /**
     * Field keys
     */
    public const FIELD_STATUS = 'task_status';
    public const FIELD_PRIORITY = 'task_priority';
    public const FIELD_DUE_DATE = 'task_due_date';
    public const FIELD_ASSIGNED_TO = 'task_assigned_to';
    public const FIELD_CHECKLIST = 'task_checklist';
    public const FIELD_ORDER = 'task_order';
    public const FIELD_SEASON = 'task_season';
    public const FIELD_IS_RECURRING = 'task_is_recurring';
    public const FIELD_RECURRING_MONTH = 'task_recurring_month';
    public const FIELD_RECURRING_DAY = 'task_recurring_day';

    /**
     * Status constants
     */
    public const STATUS_TODO = 'todo';
    public const STATUS_IN_PROGRESS = 'in_progress';
    public const STATUS_DONE = 'done';

    /**
     * Priority constants
     */
    public const PRIORITY_LOW = 'low';
    public const PRIORITY_MEDIUM = 'medium';
    public const PRIORITY_HIGH = 'high';

    /**
     * Initialize the Tasks CPT
     */
    public static function init(): void
    {
        add_action('init', [self::class, 'register'], 10);
        add_action('init', [self::class, 'registerTaxonomy'], 10);
        add_action('carbon_fields_register_fields', [self::class, 'registerFields'], 10);
        add_action('rest_api_init', [self::class, 'registerRestRoutes'], 10);

        // Admin columns
        add_filter('manage_' . self::POST_TYPE . '_posts_columns', [self::class, 'addAdminColumns'], 10);
        add_action('manage_' . self::POST_TYPE . '_posts_custom_column', [self::class, 'renderAdminColumns'], 10, 2);
        add_filter('manage_edit-' . self::POST_TYPE . '_sortable_columns', [self::class, 'sortableColumns'], 10);

        // Restrict access
        add_filter('user_has_cap', [self::class, 'filterCapabilities'], 10, 4);

        // Admin actions for recurring tasks
        add_action('admin_init', [self::class, 'handleGenerateRecurringTasks'], 10);
        add_action('admin_notices', [self::class, 'adminNotices'], 10);
        add_filter('bulk_actions-edit-' . self::POST_TYPE, [self::class, 'addBulkActions'], 10);
    }

    /**
     * Register the post type
     */
    public static function register(): void
    {
        $labels = [
            'name'                  => __('Tâches', 'lemur'),
            'singular_name'         => __('Tâche', 'lemur'),
            'menu_name'             => __('Tâches club', 'lemur'),
            'add_new'               => __('Ajouter', 'lemur'),
            'add_new_item'          => __('Ajouter une tâche', 'lemur'),
            'edit_item'             => __('Modifier la tâche', 'lemur'),
            'new_item'              => __('Nouvelle tâche', 'lemur'),
            'view_item'             => __('Voir la tâche', 'lemur'),
            'search_items'          => __('Rechercher une tâche', 'lemur'),
            'not_found'             => __('Aucune tâche trouvée', 'lemur'),
            'not_found_in_trash'    => __('Aucune tâche dans la corbeille', 'lemur'),
            'all_items'             => __('Toutes les tâches', 'lemur'),
        ];

        $args = [
            'labels'              => $labels,
            'public'              => false,
            'publicly_queryable'  => false,
            'show_ui'             => true,
            'show_in_menu'        => true,
            'show_in_rest'        => false, // Custom REST endpoint
            'query_var'           => false,
            'rewrite'             => false,
            'capability_type'     => 'lemur_task',
            'map_meta_cap'        => true,
            'has_archive'         => false,
            'hierarchical'        => false,
            'menu_position'       => 26,
            'menu_icon'           => 'dashicons-yes-alt',
            'supports'            => ['title', 'editor'],
        ];

        register_post_type(self::POST_TYPE, $args);
    }

    /**
     * Register the task category taxonomy
     */
    public static function registerTaxonomy(): void
    {
        $labels = [
            'name'          => __('Catégories de tâches', 'lemur'),
            'singular_name' => __('Catégorie', 'lemur'),
            'search_items'  => __('Rechercher une catégorie', 'lemur'),
            'all_items'     => __('Toutes les catégories', 'lemur'),
            'edit_item'     => __('Modifier la catégorie', 'lemur'),
            'update_item'   => __('Mettre à jour', 'lemur'),
            'add_new_item'  => __('Ajouter une catégorie', 'lemur'),
            'new_item_name' => __('Nom de la catégorie', 'lemur'),
            'menu_name'     => __('Catégories', 'lemur'),
        ];

        $args = [
            'labels'            => $labels,
            'hierarchical'      => true,
            'public'            => false,
            'show_ui'           => true,
            'show_admin_column' => true,
            'show_in_rest'      => false,
            'rewrite'           => false,
        ];

        register_taxonomy(self::TAXONOMY, [self::POST_TYPE], $args);
    }

    /**
     * Register Carbon Fields for tasks
     */
    public static function registerFields(): void
    {
        Container::make('post_meta', __('Détails de la tâche', 'lemur'))
            ->where('post_type', '=', self::POST_TYPE)
            ->add_fields([
                Field::make('select', self::FIELD_STATUS, __('Statut', 'lemur'))
                    ->set_options([
                        self::STATUS_TODO        => __('À faire', 'lemur'),
                        self::STATUS_IN_PROGRESS => __('En cours', 'lemur'),
                        self::STATUS_DONE        => __('Terminé', 'lemur'),
                    ])
                    ->set_default_value(self::STATUS_TODO)
                    ->set_width(25),

                Field::make('select', self::FIELD_PRIORITY, __('Priorité', 'lemur'))
                    ->set_options([
                        self::PRIORITY_LOW    => __('Basse', 'lemur'),
                        self::PRIORITY_MEDIUM => __('Moyenne', 'lemur'),
                        self::PRIORITY_HIGH   => __('Haute', 'lemur'),
                    ])
                    ->set_default_value(self::PRIORITY_MEDIUM)
                    ->set_width(25),

                Field::make('date', self::FIELD_DUE_DATE, __('Échéance', 'lemur'))
                    ->set_storage_format('Y-m-d')
                    ->set_width(25),

                Field::make('text', self::FIELD_SEASON, __('Saison', 'lemur'))
                    ->set_default_value(self::getCurrentSeason())
                    ->set_width(25)
                    ->set_help_text(__('Format: 2024-2025', 'lemur')),

                Field::make('text', self::FIELD_ORDER, __('Ordre', 'lemur'))
                    ->set_attribute('type', 'number')
                    ->set_default_value('0')
                    ->set_width(25)
                    ->set_help_text(__('Pour le tri dans le Kanban', 'lemur')),

                Field::make('association', self::FIELD_ASSIGNED_TO, __('Assigné à', 'lemur'))
                    ->set_types([['type' => 'user']])
                    ->set_width(75),

                Field::make('complex', self::FIELD_CHECKLIST, __('Checklist', 'lemur'))
                    ->add_fields([
                        Field::make('text', 'item', __('Élément', 'lemur'))
                            ->set_width(80),
                        Field::make('checkbox', 'done', __('Fait', 'lemur'))
                            ->set_width(20),
                    ])
                    ->set_collapsed(true)
                    ->set_header_template('<%- item %>')
                    ->set_help_text(__('Liste de sous-tâches à cocher', 'lemur')),

                // Recurring task fields
                Field::make('separator', 'sep_recurring', __('Récurrence annuelle', 'lemur')),

                Field::make('checkbox', self::FIELD_IS_RECURRING, __('Tâche récurrente', 'lemur'))
                    ->set_option_value('yes')
                    ->set_width(25)
                    ->set_help_text(__('Cette tâche sera régénérée chaque saison', 'lemur')),

                Field::make('select', self::FIELD_RECURRING_MONTH, __('Mois de la saison', 'lemur'))
                    ->set_options(self::getSeasonMonthOptions())
                    ->set_width(37)
                    ->set_help_text(__('Mois relatif à la saison (sept-août)', 'lemur')),

                Field::make('select', self::FIELD_RECURRING_DAY, __('Jour du mois', 'lemur'))
                    ->set_options(self::getDayOptions())
                    ->set_width(38)
                    ->set_help_text(__('Jour d\'échéance', 'lemur')),
            ]);
    }

    /**
     * Register REST API routes
     */
    public static function registerRestRoutes(): void
    {
        // GET /lemur/v1/tasks - List tasks
        register_rest_route('lemur/v1', '/tasks', [
            'methods'             => 'GET',
            'callback'            => [self::class, 'restGetTasks'],
            'permission_callback' => [self::class, 'canViewTasks'],
            'args'                => [
                'status' => ['type' => 'string'],
                'season' => ['type' => 'string'],
            ],
        ]);

        // PATCH /lemur/v1/tasks/{id} - Update task (status, order, priority)
        register_rest_route('lemur/v1', '/tasks/(?P<id>\d+)', [
            'methods'             => 'PATCH',
            'callback'            => [self::class, 'restUpdateTask'],
            'permission_callback' => [self::class, 'canEditTask'],
            'args'                => [
                'id' => [
                    'validate_callback' => function ($param) {
                        return is_numeric($param) && (int) $param > 0;
                    },
                ],
            ],
        ]);

        // POST /lemur/v1/tasks/{id}/reorder - Reorder task in column
        register_rest_route('lemur/v1', '/tasks/(?P<id>\d+)/reorder', [
            'methods'             => 'POST',
            'callback'            => [self::class, 'restReorderTask'],
            'permission_callback' => [self::class, 'canEditTask'],
        ]);
    }

    /**
     * Check if user can view tasks
     */
    public static function canViewTasks(): bool
    {
        return Capabilities::canAccessMemberArea();
    }

    /**
     * Check if user can edit tasks
     */
    public static function canEditTask(): bool
    {
        return Capabilities::canEditTodos();
    }

    /**
     * REST handler: Get tasks list
     *
     * @param \WP_REST_Request $request REST request
     * @return \WP_REST_Response
     */
    public static function restGetTasks(\WP_REST_Request $request): \WP_REST_Response
    {
        $args = [
            'post_type'      => self::POST_TYPE,
            'posts_per_page' => -1,
            'orderby'        => 'meta_value_num',
            'meta_key'       => '_' . self::FIELD_ORDER,
            'order'          => 'ASC',
        ];

        // Filter by status
        $status = $request->get_param('status');
        if (!empty($status) && in_array($status, [self::STATUS_TODO, self::STATUS_IN_PROGRESS, self::STATUS_DONE], true)) {
            $args['meta_query'] = [
                [
                    'key'   => '_' . self::FIELD_STATUS,
                    'value' => $status,
                ],
            ];
        }

        // Filter by season
        $season = $request->get_param('season');
        if (!empty($season)) {
            $args['meta_query'] = $args['meta_query'] ?? [];
            $args['meta_query'][] = [
                'key'   => '_' . self::FIELD_SEASON,
                'value' => sanitize_text_field($season),
            ];
        }

        $tasks = get_posts($args);

        $data = array_map(function ($task) {
            return self::formatTaskForApi($task);
        }, $tasks);

        return new \WP_REST_Response($data, 200);
    }

    /**
     * REST handler: Update task
     *
     * @param \WP_REST_Request $request REST request
     * @return \WP_REST_Response
     */
    public static function restUpdateTask(\WP_REST_Request $request): \WP_REST_Response
    {
        $task_id = (int) $request->get_param('id');
        $data = $request->get_json_params();

        // Verify task exists
        $task = get_post($task_id);
        if (!$task || $task->post_type !== self::POST_TYPE) {
            return new \WP_REST_Response(['error' => 'Task not found'], 404);
        }

        // Update status
        if (isset($data['status'])) {
            $allowed_statuses = [self::STATUS_TODO, self::STATUS_IN_PROGRESS, self::STATUS_DONE];
            if (in_array($data['status'], $allowed_statuses, true)) {
                carbon_set_post_meta($task_id, self::FIELD_STATUS, $data['status']);
            }
        }

        // Update priority
        if (isset($data['priority'])) {
            $allowed_priorities = [self::PRIORITY_LOW, self::PRIORITY_MEDIUM, self::PRIORITY_HIGH];
            if (in_array($data['priority'], $allowed_priorities, true)) {
                carbon_set_post_meta($task_id, self::FIELD_PRIORITY, $data['priority']);
            }
        }

        // Update order
        if (isset($data['order'])) {
            carbon_set_post_meta($task_id, self::FIELD_ORDER, (int) $data['order']);
        }

        // Update due date
        if (isset($data['due_date'])) {
            $date = sanitize_text_field($data['due_date']);
            if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
                carbon_set_post_meta($task_id, self::FIELD_DUE_DATE, $date);
            }
        }

        return new \WP_REST_Response([
            'success' => true,
            'task'    => self::formatTaskForApi(get_post($task_id)),
        ], 200);
    }

    /**
     * REST handler: Reorder task
     *
     * @param \WP_REST_Request $request REST request
     * @return \WP_REST_Response
     */
    public static function restReorderTask(\WP_REST_Request $request): \WP_REST_Response
    {
        $task_id = (int) $request->get_param('id');
        $data = $request->get_json_params();

        // Verify task exists
        $task = get_post($task_id);
        if (!$task || $task->post_type !== self::POST_TYPE) {
            return new \WP_REST_Response(['error' => 'Task not found'], 404);
        }

        // Update order for all tasks in the same column
        if (isset($data['order']) && is_array($data['order'])) {
            foreach ($data['order'] as $index => $id) {
                carbon_set_post_meta((int) $id, self::FIELD_ORDER, $index);
            }
        }

        return new \WP_REST_Response(['success' => true], 200);
    }

    /**
     * Format task for API response
     *
     * @param \WP_Post $task Task post
     * @return array<string, mixed>
     */
    public static function formatTaskForApi(\WP_Post $task): array
    {
        $assigned = carbon_get_post_meta($task->ID, self::FIELD_ASSIGNED_TO);
        $assigned_users = [];

        if (!empty($assigned) && is_array($assigned)) {
            foreach ($assigned as $assignment) {
                if (isset($assignment['id'])) {
                    $user = get_user_by('id', $assignment['id']);
                    if ($user) {
                        $assigned_users[] = [
                            'id'   => $user->ID,
                            'name' => $user->display_name,
                        ];
                    }
                }
            }
        }

        $checklist = carbon_get_post_meta($task->ID, self::FIELD_CHECKLIST);
        $checklist_data = [];

        if (!empty($checklist) && is_array($checklist)) {
            foreach ($checklist as $item) {
                $checklist_data[] = [
                    'item' => $item['item'] ?? '',
                    'done' => !empty($item['done']),
                ];
            }
        }

        $terms = wp_get_post_terms($task->ID, self::TAXONOMY);
        $category = !empty($terms) && !is_wp_error($terms) ? $terms[0]->name : null;

        return [
            'id'          => $task->ID,
            'title'       => get_the_title($task),
            'description' => apply_filters('the_content', $task->post_content),
            'status'      => carbon_get_post_meta($task->ID, self::FIELD_STATUS) ?: self::STATUS_TODO,
            'priority'    => carbon_get_post_meta($task->ID, self::FIELD_PRIORITY) ?: self::PRIORITY_MEDIUM,
            'due_date'    => carbon_get_post_meta($task->ID, self::FIELD_DUE_DATE),
            'season'      => carbon_get_post_meta($task->ID, self::FIELD_SEASON),
            'order'       => (int) carbon_get_post_meta($task->ID, self::FIELD_ORDER),
            'assigned_to' => $assigned_users,
            'checklist'   => $checklist_data,
            'category'    => $category,
        ];
    }

    /**
     * Get current season (e.g., "2024-2025")
     */
    public static function getCurrentSeason(): string
    {
        $year = (int) date('Y');
        $month = (int) date('m');

        // Season starts in September
        if ($month >= 9) {
            return $year . '-' . ($year + 1);
        }

        return ($year - 1) . '-' . $year;
    }

    /**
     * Add custom admin columns
     *
     * @param array<string, string> $columns Existing columns
     * @return array<string, string>
     */
    public static function addAdminColumns(array $columns): array
    {
        $new_columns = [];

        foreach ($columns as $key => $value) {
            $new_columns[$key] = $value;

            if ($key === 'title') {
                $new_columns['task_status'] = __('Statut', 'lemur');
                $new_columns['task_priority'] = __('Priorité', 'lemur');
                $new_columns['task_due_date'] = __('Échéance', 'lemur');
                $new_columns['task_recurring'] = __('Récurrente', 'lemur');
            }
        }

        return $new_columns;
    }

    /**
     * Render custom admin columns
     *
     * @param string $column  Column key
     * @param int    $post_id Post ID
     */
    public static function renderAdminColumns(string $column, int $post_id): void
    {
        switch ($column) {
            case 'task_status':
                $status = carbon_get_post_meta($post_id, self::FIELD_STATUS) ?: self::STATUS_TODO;
                $labels = [
                    self::STATUS_TODO        => __('À faire', 'lemur'),
                    self::STATUS_IN_PROGRESS => __('En cours', 'lemur'),
                    self::STATUS_DONE        => __('Terminé', 'lemur'),
                ];
                $colors = [
                    self::STATUS_TODO        => '#6c757d',
                    self::STATUS_IN_PROGRESS => '#0d6efd',
                    self::STATUS_DONE        => '#198754',
                ];
                printf(
                    '<span style="background:%s;color:#fff;padding:2px 8px;border-radius:3px;font-size:12px;">%s</span>',
                    esc_attr($colors[$status] ?? '#6c757d'),
                    esc_html($labels[$status] ?? $status)
                );
                break;

            case 'task_priority':
                $priority = carbon_get_post_meta($post_id, self::FIELD_PRIORITY) ?: self::PRIORITY_MEDIUM;
                $labels = [
                    self::PRIORITY_LOW    => __('Basse', 'lemur'),
                    self::PRIORITY_MEDIUM => __('Moyenne', 'lemur'),
                    self::PRIORITY_HIGH   => __('Haute', 'lemur'),
                ];
                echo esc_html($labels[$priority] ?? $priority);
                break;

            case 'task_due_date':
                $date = carbon_get_post_meta($post_id, self::FIELD_DUE_DATE);
                if ($date) {
                    $timestamp = strtotime($date);
                    if ($timestamp === false) {
                        echo '—';
                        break;
                    }
                    $is_overdue = $timestamp < time() && carbon_get_post_meta($post_id, self::FIELD_STATUS) !== self::STATUS_DONE;
                    $formatted = date_i18n(get_option('date_format'), $timestamp);

                    if ($is_overdue) {
                        printf('<span style="color:#dc3545;font-weight:bold;">%s</span>', esc_html($formatted));
                    } else {
                        echo esc_html($formatted);
                    }
                } else {
                    echo '—';
                }
                break;

            case 'task_recurring':
                $is_recurring = carbon_get_post_meta($post_id, self::FIELD_IS_RECURRING);
                if ($is_recurring === 'yes') {
                    $month = carbon_get_post_meta($post_id, self::FIELD_RECURRING_MONTH);
                    $day = carbon_get_post_meta($post_id, self::FIELD_RECURRING_DAY);
                    $month_labels = self::getSeasonMonthOptions();
                    $month_name = isset($month_labels[$month]) ? str_replace(' (mois ' . $month . ')', '', $month_labels[$month]) : '';
                    $title = $month_name && $day ? sprintf('%s %s', $day, $month_name) : __('Oui', 'lemur');
                    printf(
                        '<span class="dashicons dashicons-update" style="color:#198754;" title="%s"></span> %s',
                        esc_attr($title),
                        esc_html($title)
                    );
                } else {
                    echo '—';
                }
                break;
        }
    }

    /**
     * Make columns sortable
     *
     * @param array<string, string> $columns Sortable columns
     * @return array<string, string>
     */
    public static function sortableColumns(array $columns): array
    {
        $columns['task_status'] = 'task_status';
        $columns['task_priority'] = 'task_priority';
        $columns['task_due_date'] = 'task_due_date';

        return $columns;
    }

    /**
     * Filter capabilities for task management
     *
     * @param array<string, bool> $allcaps All capabilities
     * @param array<string>       $caps    Required capabilities
     * @param array<mixed>        $args    Arguments
     * @param \WP_User            $user    User object
     * @return array<string, bool>
     */
    public static function filterCapabilities(array $allcaps, array $caps, array $args, \WP_User $user): array
    {
        $cap_mapping = [
            'edit_lemur_task'     => Capabilities::CAP_EDIT_TODOS,
            'edit_lemur_tasks'    => Capabilities::CAP_EDIT_TODOS,
            'delete_lemur_task'   => Capabilities::CAP_EDIT_TODOS,
            'delete_lemur_tasks'  => Capabilities::CAP_EDIT_TODOS,
            'publish_lemur_tasks' => Capabilities::CAP_EDIT_TODOS,
        ];

        foreach ($cap_mapping as $task_cap => $lemur_cap) {
            if (in_array($task_cap, $caps, true)) {
                // Check directly in $allcaps to avoid recursion (user_can triggers user_has_cap filter)
                if (!empty($allcaps[$lemur_cap]) || !empty($allcaps['manage_options'])) {
                    $allcaps[$task_cap] = true;
                }
            }
        }

        return $allcaps;
    }

    /**
     * Get tasks grouped by status (for Kanban)
     *
     * @param string|null $season Season filter
     * @return array<string, array<\WP_Post>>
     */
    public static function getTasksByStatus(?string $season = null): array
    {
        $args = [
            'post_type'      => self::POST_TYPE,
            'posts_per_page' => -1,
            'orderby'        => 'meta_value_num',
            'meta_key'       => '_' . self::FIELD_ORDER,
            'order'          => 'ASC',
        ];

        if ($season !== null) {
            $args['meta_query'] = [
                [
                    'key'   => '_' . self::FIELD_SEASON,
                    'value' => $season,
                ],
            ];
        }

        $tasks = get_posts($args);

        $grouped = [
            self::STATUS_TODO        => [],
            self::STATUS_IN_PROGRESS => [],
            self::STATUS_DONE        => [],
        ];

        foreach ($tasks as $task) {
            $status = carbon_get_post_meta($task->ID, self::FIELD_STATUS) ?: self::STATUS_TODO;

            if (isset($grouped[$status])) {
                $grouped[$status][] = $task;
            }
        }

        return $grouped;
    }

    /**
     * Get season month options (September = 1, August = 12)
     *
     * @return array<string, string>
     */
    private static function getSeasonMonthOptions(): array
    {
        return [
            ''   => __('Non défini', 'lemur'),
            '1'  => __('Septembre (mois 1)', 'lemur'),
            '2'  => __('Octobre (mois 2)', 'lemur'),
            '3'  => __('Novembre (mois 3)', 'lemur'),
            '4'  => __('Décembre (mois 4)', 'lemur'),
            '5'  => __('Janvier (mois 5)', 'lemur'),
            '6'  => __('Février (mois 6)', 'lemur'),
            '7'  => __('Mars (mois 7)', 'lemur'),
            '8'  => __('Avril (mois 8)', 'lemur'),
            '9'  => __('Mai (mois 9)', 'lemur'),
            '10' => __('Juin (mois 10)', 'lemur'),
            '11' => __('Juillet (mois 11)', 'lemur'),
            '12' => __('Août (mois 12)', 'lemur'),
        ];
    }

    /**
     * Get day options (1-31)
     *
     * @return array<string, string>
     */
    private static function getDayOptions(): array
    {
        $options = ['' => __('Non défini', 'lemur')];

        for ($i = 1; $i <= 31; $i++) {
            $options[(string) $i] = (string) $i;
        }

        return $options;
    }

    /**
     * Add bulk actions for generating recurring tasks
     *
     * @param array<string, string> $actions Existing bulk actions
     * @return array<string, string>
     */
    public static function addBulkActions(array $actions): array
    {
        $next_season = self::getNextSeason();
        $actions['generate_recurring'] = sprintf(
            /* translators: %s: next season (e.g., "2025-2026") */
            __('Générer pour %s', 'lemur'),
            $next_season
        );

        return $actions;
    }

    /**
     * Get next season
     *
     * @return string Next season (e.g., "2025-2026")
     */
    public static function getNextSeason(): string
    {
        $current = self::getCurrentSeason();
        $parts = explode('-', $current);

        if (count($parts) === 2) {
            $start_year = (int) $parts[0] + 1;
            return $start_year . '-' . ($start_year + 1);
        }

        // Fallback
        $year = (int) date('Y');
        return ($year + 1) . '-' . ($year + 2);
    }

    /**
     * Handle admin action for generating recurring tasks
     */
    public static function handleGenerateRecurringTasks(): void
    {
        // Check for bulk action
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        $action = isset($_GET['action']) ? sanitize_text_field($_GET['action']) : '';
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        $action2 = isset($_GET['action2']) ? sanitize_text_field($_GET['action2']) : '';

        if ($action !== 'generate_recurring' && $action2 !== 'generate_recurring') {
            return;
        }

        // Verify nonce and capability
        if (!check_admin_referer('bulk-posts')) {
            return;
        }

        if (!Capabilities::canEditTodos()) {
            return;
        }

        // Generate tasks for next season
        $next_season = self::getNextSeason();
        $count = self::generateRecurringTasks($next_season);

        // Redirect with result
        $redirect = add_query_arg([
            'post_type'             => self::POST_TYPE,
            'recurring_generated'   => $count,
            'recurring_season'      => $next_season,
        ], admin_url('edit.php'));

        wp_safe_redirect($redirect);
        exit;
    }

    /**
     * Generate recurring tasks for a new season
     *
     * @param string $new_season Target season (e.g., "2025-2026")
     * @return int Number of tasks created
     */
    public static function generateRecurringTasks(string $new_season): int
    {
        // Get all recurring tasks (regardless of season)
        $recurring_tasks = get_posts([
            'post_type'      => self::POST_TYPE,
            'posts_per_page' => -1,
            'meta_query'     => [
                [
                    'key'   => '_' . self::FIELD_IS_RECURRING,
                    'value' => 'yes',
                ],
            ],
        ]);

        if (empty($recurring_tasks)) {
            return 0;
        }

        // Check which tasks already exist for the new season (by title)
        $existing_titles = [];
        $existing_tasks = get_posts([
            'post_type'      => self::POST_TYPE,
            'posts_per_page' => -1,
            'meta_query'     => [
                [
                    'key'   => '_' . self::FIELD_SEASON,
                    'value' => $new_season,
                ],
            ],
        ]);

        foreach ($existing_tasks as $task) {
            $existing_titles[] = $task->post_title;
        }

        // Parse new season start year
        $parts = explode('-', $new_season);
        $season_start_year = (int) ($parts[0] ?? date('Y'));

        $created = 0;

        foreach ($recurring_tasks as $source_task) {
            // Skip if task with same title already exists for this season
            if (in_array($source_task->post_title, $existing_titles, true)) {
                continue;
            }

            // Get recurring settings
            $recurring_month = (int) carbon_get_post_meta($source_task->ID, self::FIELD_RECURRING_MONTH);
            $recurring_day = (int) carbon_get_post_meta($source_task->ID, self::FIELD_RECURRING_DAY);

            // Calculate due date based on season month
            $due_date = '';
            if ($recurring_month > 0 && $recurring_day > 0) {
                // Season months 1-4 = September-December (same year as season start)
                // Season months 5-12 = January-August (year after season start)
                if ($recurring_month <= 4) {
                    // September (1) = month 9, October (2) = month 10, etc.
                    $calendar_month = $recurring_month + 8;
                    $calendar_year = $season_start_year;
                } else {
                    // January (5) = month 1, February (6) = month 2, etc.
                    $calendar_month = $recurring_month - 4;
                    $calendar_year = $season_start_year + 1;
                }

                // Validate day for the month
                $days_in_month = (int) date('t', mktime(0, 0, 0, $calendar_month, 1, $calendar_year));
                $valid_day = min($recurring_day, $days_in_month);

                $due_date = sprintf('%04d-%02d-%02d', $calendar_year, $calendar_month, $valid_day);
            }

            // Create new task
            $new_task_id = wp_insert_post([
                'post_type'    => self::POST_TYPE,
                'post_status'  => 'publish',
                'post_title'   => $source_task->post_title,
                'post_content' => $source_task->post_content,
            ]);

            if (is_wp_error($new_task_id) || $new_task_id === 0) {
                continue;
            }

            // Copy meta fields
            carbon_set_post_meta($new_task_id, self::FIELD_STATUS, self::STATUS_TODO);
            carbon_set_post_meta($new_task_id, self::FIELD_PRIORITY, carbon_get_post_meta($source_task->ID, self::FIELD_PRIORITY) ?: self::PRIORITY_MEDIUM);
            carbon_set_post_meta($new_task_id, self::FIELD_SEASON, $new_season);
            carbon_set_post_meta($new_task_id, self::FIELD_ORDER, carbon_get_post_meta($source_task->ID, self::FIELD_ORDER));

            // Set due date if calculated
            if (!empty($due_date)) {
                carbon_set_post_meta($new_task_id, self::FIELD_DUE_DATE, $due_date);
            }

            // Copy recurring settings
            carbon_set_post_meta($new_task_id, self::FIELD_IS_RECURRING, 'yes');
            carbon_set_post_meta($new_task_id, self::FIELD_RECURRING_MONTH, (string) $recurring_month);
            carbon_set_post_meta($new_task_id, self::FIELD_RECURRING_DAY, (string) $recurring_day);

            // Copy assigned users
            $assigned = carbon_get_post_meta($source_task->ID, self::FIELD_ASSIGNED_TO);
            if (!empty($assigned)) {
                carbon_set_post_meta($new_task_id, self::FIELD_ASSIGNED_TO, $assigned);
            }

            // Copy checklist (reset done status)
            $checklist = carbon_get_post_meta($source_task->ID, self::FIELD_CHECKLIST);
            if (!empty($checklist) && is_array($checklist)) {
                $new_checklist = array_map(function ($item) {
                    return [
                        'item' => $item['item'] ?? '',
                        'done' => false,
                    ];
                }, $checklist);
                carbon_set_post_meta($new_task_id, self::FIELD_CHECKLIST, $new_checklist);
            }

            // Copy taxonomy terms
            $terms = wp_get_post_terms($source_task->ID, self::TAXONOMY, ['fields' => 'ids']);
            if (!empty($terms) && !is_wp_error($terms)) {
                wp_set_post_terms($new_task_id, $terms, self::TAXONOMY);
            }

            $created++;
        }

        return $created;
    }

    /**
     * Display admin notices for recurring task generation
     */
    public static function adminNotices(): void
    {
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        if (!isset($_GET['recurring_generated'])) {
            return;
        }

        // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        $count = (int) $_GET['recurring_generated'];
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        $season = isset($_GET['recurring_season']) ? sanitize_text_field($_GET['recurring_season']) : '';

        if ($count > 0) {
            printf(
                '<div class="notice notice-success is-dismissible"><p>%s</p></div>',
                sprintf(
                    /* translators: 1: number of tasks, 2: season */
                    esc_html(_n(
                        '%1$d tâche récurrente générée pour la saison %2$s.',
                        '%1$d tâches récurrentes générées pour la saison %2$s.',
                        $count,
                        'lemur'
                    )),
                    $count,
                    esc_html($season)
                )
            );
        } else {
            printf(
                '<div class="notice notice-warning is-dismissible"><p>%s</p></div>',
                esc_html__('Aucune nouvelle tâche récurrente à générer (toutes existent déjà pour cette saison).', 'lemur')
            );
        }
    }
}
