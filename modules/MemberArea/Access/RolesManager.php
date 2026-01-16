<?php
/**
 * Roles Manager for Member Area
 *
 * Manages custom WordPress roles for Lemur members.
 * Maps Galette groups to WordPress roles.
 *
 * @package Lemur\MemberArea\Access
 */

declare(strict_types=1);

namespace Lemur\MemberArea\Access;

/**
 * WordPress roles management for member area
 */
class RolesManager
{
    /**
     * WordPress role slugs
     */
    public const ROLE_BUREAU = 'lemur_bureau';
    public const ROLE_MEMBRE = 'lemur_membre';
    public const ROLE_BACKUP = 'lemur_backup_member';

    /**
     * Galette group to WordPress role mapping
     */
    public const GALETTE_MAPPING = [
        'admin'    => self::ROLE_BUREAU,
        'staff'    => self::ROLE_BUREAU,
        'bureau'   => self::ROLE_BUREAU,
        'member'   => self::ROLE_MEMBRE,
        'cotisant' => self::ROLE_MEMBRE,
        'adherent' => self::ROLE_MEMBRE,
    ];

    /**
     * User meta key for storing Galette collectifs
     */
    public const META_COLLECTIFS = 'lemur_collectifs';

    /**
     * User meta key for Galette ID
     */
    public const META_GALETTE_ID = 'galette_id';

    /**
     * User meta key for last Galette sync
     */
    public const META_LAST_SYNC = 'lemur_last_galette_sync';

    /**
     * Whether roles have been registered this request
     */
    private static bool $rolesRegistered = false;

    /**
     * Initialize the roles manager
     */
    public static function init(): void
    {
        // Register roles on theme activation
        add_action('after_switch_theme', [self::class, 'registerRoles'], 10);

        // Also ensure roles exist on every load (in case of manual DB changes)
        add_action('init', [self::class, 'ensureRolesExist'], 5);

        // Hook for Galette user sync
        add_action('lemur_galette_user_sync', [self::class, 'syncGaletteRole'], 10, 2);
    }

    /**
     * Register custom WordPress roles
     */
    public static function registerRoles(): void
    {
        if (self::$rolesRegistered) {
            return;
        }

        // Bureau (admin members) - has extended capabilities
        add_role(self::ROLE_BUREAU, __('Bureau Lemur', 'lemur'), [
            'read'                           => true,
            Capabilities::CAP_READ_MEMBER_AREA => true,
            Capabilities::CAP_EDIT_TODOS       => true,
            Capabilities::CAP_EDIT_DOCUMENTS   => true,
            Capabilities::CAP_MANAGE_MEMBERS   => true,
            Capabilities::CAP_VIEW_AUDIT_LOG   => true,
            'upload_files'                   => true,
        ]);

        // Standard member - read access only
        add_role(self::ROLE_MEMBRE, __('Membre Lemur', 'lemur'), [
            'read'                           => true,
            Capabilities::CAP_READ_MEMBER_AREA => true,
        ]);

        // Backup member - for offline/fallback mode
        add_role(self::ROLE_BACKUP, __('Membre backup', 'lemur'), [
            'read'                           => true,
            Capabilities::CAP_READ_MEMBER_AREA => true,
        ]);

        self::$rolesRegistered = true;
    }

    /**
     * Ensure roles exist (called on every init)
     */
    public static function ensureRolesExist(): void
    {
        $bureau = get_role(self::ROLE_BUREAU);

        if ($bureau === null) {
            self::registerRoles();
        }
    }

    /**
     * Remove custom roles (for cleanup/uninstall)
     */
    public static function removeRoles(): void
    {
        remove_role(self::ROLE_BUREAU);
        remove_role(self::ROLE_MEMBRE);
        remove_role(self::ROLE_BACKUP);

        self::$rolesRegistered = false;
    }

    /**
     * Sync WordPress role based on Galette groups
     *
     * @param int      $user_id      WordPress user ID
     * @param array<string> $galette_groups Groups from Galette OAuth response
     */
    public static function syncGaletteRole(int $user_id, array $galette_groups): void
    {
        $user = get_user_by('id', $user_id);

        if (!$user instanceof \WP_User) {
            return;
        }

        // Determine the highest role based on Galette groups
        $new_role = self::determineRoleFromGroups($galette_groups);

        // Only update if user doesn't already have this role
        if (!in_array($new_role, $user->roles, true)) {
            // Remove old Lemur roles
            $user->remove_role(self::ROLE_BUREAU);
            $user->remove_role(self::ROLE_MEMBRE);
            $user->remove_role(self::ROLE_BACKUP);

            // Add new role
            $user->add_role($new_role);
        }

        // Store collectifs as user meta
        $collectifs = self::extractCollectifs($galette_groups);
        update_user_meta($user_id, self::META_COLLECTIFS, $collectifs);

        // Update last sync timestamp
        update_user_meta($user_id, self::META_LAST_SYNC, current_time('mysql'));
    }

    /**
     * Determine WordPress role from Galette groups
     *
     * @param array<string> $groups Galette groups
     * @return string WordPress role slug
     */
    public static function determineRoleFromGroups(array $groups): string
    {
        // Check for bureau/admin groups first (highest privilege)
        foreach ($groups as $group) {
            $normalized = strtolower(trim($group));

            if (isset(self::GALETTE_MAPPING[$normalized])) {
                $mapped_role = self::GALETTE_MAPPING[$normalized];

                if ($mapped_role === self::ROLE_BUREAU) {
                    return self::ROLE_BUREAU;
                }
            }
        }

        // Check for member groups
        foreach ($groups as $group) {
            $normalized = strtolower(trim($group));

            if (isset(self::GALETTE_MAPPING[$normalized])) {
                return self::GALETTE_MAPPING[$normalized];
            }
        }

        // Default to standard member if any groups exist
        if (!empty($groups)) {
            return self::ROLE_MEMBRE;
        }

        // Fallback to backup role
        return self::ROLE_BACKUP;
    }

    /**
     * Extract collectif names from Galette groups
     *
     * @param array<string> $groups All Galette groups
     * @return array<string> Collectif names only
     */
    public static function extractCollectifs(array $groups): array
    {
        $collectifs = [];

        foreach ($groups as $group) {
            // Collectifs are prefixed with 'collectif-' in Galette
            if (str_starts_with(strtolower($group), 'collectif-')) {
                $collectifs[] = sanitize_text_field($group);
            }

            // Also include 'encadrants' group as a collectif
            if (strtolower($group) === 'encadrants') {
                $collectifs[] = 'encadrants';
            }
        }

        return array_unique($collectifs);
    }

    /**
     * Get user's collectifs
     *
     * @param int|null $user_id User ID or null for current user
     * @return array<string>
     */
    public static function getUserCollectifs(?int $user_id = null): array
    {
        $user_id = $user_id ?? get_current_user_id();

        if ($user_id === 0) {
            return [];
        }

        $collectifs = get_user_meta($user_id, self::META_COLLECTIFS, true);

        return is_array($collectifs) ? $collectifs : [];
    }

    /**
     * Check if user is in a specific collectif
     *
     * @param string   $collectif Collectif name
     * @param int|null $user_id   User ID or null for current user
     */
    public static function userInCollectif(string $collectif, ?int $user_id = null): bool
    {
        $collectifs = self::getUserCollectifs($user_id);

        return in_array($collectif, $collectifs, true);
    }

    /**
     * Get all Lemur users
     *
     * @return array<\WP_User>
     */
    public static function getAllLemurUsers(): array
    {
        return get_users([
            'role__in' => [
                self::ROLE_BUREAU,
                self::ROLE_MEMBRE,
                self::ROLE_BACKUP,
            ],
            'orderby'  => 'display_name',
            'order'    => 'ASC',
        ]);
    }

    /**
     * Check if a user has any Lemur role
     *
     * @param int|null $user_id User ID or null for current user
     */
    public static function isLemurUser(?int $user_id = null): bool
    {
        $user_id = $user_id ?? get_current_user_id();

        if ($user_id === 0) {
            return false;
        }

        $user = get_user_by('id', $user_id);

        if (!$user instanceof \WP_User) {
            return false;
        }

        $lemur_roles = [self::ROLE_BUREAU, self::ROLE_MEMBRE, self::ROLE_BACKUP];

        foreach ($user->roles as $role) {
            if (in_array($role, $lemur_roles, true)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if user is bureau member
     *
     * @param int|null $user_id User ID or null for current user
     */
    public static function isBureau(?int $user_id = null): bool
    {
        $user_id = $user_id ?? get_current_user_id();

        if ($user_id === 0) {
            return false;
        }

        return user_can($user_id, Capabilities::CAP_MANAGE_MEMBERS);
    }
}
