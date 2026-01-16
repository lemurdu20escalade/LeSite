<?php
/**
 * Tasks REST Endpoint
 *
 * Provides REST API for task management (Kanban).
 *
 * @package Lemur\Rest
 */

declare(strict_types=1);

namespace Lemur\Rest;

use Lemur\CustomPostTypes\Tasks;
use Lemur\MemberArea\Access\Capabilities;

/**
 * Tasks REST endpoint
 */
class TasksEndpoint
{
    /**
     * REST namespace
     */
    public const NAMESPACE = 'lemur/v1';

    /**
     * Initialize the endpoint
     */
    public static function init(): void
    {
        add_action('rest_api_init', [self::class, 'registerRoutes'], 10);
    }

    /**
     * Register REST routes
     */
    public static function registerRoutes(): void
    {
        // PATCH /lemur/v1/tasks/{id} - Update task status
        register_rest_route(self::NAMESPACE, '/tasks/(?P<id>\d+)', [
            'methods'             => 'PATCH',
            'callback'            => [self::class, 'updateTask'],
            'permission_callback' => [self::class, 'canEditTasks'],
            'args'                => [
                'id' => [
                    'type'              => 'integer',
                    'required'          => true,
                    'sanitize_callback' => 'absint',
                ],
                'status' => [
                    'type'              => 'string',
                    'sanitize_callback' => 'sanitize_text_field',
                    'enum'              => [Tasks::STATUS_TODO, Tasks::STATUS_IN_PROGRESS, Tasks::STATUS_DONE],
                ],
            ],
        ]);

        // PATCH /lemur/v1/tasks/{id}/checklist - Update checklist
        register_rest_route(self::NAMESPACE, '/tasks/(?P<id>\d+)/checklist', [
            'methods'             => 'PATCH',
            'callback'            => [self::class, 'updateChecklist'],
            'permission_callback' => [self::class, 'canEditTasks'],
            'args'                => [
                'id' => [
                    'type'              => 'integer',
                    'required'          => true,
                    'sanitize_callback' => 'absint',
                ],
                'checklist' => [
                    'type'     => 'array',
                    'required' => true,
                    'items'    => [
                        'type'       => 'object',
                        'properties' => [
                            'index' => ['type' => 'integer'],
                            'item'  => ['type' => 'string'],
                            'done'  => ['type' => 'boolean'],
                        ],
                    ],
                ],
            ],
        ]);

        // GET /lemur/v1/tasks - List tasks
        register_rest_route(self::NAMESPACE, '/tasks', [
            'methods'             => 'GET',
            'callback'            => [self::class, 'getTasks'],
            'permission_callback' => [self::class, 'canViewTasks'],
            'args'                => [
                'season' => [
                    'type'              => 'string',
                    'sanitize_callback' => 'sanitize_text_field',
                ],
                'status' => [
                    'type'              => 'string',
                    'sanitize_callback' => 'sanitize_text_field',
                ],
            ],
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
    public static function canEditTasks(): bool
    {
        return Capabilities::canEditTodos();
    }

    /**
     * REST handler: Update task
     *
     * @param \WP_REST_Request $request REST request
     * @return \WP_REST_Response|\WP_Error
     */
    public static function updateTask(\WP_REST_Request $request)
    {
        $task_id = $request->get_param('id');
        $status = $request->get_param('status');

        // Verify task exists and is correct post type
        $task = get_post($task_id);

        if ($task === null || $task->post_type !== Tasks::POST_TYPE) {
            return new \WP_Error(
                'task_not_found',
                __('Tâche non trouvée.', 'lemur'),
                ['status' => 404]
            );
        }

        // Update status if provided
        if ($status !== null) {
            if (!function_exists('carbon_set_post_meta')) {
                return new \WP_Error(
                    'carbon_not_available',
                    __('Carbon Fields non disponible.', 'lemur'),
                    ['status' => 500]
                );
            }

            carbon_set_post_meta($task_id, Tasks::FIELD_STATUS, $status);
        }

        return new \WP_REST_Response([
            'success' => true,
            'task_id' => $task_id,
            'status'  => $status,
            'message' => __('Tâche mise à jour.', 'lemur'),
        ], 200);
    }

    /**
     * REST handler: Update checklist
     *
     * @param \WP_REST_Request $request REST request
     * @return \WP_REST_Response|\WP_Error
     */
    public static function updateChecklist(\WP_REST_Request $request)
    {
        $task_id = $request->get_param('id');
        $checklist = $request->get_param('checklist');

        // Verify task exists and is correct post type
        $task = get_post($task_id);

        if ($task === null || $task->post_type !== Tasks::POST_TYPE) {
            return new \WP_Error(
                'task_not_found',
                __('Tâche non trouvée.', 'lemur'),
                ['status' => 404]
            );
        }

        if (!function_exists('carbon_set_post_meta')) {
            return new \WP_Error(
                'carbon_not_available',
                __('Carbon Fields non disponible.', 'lemur'),
                ['status' => 500]
            );
        }

        // Format checklist for Carbon Fields
        $formatted_checklist = [];
        foreach ($checklist as $item) {
            $formatted_checklist[] = [
                'item' => sanitize_text_field($item['item'] ?? ''),
                'done' => !empty($item['done']),
            ];
        }

        carbon_set_post_meta($task_id, Tasks::FIELD_CHECKLIST, $formatted_checklist);

        return new \WP_REST_Response([
            'success'   => true,
            'task_id'   => $task_id,
            'checklist' => $formatted_checklist,
            'message'   => __('Checklist mise à jour.', 'lemur'),
        ], 200);
    }

    /**
     * REST handler: Get tasks
     *
     * @param \WP_REST_Request $request REST request
     * @return \WP_REST_Response
     */
    public static function getTasks(\WP_REST_Request $request): \WP_REST_Response
    {
        $season = $request->get_param('season') ?: Tasks::getCurrentSeason();
        $status = $request->get_param('status');

        $args = [
            'post_type'      => Tasks::POST_TYPE,
            'posts_per_page' => -1,
            'meta_query'     => [
                [
                    'key'   => '_' . Tasks::FIELD_SEASON,
                    'value' => $season,
                ],
            ],
        ];

        if ($status !== null) {
            $args['meta_query'][] = [
                'key'   => '_' . Tasks::FIELD_STATUS,
                'value' => $status,
            ];
        }

        $tasks = get_posts($args);

        $formatted = array_map(function ($task) {
            return self::formatTaskForApi($task);
        }, $tasks);

        return new \WP_REST_Response([
            'tasks'  => $formatted,
            'total'  => count($formatted),
            'season' => $season,
        ], 200);
    }

    /**
     * Format task for API response
     *
     * @param \WP_Post $task Task post
     * @return array<string, mixed>
     */
    private static function formatTaskForApi(\WP_Post $task): array
    {
        $checklist = carbon_get_post_meta($task->ID, Tasks::FIELD_CHECKLIST);
        $checklist_data = [];
        $checklist_done = 0;

        if (!empty($checklist) && is_array($checklist)) {
            foreach ($checklist as $index => $item) {
                $is_done = !empty($item['done']);
                if ($is_done) {
                    $checklist_done++;
                }
                $checklist_data[] = [
                    'index' => $index,
                    'item'  => $item['item'] ?? '',
                    'done'  => $is_done,
                ];
            }
        }

        $assigned = carbon_get_post_meta($task->ID, Tasks::FIELD_ASSIGNED_TO);
        $assigned_users = [];

        if (!empty($assigned) && is_array($assigned)) {
            foreach ($assigned as $assignment) {
                if (isset($assignment['id'])) {
                    $user = get_user_by('id', $assignment['id']);
                    if ($user) {
                        $assigned_users[] = [
                            'id'     => self::obfuscateUserId($user->ID),
                            'name'   => $user->display_name,
                            'avatar' => self::getPrivacySafeAvatar($user->display_name),
                        ];
                    }
                }
            }
        }

        $due_date = carbon_get_post_meta($task->ID, Tasks::FIELD_DUE_DATE);
        $status = carbon_get_post_meta($task->ID, Tasks::FIELD_STATUS) ?: Tasks::STATUS_TODO;

        return [
            'id'           => $task->ID,
            'title'        => get_the_title($task),
            'description'  => $task->post_content,
            'status'       => $status,
            'priority'     => carbon_get_post_meta($task->ID, Tasks::FIELD_PRIORITY) ?: Tasks::PRIORITY_MEDIUM,
            'due_date'     => $due_date,
            'is_overdue'   => $due_date && strtotime($due_date) < time() && $status !== Tasks::STATUS_DONE,
            'is_recurring' => carbon_get_post_meta($task->ID, Tasks::FIELD_IS_RECURRING) === 'yes',
            'checklist'    => $checklist_data,
            'checklist_done' => $checklist_done,
            'checklist_total' => count($checklist_data),
            'assigned'     => $assigned_users,
        ];
    }

    /**
     * Obfuscate user ID to prevent enumeration
     *
     * @param int $user_id WordPress user ID
     * @return string Obfuscated ID (12 chars)
     */
    private static function obfuscateUserId(int $user_id): string
    {
        $salt = defined('NONCE_SALT') ? NONCE_SALT : 'lemur_default_salt';

        return substr(hash('sha256', 'task_user_' . $salt . $user_id), 0, 12);
    }

    /**
     * Get privacy-safe avatar URL (no email hash exposure)
     *
     * Uses UI Avatars service with initials only - no personal data exposed.
     *
     * @param string $name Display name for initials
     * @return string Avatar URL
     */
    private static function getPrivacySafeAvatar(string $name): string
    {
        // Extract initials (first letter of first two words)
        $parts = explode(' ', trim($name));
        $initials = mb_substr($parts[0], 0, 1);
        if (isset($parts[1])) {
            $initials .= mb_substr($parts[1], 0, 1);
        }

        $initials = urlencode(strtoupper($initials));

        // UI Avatars - generates avatars from initials, no personal data
        return sprintf(
            'https://ui-avatars.com/api/?name=%s&size=32&background=4a7c59&color=ffffff&bold=true',
            $initials
        );
    }
}
