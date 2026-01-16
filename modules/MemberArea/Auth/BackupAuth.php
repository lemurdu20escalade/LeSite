<?php
/**
 * Backup Authentication
 *
 * Provides fallback authentication when Galette OAuth2 is unavailable.
 * Supports three modes: oauth2, backup, and both.
 *
 * @package Lemur\MemberArea\Auth
 */

declare(strict_types=1);

namespace Lemur\MemberArea\Auth;

use Lemur\MemberArea\Access\RolesManager;

/**
 * Backup authentication management
 */
class BackupAuth
{
    /**
     * Authentication mode constants
     */
    public const MODE_OAUTH2 = 'oauth2';
    public const MODE_BACKUP = 'backup';
    public const MODE_BOTH = 'both';

    /**
     * Option keys
     */
    public const OPTION_AUTH_MODE = 'lemur_auth_mode';
    public const OPTION_GALETTE_STATUS = 'lemur_galette_status';

    /**
     * Galette status constants
     */
    public const GALETTE_STATUS_ONLINE = 'online';
    public const GALETTE_STATUS_OFFLINE = 'offline';
    public const GALETTE_STATUS_UNKNOWN = 'unknown';

    /**
     * Initialize backup auth
     */
    public static function init(): void
    {
        // Filter authentication based on mode
        add_filter('authenticate', [self::class, 'filterAuthenticate'], 30, 3);

        // Add admin notice when in backup mode
        add_action('admin_notices', [self::class, 'adminNotice'], 10);

        // Register settings page
        add_action('admin_menu', [self::class, 'addSettingsPage'], 10);

        // Register settings
        add_action('admin_init', [self::class, 'registerSettings'], 10);
    }

    /**
     * Add settings page to admin menu
     */
    public static function addSettingsPage(): void
    {
        add_options_page(
            __('Espace Membre', 'lemur'),
            __('Espace Membre', 'lemur'),
            'manage_options',
            'lemur-member-settings',
            [self::class, 'renderSettingsPage']
        );
    }

    /**
     * Render settings page
     */
    public static function renderSettingsPage(): void
    {
        if (!current_user_can('manage_options')) {
            return;
        }

        $current_mode = self::getAuthMode();
        $galette_status = self::getGaletteStatus();
        ?>
        <div class="wrap">
            <h1><?php esc_html_e('Paramètres Espace Membre', 'lemur'); ?></h1>

            <form method="post" action="options.php">
                <?php settings_fields('lemur_member_settings'); ?>

                <table class="form-table" role="presentation">
                    <tr>
                        <th scope="row">
                            <label for="<?php echo esc_attr(self::OPTION_AUTH_MODE); ?>">
                                <?php esc_html_e('Mode d\'authentification', 'lemur'); ?>
                            </label>
                        </th>
                        <td>
                            <select name="<?php echo esc_attr(self::OPTION_AUTH_MODE); ?>" id="<?php echo esc_attr(self::OPTION_AUTH_MODE); ?>">
                                <?php foreach (self::getAvailableModes() as $mode => $label) : ?>
                                    <option value="<?php echo esc_attr($mode); ?>" <?php selected($current_mode, $mode); ?>>
                                        <?php echo esc_html($label); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <p class="description">
                                <?php esc_html_e('Choisissez le mode d\'authentification pour l\'espace membre.', 'lemur'); ?>
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php esc_html_e('Statut Galette', 'lemur'); ?></th>
                        <td>
                            <?php
                            $status_labels = [
                                self::GALETTE_STATUS_ONLINE  => __('En ligne', 'lemur'),
                                self::GALETTE_STATUS_OFFLINE => __('Hors ligne', 'lemur'),
                                self::GALETTE_STATUS_UNKNOWN => __('Inconnu', 'lemur'),
                            ];
                            $status_colors = [
                                self::GALETTE_STATUS_ONLINE  => '#00a32a',
                                self::GALETTE_STATUS_OFFLINE => '#d63638',
                                self::GALETTE_STATUS_UNKNOWN => '#dba617',
                            ];
                            ?>
                            <span style="color: <?php echo esc_attr($status_colors[$galette_status] ?? '#666'); ?>; font-weight: 600;">
                                <?php echo esc_html($status_labels[$galette_status] ?? $galette_status); ?>
                            </span>
                            <p class="description">
                                <?php esc_html_e('Ce statut est mis à jour automatiquement lors des tentatives de connexion OAuth2.', 'lemur'); ?>
                            </p>
                        </td>
                    </tr>
                </table>

                <?php submit_button(); ?>
            </form>

            <hr>

            <h2><?php esc_html_e('Informations', 'lemur'); ?></h2>
            <table class="widefat" style="max-width: 600px;">
                <tr>
                    <th><?php esc_html_e('Mode actuel', 'lemur'); ?></th>
                    <td><strong><?php echo esc_html(self::getAuthModeLabel($current_mode)); ?></strong></td>
                </tr>
                <tr>
                    <th><?php esc_html_e('Connexion WordPress native', 'lemur'); ?></th>
                    <td><?php echo self::isBackupModeActive() ? '✅ ' . esc_html__('Activée', 'lemur') : '❌ ' . esc_html__('Désactivée', 'lemur'); ?></td>
                </tr>
                <tr>
                    <th><?php esc_html_e('Connexion OAuth2 Galette', 'lemur'); ?></th>
                    <td><?php echo self::isOAuth2ModeActive() ? '✅ ' . esc_html__('Activée', 'lemur') : '❌ ' . esc_html__('Désactivée', 'lemur'); ?></td>
                </tr>
            </table>
        </div>
        <?php
    }

    /**
     * Get current authentication mode
     *
     * @return string One of MODE_OAUTH2, MODE_BACKUP, or MODE_BOTH
     */
    public static function getAuthMode(): string
    {
        $mode = get_option(self::OPTION_AUTH_MODE, self::MODE_BACKUP);

        // Validate mode
        $valid_modes = [self::MODE_OAUTH2, self::MODE_BACKUP, self::MODE_BOTH];

        if (!in_array($mode, $valid_modes, true)) {
            return self::MODE_BACKUP;
        }

        return $mode;
    }

    /**
     * Set authentication mode
     *
     * @param string $mode Authentication mode
     */
    public static function setAuthMode(string $mode): bool
    {
        $valid_modes = [self::MODE_OAUTH2, self::MODE_BACKUP, self::MODE_BOTH];

        if (!in_array($mode, $valid_modes, true)) {
            return false;
        }

        return update_option(self::OPTION_AUTH_MODE, $mode);
    }

    /**
     * Check if backup mode is active
     */
    public static function isBackupModeActive(): bool
    {
        $mode = self::getAuthMode();

        return $mode === self::MODE_BACKUP || $mode === self::MODE_BOTH;
    }

    /**
     * Check if OAuth2 mode is active
     */
    public static function isOAuth2ModeActive(): bool
    {
        $mode = self::getAuthMode();

        return $mode === self::MODE_OAUTH2 || $mode === self::MODE_BOTH;
    }

    /**
     * Filter WordPress authentication
     *
     * @param \WP_User|\WP_Error|null $user     User object or error
     * @param string                  $username Username
     * @param string                  $password Password
     * @return \WP_User|\WP_Error|null
     */
    public static function filterAuthenticate($user, string $username, string $password)
    {
        // If already authenticated, pass through
        if ($user instanceof \WP_User) {
            return $user;
        }

        // If OAuth2 only mode, block standard WP login for non-admins
        if (self::getAuthMode() === self::MODE_OAUTH2) {
            // Allow admins to still use WP login
            $attempted_user = get_user_by('login', $username);

            if ($attempted_user instanceof \WP_User) {
                // Administrators can always log in with WP credentials
                if (user_can($attempted_user, 'manage_options')) {
                    return $user;
                }

                // Block non-admin login in OAuth2-only mode
                return new \WP_Error(
                    'oauth2_required',
                    __('La connexion se fait via le compte Galette. Utilisez le bouton "Se connecter avec Galette".', 'lemur')
                );
            }
        }

        return $user;
    }

    /**
     * Display admin notice when in backup mode
     */
    public static function adminNotice(): void
    {
        // Only show to administrators
        if (!current_user_can('manage_options')) {
            return;
        }

        $mode = self::getAuthMode();

        if ($mode === self::MODE_BACKUP) {
            $message = sprintf(
                /* translators: %s: Settings URL */
                __('Mode backup actif : l\'authentification OAuth2 Galette est désactivée. <a href="%s">Configurer</a>', 'lemur'),
                esc_url(admin_url('options-general.php?page=lemur-member-settings'))
            );

            printf(
                '<div class="notice notice-warning"><p>%s</p></div>',
                wp_kses($message, ['a' => ['href' => []]])
            );
        }

        // Check Galette status
        $galette_status = get_option(self::OPTION_GALETTE_STATUS, self::GALETTE_STATUS_UNKNOWN);

        if ($galette_status === self::GALETTE_STATUS_OFFLINE && $mode !== self::MODE_BACKUP) {
            printf(
                '<div class="notice notice-error"><p>%s</p></div>',
                esc_html__('Galette semble inaccessible. Envisagez de passer en mode backup.', 'lemur')
            );
        }
    }

    /**
     * Register admin settings
     */
    public static function registerSettings(): void
    {
        register_setting(
            'lemur_member_settings',
            self::OPTION_AUTH_MODE,
            [
                'type'              => 'string',
                'sanitize_callback' => [self::class, 'sanitizeAuthMode'],
                'default'           => self::MODE_BACKUP,
            ]
        );
    }

    /**
     * Sanitize auth mode option
     *
     * @param mixed $value Input value
     */
    public static function sanitizeAuthMode($value): string
    {
        $valid_modes = [self::MODE_OAUTH2, self::MODE_BACKUP, self::MODE_BOTH];

        if (is_string($value) && in_array($value, $valid_modes, true)) {
            return $value;
        }

        return self::MODE_BACKUP;
    }

    /**
     * Create a backup user account
     *
     * @param string $username Username
     * @param string $email    Email address
     * @param string $password Password
     * @param string $first_name First name
     * @return int|\WP_Error User ID or error
     */
    public static function createBackupUser(
        string $username,
        string $email,
        string $password,
        string $first_name = ''
    ) {
        // Validate inputs
        $username = sanitize_user($username);
        $email = sanitize_email($email);

        if (empty($username) || empty($email) || empty($password)) {
            return new \WP_Error('missing_fields', __('Tous les champs sont requis.', 'lemur'));
        }

        if (strlen($password) < 12) {
            return new \WP_Error('weak_password', __('Le mot de passe doit contenir au moins 12 caractères.', 'lemur'));
        }

        // Create user
        $user_id = wp_create_user($username, $password, $email);

        if (is_wp_error($user_id)) {
            return $user_id;
        }

        // Set user role to backup member
        $user = get_user_by('id', $user_id);

        if ($user instanceof \WP_User) {
            $user->set_role(RolesManager::ROLE_BACKUP);

            // Set first name if provided
            if (!empty($first_name)) {
                update_user_meta($user_id, 'first_name', sanitize_text_field($first_name));
                wp_update_user([
                    'ID'           => $user_id,
                    'display_name' => sanitize_text_field($first_name),
                ]);
            }
        }

        return $user_id;
    }

    /**
     * Update Galette status
     *
     * @param string $status Status constant
     */
    public static function setGaletteStatus(string $status): void
    {
        $valid_statuses = [
            self::GALETTE_STATUS_ONLINE,
            self::GALETTE_STATUS_OFFLINE,
            self::GALETTE_STATUS_UNKNOWN,
        ];

        if (in_array($status, $valid_statuses, true)) {
            update_option(self::OPTION_GALETTE_STATUS, $status);
        }
    }

    /**
     * Get Galette status
     */
    public static function getGaletteStatus(): string
    {
        return get_option(self::OPTION_GALETTE_STATUS, self::GALETTE_STATUS_UNKNOWN);
    }

    /**
     * Get authentication mode label
     *
     * @param string $mode Mode constant
     */
    public static function getAuthModeLabel(string $mode): string
    {
        $labels = [
            self::MODE_OAUTH2 => __('OAuth2 Galette uniquement', 'lemur'),
            self::MODE_BACKUP => __('WordPress natif (backup)', 'lemur'),
            self::MODE_BOTH   => __('OAuth2 + WordPress natif', 'lemur'),
        ];

        return $labels[$mode] ?? $mode;
    }

    /**
     * Get all available authentication modes with labels
     *
     * @return array<string, string>
     */
    public static function getAvailableModes(): array
    {
        return [
            self::MODE_BACKUP => self::getAuthModeLabel(self::MODE_BACKUP),
            self::MODE_BOTH   => self::getAuthModeLabel(self::MODE_BOTH),
            self::MODE_OAUTH2 => self::getAuthModeLabel(self::MODE_OAUTH2),
        ];
    }
}
