<?php
/**
 * Members Custom Post Type
 *
 * Manages team members: board, instructors, volunteers.
 *
 * @package Lemur\CustomPostTypes
 */

declare(strict_types=1);

namespace Lemur\CustomPostTypes;

use Carbon_Fields\Container;
use Carbon_Fields\Field;
use Lemur\CustomPostTypes\Traits\HasDefaultTerms;
use Lemur\CustomPostTypes\Traits\HasOrderField;

/**
 * Members CPT with taxonomy and custom fields
 */
class Members
{
    use HasDefaultTerms;
    use HasOrderField;
    /**
     * Post type slug
     */
    public const POST_TYPE = 'membres';

    /**
     * Taxonomy slug
     */
    public const TAXONOMY = 'role-membre';

    /**
     * Field keys
     */
    public const FIELD_FUNCTION = 'member_function';
    public const FIELD_EMAIL = 'member_email';
    public const FIELD_PHONE = 'member_phone';
    public const FIELD_BIO = 'member_bio';
    public const FIELD_ORDER = 'member_order';
    public const FIELD_CERTIFICATIONS = 'member_certifications';
    public const FIELD_SINCE = 'member_since';

    /**
     * Initialize the Members CPT
     */
    public static function init(): void
    {
        add_action('init', [self::class, 'register']);
        add_action('init', [self::class, 'registerTaxonomy']);
        add_action('carbon_fields_register_fields', [self::class, 'registerFields']);

        // Admin columns
        add_filter('manage_' . self::POST_TYPE . '_posts_columns', [self::class, 'addAdminColumns']);
        add_action('manage_' . self::POST_TYPE . '_posts_custom_column', [self::class, 'renderAdminColumns'], 10, 2);
        add_filter('manage_edit-' . self::POST_TYPE . '_sortable_columns', [self::class, 'sortableColumns']);

        // Order by custom field in admin
        add_action('pre_get_posts', [self::class, 'orderByCustomField']);
    }

    /**
     * Register the post type
     */
    public static function register(): void
    {
        $labels = [
            'name'                  => __('Membres', 'lemur'),
            'singular_name'         => __('Membre', 'lemur'),
            'menu_name'             => __('Équipe', 'lemur'),
            'add_new'               => __('Ajouter', 'lemur'),
            'add_new_item'          => __('Ajouter un membre', 'lemur'),
            'edit_item'             => __('Modifier le membre', 'lemur'),
            'new_item'              => __('Nouveau membre', 'lemur'),
            'view_item'             => __('Voir le membre', 'lemur'),
            'search_items'          => __('Rechercher un membre', 'lemur'),
            'not_found'             => __('Aucun membre trouvé', 'lemur'),
            'not_found_in_trash'    => __('Aucun membre dans la corbeille', 'lemur'),
            'all_items'             => __('Tous les membres', 'lemur'),
            'featured_image'        => __('Photo du membre', 'lemur'),
            'set_featured_image'    => __('Définir la photo', 'lemur'),
            'remove_featured_image' => __('Supprimer la photo', 'lemur'),
        ];

        $args = [
            'labels'              => $labels,
            'public'              => true,
            'publicly_queryable'  => false, // No single page
            'show_ui'             => true,
            'show_in_menu'        => true,
            'show_in_rest'        => false, // Protect member data from REST API
            'query_var'           => false,
            'rewrite'             => false,
            'capability_type'     => 'post',
            'has_archive'         => false,
            'hierarchical'        => false,
            'menu_position'       => 6,
            'menu_icon'           => 'dashicons-groups',
            'supports'            => ['title', 'thumbnail'],
        ];

        register_post_type(self::POST_TYPE, $args);
    }

    /**
     * Register the member role taxonomy
     */
    public static function registerTaxonomy(): void
    {
        $labels = [
            'name'          => __('Rôles', 'lemur'),
            'singular_name' => __('Rôle', 'lemur'),
            'search_items'  => __('Rechercher un rôle', 'lemur'),
            'all_items'     => __('Tous les rôles', 'lemur'),
            'edit_item'     => __('Modifier le rôle', 'lemur'),
            'update_item'   => __('Mettre à jour', 'lemur'),
            'add_new_item'  => __('Ajouter un rôle', 'lemur'),
            'new_item_name' => __('Nom du rôle', 'lemur'),
            'menu_name'     => __('Rôles', 'lemur'),
        ];

        $args = [
            'labels'            => $labels,
            'hierarchical'      => false, // Tags, not categories
            'public'            => false,
            'show_ui'           => true,
            'show_admin_column' => true,
            'show_in_rest'      => false,
            'rewrite'           => false,
        ];

        register_taxonomy(self::TAXONOMY, [self::POST_TYPE], $args);

        // Add default terms
        self::insertDefaultTerms();
    }

    /**
     * Insert default taxonomy terms
     */
    private static function insertDefaultTerms(): void
    {
        self::insertTermsOnce(self::TAXONOMY, [
            'Bureau',
            'Encadrant',
            'Bénévole',
            'Fondateur',
        ]);
    }

    /**
     * Register Carbon Fields for members
     */
    public static function registerFields(): void
    {
        Container::make('post_meta', __('Informations du membre', 'lemur'))
            ->where('post_type', '=', self::POST_TYPE)
            ->add_fields([
                Field::make('text', self::FIELD_FUNCTION, __('Fonction', 'lemur'))
                    ->set_attribute('placeholder', 'Président, Encadrant SAE...')
                    ->set_width(60),

                Field::make('select', self::FIELD_ORDER, __('Ordre', 'lemur'))
                    ->set_options(self::getOrderOptions())
                    ->set_default_value('10')
                    ->set_width(20)
                    ->set_help_text(__('Tri sur la page', 'lemur')),

                Field::make('text', self::FIELD_SINCE, __('Depuis', 'lemur'))
                    ->set_attribute('placeholder', date('Y'))
                    ->set_width(20),

                Field::make('text', self::FIELD_EMAIL, __('Email', 'lemur'))
                    ->set_attribute('placeholder', 'prenom@example.org')
                    ->set_attribute('type', 'email')
                    ->set_width(50),

                Field::make('text', self::FIELD_PHONE, __('Téléphone', 'lemur'))
                    ->set_attribute('placeholder', '06 12 34 56 78')
                    ->set_width(50),

                Field::make('rich_text', self::FIELD_BIO, __('Biographie', 'lemur'))
                    ->set_rows(4),

                Field::make('text', self::FIELD_CERTIFICATIONS, __('Certifications / Diplômes', 'lemur'))
                    ->set_attribute('placeholder', 'BEES, Initiateur FFME...'),
            ]);
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
            if ($key === 'title') {
                $new_columns['member_photo'] = __('Photo', 'lemur');
            }

            $new_columns[$key] = $value;

            if ($key === 'title') {
                $new_columns['member_function'] = __('Fonction', 'lemur');
                $new_columns['member_order'] = __('Ordre', 'lemur');
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
            case 'member_photo':
                $thumbnail = get_the_post_thumbnail($post_id, [50, 50]);
                if ($thumbnail) {
                    echo $thumbnail;
                } else {
                    echo '<span class="dashicons dashicons-admin-users" style="font-size:32px;color:#ccc;"></span>';
                }
                break;

            case 'member_function':
                $function = carbon_get_post_meta($post_id, self::FIELD_FUNCTION);
                echo esc_html($function ?: '—');
                break;

            case 'member_order':
                $order = carbon_get_post_meta($post_id, self::FIELD_ORDER);
                echo esc_html($order ?: '10');
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
        $columns['member_order'] = 'member_order';
        return $columns;
    }

    /**
     * Order by custom field in admin
     *
     * @param \WP_Query $query The query object
     */
    public static function orderByCustomField(\WP_Query $query): void
    {
        if (!is_admin() || !$query->is_main_query()) {
            return;
        }

        if ($query->get('post_type') !== self::POST_TYPE) {
            return;
        }

        $orderby = $query->get('orderby');

        // Carbon Fields stores meta with underscore prefix
        $meta_key = '_' . self::FIELD_ORDER;

        if ($orderby === 'member_order') {
            $query->set('meta_key', $meta_key);
            $query->set('orderby', 'meta_value_num');
        }

        // Default ordering - don't filter out posts without meta
        if (!$orderby) {
            $query->set('orderby', 'title');
            $query->set('order', 'ASC');
        }
    }

    /**
     * Get member meta data
     *
     * @param int $post_id Member post ID
     * @return array<string, mixed>
     */
    public static function getMemberMeta(int $post_id): array
    {
        return [
            'name'           => get_the_title($post_id),
            'function'       => carbon_get_post_meta($post_id, self::FIELD_FUNCTION),
            'email'          => carbon_get_post_meta($post_id, self::FIELD_EMAIL),
            'phone'          => carbon_get_post_meta($post_id, self::FIELD_PHONE),
            'bio'            => carbon_get_post_meta($post_id, self::FIELD_BIO),
            'order'          => carbon_get_post_meta($post_id, self::FIELD_ORDER),
            'certifications' => carbon_get_post_meta($post_id, self::FIELD_CERTIFICATIONS),
            'since'          => carbon_get_post_meta($post_id, self::FIELD_SINCE),
            'photo_id'       => get_post_thumbnail_id($post_id),
            'photo_url'      => get_the_post_thumbnail_url($post_id, 'lemur-team'),
        ];
    }
}
