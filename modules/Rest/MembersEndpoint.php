<?php
/**
 * Members REST Endpoint
 *
 * Provides RGPD-compliant member directory API.
 * Only exposes first names for privacy.
 *
 * @package Lemur\Rest
 */

declare(strict_types=1);

namespace Lemur\Rest;

use Lemur\MemberArea\Access\Capabilities;
use Lemur\MemberArea\Access\RolesManager;

/**
 * Members directory REST endpoint
 */
class MembersEndpoint
{
    /**
     * REST namespace
     */
    public const NAMESPACE = 'lemur/v1';

    /**
     * User meta key for hiding from directory
     */
    public const META_HIDE_FROM_DIRECTORY = '_lemur_hide_from_directory';

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
        // GET /lemur/v1/members - List members (RGPD minimal)
        register_rest_route(self::NAMESPACE, '/members', [
            'methods'             => 'GET',
            'callback'            => [self::class, 'getMembers'],
            'permission_callback' => [self::class, 'canViewMembers'],
            'args'                => [
                'search' => [
                    'type'              => 'string',
                    'sanitize_callback' => 'sanitize_text_field',
                ],
                'letter' => [
                    'type'              => 'string',
                    'sanitize_callback' => function ($value) {
                        return strtoupper(substr(sanitize_text_field($value), 0, 1));
                    },
                ],
                'page' => [
                    'type'    => 'integer',
                    'default' => 1,
                ],
                'per_page' => [
                    'type'    => 'integer',
                    'default' => 50,
                    'maximum' => 100,
                ],
            ],
        ]);

        // GET /lemur/v1/members/count - Get total count
        register_rest_route(self::NAMESPACE, '/members/count', [
            'methods'             => 'GET',
            'callback'            => [self::class, 'getMembersCount'],
            'permission_callback' => [self::class, 'canViewMembers'],
        ]);
    }

    /**
     * Check if user can view members directory
     */
    public static function canViewMembers(): bool
    {
        return Capabilities::canAccessMemberArea();
    }

    /**
     * REST handler: Get members list
     *
     * @param \WP_REST_Request $request REST request
     * @return \WP_REST_Response
     */
    public static function getMembers(\WP_REST_Request $request): \WP_REST_Response
    {
        $search = $request->get_param('search');
        $letter = $request->get_param('letter');
        $page = max(1, (int) $request->get_param('page'));
        $per_page = min(100, max(1, (int) $request->get_param('per_page')));

        // Get all Lemur users
        $args = [
            'role__in' => [
                RolesManager::ROLE_BUREAU,
                RolesManager::ROLE_MEMBRE,
                RolesManager::ROLE_BACKUP,
            ],
            'orderby'  => 'meta_value',
            'meta_key' => 'first_name',
            'order'    => 'ASC',
            'number'   => $per_page,
            'paged'    => $page,
        ];

        // Search filter
        if (!empty($search)) {
            $args['meta_query'] = [
                [
                    'key'     => 'first_name',
                    'value'   => $search,
                    'compare' => 'LIKE',
                ],
            ];
        }

        // Letter filter
        if (!empty($letter) && strlen($letter) === 1) {
            $args['meta_query'] = [
                [
                    'key'     => 'first_name',
                    'value'   => '^' . $letter,
                    'compare' => 'REGEXP',
                ],
            ];
        }

        $user_query = new \WP_User_Query($args);
        $users = $user_query->get_results();

        // Format for RGPD compliance (first names only)
        $members = [];

        foreach ($users as $user) {
            // Skip users who opted out of directory
            $hide = get_user_meta($user->ID, self::META_HIDE_FROM_DIRECTORY, true);
            if ($hide) {
                continue;
            }

            $members[] = self::formatMemberForApi($user);
        }

        // Group by first letter
        $grouped = self::groupByLetter($members);

        return new \WP_REST_Response([
            'members'     => $members,
            'grouped'     => $grouped,
            'total'       => $user_query->get_total(),
            'pages'       => ceil($user_query->get_total() / $per_page),
            'current_page' => $page,
        ], 200);
    }

    /**
     * REST handler: Get members count
     *
     * @param \WP_REST_Request $request REST request
     * @return \WP_REST_Response
     */
    public static function getMembersCount(\WP_REST_Request $request): \WP_REST_Response
    {
        $args = [
            'role__in'    => [
                RolesManager::ROLE_BUREAU,
                RolesManager::ROLE_MEMBRE,
                RolesManager::ROLE_BACKUP,
            ],
            'count_total' => true,
            'fields'      => 'ID',
        ];

        $user_query = new \WP_User_Query($args);

        return new \WP_REST_Response([
            'total' => $user_query->get_total(),
        ], 200);
    }

    /**
     * Format member for API response (RGPD compliant)
     *
     * @param \WP_User $user User object
     * @return array<string, mixed>
     */
    private static function formatMemberForApi(\WP_User $user): array
    {
        // Get first name only (RGPD: data minimization)
        $first_name = get_user_meta($user->ID, 'first_name', true);

        if (empty($first_name)) {
            // Fallback: use display name but truncate to first word
            $display_name = $user->display_name;
            $parts = explode(' ', $display_name);
            $first_name = $parts[0];
        }

        // Determine role label
        $role_label = '';
        if (in_array(RolesManager::ROLE_BUREAU, $user->roles, true)) {
            $role_label = __('Bureau', 'lemur');
        }

        // Get collectifs
        $collectifs = RolesManager::getUserCollectifs($user->ID);

        // Generate obfuscated ID (prevents user enumeration)
        $obfuscated_id = self::obfuscateUserId($user->ID);

        // Generate privacy-safe avatar (no email hash exposure)
        $avatar_url = self::getPrivacySafeAvatar($first_name);

        return [
            'id'         => $obfuscated_id,
            'first_name' => sanitize_text_field($first_name),
            'initial'    => strtoupper(mb_substr($first_name, 0, 1)),
            'role'       => $role_label,
            'collectifs' => $collectifs,
            'avatar'     => $avatar_url,
        ];
    }

    /**
     * Obfuscate user ID to prevent enumeration
     *
     * @param int $user_id WordPress user ID
     * @return string Obfuscated ID
     */
    private static function obfuscateUserId(int $user_id): string
    {
        $salt = defined('NONCE_SALT') ? NONCE_SALT : 'lemur_default_salt';
        return substr(hash('sha256', $salt . $user_id), 0, 12);
    }

    /**
     * Get privacy-safe avatar URL (no email hash)
     *
     * Uses UI Avatars service with first name initial only
     *
     * @param string $first_name First name for initial
     * @return string Avatar URL
     */
    private static function getPrivacySafeAvatar(string $first_name): string
    {
        $initial = mb_substr($first_name, 0, 1);
        $initial = urlencode(strtoupper($initial));

        // UI Avatars - generates avatars from initials, no personal data
        // Colors based on Lemur theme
        return sprintf(
            'https://ui-avatars.com/api/?name=%s&size=80&background=4a7c59&color=ffffff&bold=true',
            $initial
        );
    }

    /**
     * Group members by first letter
     *
     * @param array<array<string, mixed>> $members Members array
     * @return array<string, array<array<string, mixed>>>
     */
    private static function groupByLetter(array $members): array
    {
        $grouped = [];

        foreach ($members as $member) {
            $letter = $member['initial'] ?? '#';

            if (!isset($grouped[$letter])) {
                $grouped[$letter] = [];
            }

            $grouped[$letter][] = $member;
        }

        // Sort by letter
        ksort($grouped);

        return $grouped;
    }

    /**
     * Get available letters in directory
     *
     * @return array<string>
     */
    public static function getAvailableLetters(): array
    {
        global $wpdb;

        $results = $wpdb->get_col($wpdb->prepare(
            "SELECT DISTINCT UPPER(LEFT(meta_value, 1)) as letter
             FROM {$wpdb->usermeta}
             WHERE meta_key = %s
             AND meta_value != ''
             ORDER BY letter ASC",
            'first_name'
        ));

        return array_filter($results, function ($letter) {
            return preg_match('/^[A-Z]$/', $letter);
        });
    }

    /**
     * Get all members for directory (non-paginated)
     *
     * @return array<array<string, mixed>>
     */
    public static function getAllMembers(): array
    {
        $users = get_users([
            'role__in' => [
                RolesManager::ROLE_BUREAU,
                RolesManager::ROLE_MEMBRE,
                RolesManager::ROLE_BACKUP,
            ],
            'orderby'  => 'meta_value',
            'meta_key' => 'first_name',
            'order'    => 'ASC',
        ]);

        $members = [];

        foreach ($users as $user) {
            // Skip users who opted out
            $hide = get_user_meta($user->ID, self::META_HIDE_FROM_DIRECTORY, true);
            if ($hide) {
                continue;
            }

            $members[] = self::formatMemberForApi($user);
        }

        return $members;
    }
}
