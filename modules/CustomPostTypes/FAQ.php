<?php
/**
 * FAQ Custom Post Type
 *
 * Manages frequently asked questions organized by category.
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
 * FAQ CPT with taxonomy and custom fields
 */
class FAQ
{
    use HasDefaultTerms;
    use HasOrderField;
    /**
     * Post type slug
     */
    public const POST_TYPE = 'faq';

    /**
     * Taxonomy slug
     */
    public const TAXONOMY = 'categorie-faq';

    /**
     * Field keys
     */
    public const FIELD_ANSWER = 'faq_answer';
    public const FIELD_ORDER = 'faq_order';

    /**
     * Initialize the FAQ CPT
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

        // Default ordering
        add_action('pre_get_posts', [self::class, 'defaultOrdering']);
    }

    /**
     * Register the post type
     */
    public static function register(): void
    {
        $labels = [
            'name'               => __('FAQ', 'lemur'),
            'singular_name'      => __('Question FAQ', 'lemur'),
            'menu_name'          => __('FAQ', 'lemur'),
            'add_new'            => __('Ajouter', 'lemur'),
            'add_new_item'       => __('Ajouter une question', 'lemur'),
            'edit_item'          => __('Modifier la question', 'lemur'),
            'new_item'           => __('Nouvelle question', 'lemur'),
            'view_item'          => __('Voir la question', 'lemur'),
            'search_items'       => __('Rechercher une question', 'lemur'),
            'not_found'          => __('Aucune question trouvée', 'lemur'),
            'not_found_in_trash' => __('Aucune question dans la corbeille', 'lemur'),
            'all_items'          => __('Toutes les questions', 'lemur'),
        ];

        $args = [
            'labels'              => $labels,
            'public'              => false,
            'publicly_queryable'  => false,
            'show_ui'             => true,
            'show_in_menu'        => true,
            'show_in_rest'        => true,
            'query_var'           => false,
            'rewrite'             => false,
            'capability_type'     => 'post',
            'has_archive'         => false,
            'hierarchical'        => false,
            'menu_position'       => 8,
            'menu_icon'           => 'dashicons-editor-help',
            'supports'            => ['title'],
        ];

        register_post_type(self::POST_TYPE, $args);
    }

    /**
     * Register the FAQ category taxonomy
     */
    public static function registerTaxonomy(): void
    {
        $labels = [
            'name'          => __('Catégories FAQ', 'lemur'),
            'singular_name' => __('Catégorie', 'lemur'),
            'search_items'  => __('Rechercher une catégorie', 'lemur'),
            'all_items'     => __('Toutes les catégories', 'lemur'),
            'parent_item'   => __('Catégorie parente', 'lemur'),
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
            'show_in_rest'      => true,
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
            'Inscription',
            'Matériel',
            'Créneaux',
            'Tarifs',
            'Sorties',
        ]);
    }

    /**
     * Register Carbon Fields for FAQ
     */
    public static function registerFields(): void
    {
        Container::make('post_meta', __('Réponse', 'lemur'))
            ->where('post_type', '=', self::POST_TYPE)
            ->add_fields([
                Field::make('rich_text', self::FIELD_ANSWER, __('Réponse', 'lemur'))
                    ->set_help_text(__('Réponse détaillée à la question', 'lemur')),

                Field::make('select', self::FIELD_ORDER, __('Ordre d\'affichage', 'lemur'))
                    ->set_options(self::getOrderOptions())
                    ->set_default_value('10')
                    ->set_help_text(__('Plus le nombre est petit, plus la question apparaît en haut', 'lemur')),
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
            $new_columns[$key] = $value;

            if ($key === 'title') {
                $new_columns['faq_order'] = __('Ordre', 'lemur');
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
        if ($column === 'faq_order') {
            $order = carbon_get_post_meta($post_id, self::FIELD_ORDER);
            echo esc_html($order ?: '10');
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
        $columns['faq_order'] = 'faq_order';
        return $columns;
    }

    /**
     * Set default ordering in admin
     *
     * @param \WP_Query $query The query object
     */
    public static function defaultOrdering(\WP_Query $query): void
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

        // Sort by order if requested
        if ($orderby === 'faq_order') {
            $query->set('meta_key', $meta_key);
            $query->set('orderby', 'meta_value_num');
        }

        // Default ordering - but don't filter out posts without meta
        if (!$orderby) {
            $query->set('orderby', 'date');
            $query->set('order', 'DESC');
        }
    }

    /**
     * Get FAQ answer
     *
     * @param int $post_id FAQ post ID
     * @return string
     */
    public static function getAnswer(int $post_id): string
    {
        return carbon_get_post_meta($post_id, self::FIELD_ANSWER) ?: '';
    }
}
