<?php
/**
 * Custom Capabilities for Member Area
 *
 * Defines and manages custom WordPress capabilities for the member area.
 *
 * @package Lemur\MemberArea\Access
 */

declare(strict_types=1);

namespace Lemur\MemberArea\Access;

/**
 * Custom capabilities management
 */
class Capabilities
{
    /**
     * Capability constants
     */
    public const CAP_READ_MEMBER_AREA = 'read_member_area';
    public const CAP_EDIT_TODOS = 'edit_lemur_todos';
    public const CAP_EDIT_DOCUMENTS = 'edit_lemur_documents';
    public const CAP_MANAGE_MEMBERS = 'manage_lemur_members';
    public const CAP_VIEW_AUDIT_LOG = 'view_lemur_audit_log';

    /**
     * Initialize capabilities
     */
    public static function init(): void
    {
        // Grant capabilities to administrator on init (not just admin_init)
        // This ensures capabilities are available immediately
        add_action('init', [self::class, 'grantToAdministrator'], 5);

        // Also on theme switch/activation
        add_action('after_switch_theme', [self::class, 'grantToAdministrator'], 10);

        // Filter capabilities for custom post types
        add_filter('map_meta_cap', [self::class, 'mapMetaCap'], 10, 4);
    }

    /**
     * Grant all Lemur capabilities to administrators
     */
    public static function grantToAdministrator(): void
    {
        $admin = get_role('administrator');

        if ($admin === null) {
            return;
        }

        // Lemur custom capabilities
        $capabilities = [
            self::CAP_READ_MEMBER_AREA,
            self::CAP_EDIT_TODOS,
            self::CAP_EDIT_DOCUMENTS,
            self::CAP_MANAGE_MEMBERS,
            self::CAP_VIEW_AUDIT_LOG,
        ];

        // CPT capabilities for Tasks (lemur_task)
        $task_caps = [
            'edit_lemur_task',
            'read_lemur_task',
            'delete_lemur_task',
            'edit_lemur_tasks',
            'edit_others_lemur_tasks',
            'publish_lemur_tasks',
            'read_private_lemur_tasks',
            'delete_lemur_tasks',
            'delete_private_lemur_tasks',
            'delete_published_lemur_tasks',
            'delete_others_lemur_tasks',
            'edit_private_lemur_tasks',
            'edit_published_lemur_tasks',
        ];

        // CPT capabilities for Documents (lemur_document)
        $document_caps = [
            'edit_lemur_document',
            'read_lemur_document',
            'delete_lemur_document',
            'edit_lemur_documents',
            'edit_others_lemur_documents',
            'publish_lemur_documents',
            'read_private_lemur_documents',
            'delete_lemur_documents',
            'delete_private_lemur_documents',
            'delete_published_lemur_documents',
            'delete_others_lemur_documents',
            'edit_private_lemur_documents',
            'edit_published_lemur_documents',
        ];

        $all_caps = array_merge($capabilities, $task_caps, $document_caps);

        foreach ($all_caps as $cap) {
            if (!$admin->has_cap($cap)) {
                $admin->add_cap($cap);
            }
        }
    }

    /**
     * Map meta capabilities to primitive capabilities
     *
     * @param array<string> $caps    Required capabilities
     * @param string        $cap     Capability being checked
     * @param int           $user_id User ID
     * @param mixed[]       $args    Additional arguments
     * @return array<string>
     */
    public static function mapMetaCap(array $caps, string $cap, int $user_id, array $args): array
    {
        // Map document capabilities
        if ($cap === 'edit_lemur_document' || $cap === 'delete_lemur_document') {
            $caps = [self::CAP_EDIT_DOCUMENTS];
        }

        // Map task capabilities
        if ($cap === 'edit_lemur_task' || $cap === 'delete_lemur_task') {
            $caps = [self::CAP_EDIT_TODOS];
        }

        return $caps;
    }

    /**
     * Check if current user can access member area
     *
     * @param int|null $user_id User ID or null for current user
     */
    public static function canAccessMemberArea(?int $user_id = null): bool
    {
        $user_id = $user_id ?? get_current_user_id();

        if ($user_id === 0) {
            return false;
        }

        // Administrators always have access
        if (user_can($user_id, 'manage_options')) {
            return true;
        }

        return user_can($user_id, self::CAP_READ_MEMBER_AREA);
    }

    /**
     * Check if current user can edit todos
     *
     * @param int|null $user_id User ID or null for current user
     */
    public static function canEditTodos(?int $user_id = null): bool
    {
        $user_id = $user_id ?? get_current_user_id();

        if ($user_id === 0) {
            return false;
        }

        return user_can($user_id, self::CAP_EDIT_TODOS);
    }

    /**
     * Check if current user can edit documents
     *
     * @param int|null $user_id User ID or null for current user
     */
    public static function canEditDocuments(?int $user_id = null): bool
    {
        $user_id = $user_id ?? get_current_user_id();

        if ($user_id === 0) {
            return false;
        }

        return user_can($user_id, self::CAP_EDIT_DOCUMENTS);
    }

    /**
     * Check if current user can manage members
     *
     * @param int|null $user_id User ID or null for current user
     */
    public static function canManageMembers(?int $user_id = null): bool
    {
        $user_id = $user_id ?? get_current_user_id();

        if ($user_id === 0) {
            return false;
        }

        return user_can($user_id, self::CAP_MANAGE_MEMBERS);
    }

    /**
     * Check if current user is bureau (convenience alias)
     *
     * @param int|null $user_id User ID or null for current user
     */
    public static function isBureau(?int $user_id = null): bool
    {
        return self::canManageMembers($user_id);
    }

    /**
     * Check if current user can view audit log
     *
     * @param int|null $user_id User ID or null for current user
     */
    public static function canViewAuditLog(?int $user_id = null): bool
    {
        $user_id = $user_id ?? get_current_user_id();

        if ($user_id === 0) {
            return false;
        }

        return user_can($user_id, self::CAP_VIEW_AUDIT_LOG);
    }

    /**
     * Get all Lemur capabilities
     *
     * @return array<string>
     */
    public static function getAllCapabilities(): array
    {
        return [
            self::CAP_READ_MEMBER_AREA,
            self::CAP_EDIT_TODOS,
            self::CAP_EDIT_DOCUMENTS,
            self::CAP_MANAGE_MEMBERS,
            self::CAP_VIEW_AUDIT_LOG,
        ];
    }
}
