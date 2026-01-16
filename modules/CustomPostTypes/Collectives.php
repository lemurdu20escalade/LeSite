<?php
/**
 * CPT Collectifs
 *
 * Gestion des groupes de travail de l'association.
 *
 * @package Lemur
 */

declare(strict_types=1);

namespace Lemur\CustomPostTypes;

use Carbon_Fields\Container;
use Carbon_Fields\Field;

class Collectives
{
    public const POST_TYPE = 'collectifs';
    public const TAXONOMY = 'categorie-collectif';

    // Field keys
    public const FIELD_DESCRIPTION = 'collective_description';
    public const FIELD_OBJECTIVES = 'collective_objectives';
    public const FIELD_EMAIL = 'collective_email';
    public const FIELD_MAILING_LIST = 'collective_mailing_list';
    public const FIELD_MEETING_FREQUENCY = 'collective_meeting_frequency';
    public const FIELD_COLOR = 'collective_color';

    public static function init(): void
    {
        self::register();
        self::registerTaxonomy();

        add_action('carbon_fields_register_fields', [self::class, 'registerFields'], 10);
        add_filter('manage_' . self::POST_TYPE . '_posts_columns', [self::class, 'addAdminColumns'], 10);
        add_action('manage_' . self::POST_TYPE . '_posts_custom_column', [self::class, 'renderAdminColumns'], 10, 2);
    }

    public static function register(): void
    {
        $labels = [
            'name' => __('Collectifs', 'lemur'),
            'singular_name' => __('Collectif', 'lemur'),
            'menu_name' => __('Collectifs', 'lemur'),
            'add_new' => __('Ajouter', 'lemur'),
            'add_new_item' => __('Ajouter un collectif', 'lemur'),
            'edit_item' => __('Modifier le collectif', 'lemur'),
            'new_item' => __('Nouveau collectif', 'lemur'),
            'view_item' => __('Voir le collectif', 'lemur'),
            'search_items' => __('Rechercher un collectif', 'lemur'),
            'not_found' => __('Aucun collectif trouvé', 'lemur'),
            'not_found_in_trash' => __('Aucun collectif dans la corbeille', 'lemur'),
            'all_items' => __('Tous les collectifs', 'lemur'),
            'archives' => __('Archives des collectifs', 'lemur'),
            'featured_image' => __('Image du collectif', 'lemur'),
            'set_featured_image' => __('Définir l\'image', 'lemur'),
            'remove_featured_image' => __('Supprimer l\'image', 'lemur'),
        ];

        $args = [
            'labels' => $labels,
            'public' => true,
            'publicly_queryable' => true,
            'show_ui' => true,
            'show_in_menu' => true,
            'show_in_rest' => true,
            'query_var' => true,
            'rewrite' => [
                'slug' => 'collectif',
                'with_front' => false,
            ],
            'capability_type' => 'post',
            'has_archive' => 'collectifs',
            'hierarchical' => false,
            'menu_position' => 7,
            'menu_icon' => 'dashicons-groups',
            'supports' => ['title', 'thumbnail'],
        ];

        register_post_type(self::POST_TYPE, $args);
    }

    public static function registerTaxonomy(): void
    {
        $labels = [
            'name' => __('Catégories de collectifs', 'lemur'),
            'singular_name' => __('Catégorie', 'lemur'),
            'search_items' => __('Rechercher une catégorie', 'lemur'),
            'all_items' => __('Toutes les catégories', 'lemur'),
            'parent_item' => __('Catégorie parente', 'lemur'),
            'edit_item' => __('Modifier la catégorie', 'lemur'),
            'update_item' => __('Mettre à jour', 'lemur'),
            'add_new_item' => __('Ajouter une catégorie', 'lemur'),
            'new_item_name' => __('Nom de la catégorie', 'lemur'),
            'menu_name' => __('Catégories', 'lemur'),
        ];

        $args = [
            'labels' => $labels,
            'hierarchical' => true,
            'public' => true,
            'show_ui' => true,
            'show_admin_column' => true,
            'show_in_rest' => true,
            'rewrite' => [
                'slug' => 'categorie-collectif',
                'with_front' => false,
            ],
        ];

        register_taxonomy(self::TAXONOMY, [self::POST_TYPE], $args);

        self::insertDefaultTerms();
    }

    private static function insertDefaultTerms(): void
    {
        $default_terms = [
            'Organisation',
            'Formation',
            'Activités',
            'Communication',
            'Logistique',
        ];

        foreach ($default_terms as $term) {
            if (!term_exists($term, self::TAXONOMY)) {
                wp_insert_term($term, self::TAXONOMY);
            }
        }
    }

    public static function registerFields(): void
    {
        Container::make('post_meta', __('Informations du collectif', 'lemur'))
            ->where('post_type', '=', self::POST_TYPE)
            ->add_fields([
                Field::make('rich_text', self::FIELD_DESCRIPTION, __('Description', 'lemur'))
                    ->set_help_text(__('Présentation du collectif et de ses missions', 'lemur')),

                Field::make('textarea', self::FIELD_OBJECTIVES, __('Objectifs', 'lemur'))
                    ->set_rows(4)
                    ->set_help_text(__('Liste des objectifs (un par ligne)', 'lemur')),

                Field::make('separator', 'sep_contact', __('Contact', 'lemur')),

                Field::make('text', self::FIELD_EMAIL, __('Email de contact', 'lemur'))
                    ->set_attribute('type', 'email')
                    ->set_width(50)
                    ->set_help_text(__('Email du collectif', 'lemur')),

                Field::make('text', self::FIELD_MAILING_LIST, __('Liste de diffusion', 'lemur'))
                    ->set_width(50)
                    ->set_help_text(__('Nom de la liste (ex: collectif-sorties)', 'lemur')),

                Field::make('text', self::FIELD_MEETING_FREQUENCY, __('Fréquence des réunions', 'lemur'))
                    ->set_help_text(__('Ex: 1 fois par mois, tous les mercredis', 'lemur')),

                Field::make('separator', 'sep_display', __('Affichage', 'lemur')),

                Field::make('color', self::FIELD_COLOR, __('Couleur', 'lemur'))
                    ->set_help_text(__('Couleur associée au collectif', 'lemur')),
            ]);
    }

    /**
     * @param array<string, string> $columns
     * @return array<string, string>
     */
    public static function addAdminColumns(array $columns): array
    {
        $new_columns = [];

        foreach ($columns as $key => $value) {
            $new_columns[$key] = $value;

            if ($key === 'title') {
                $new_columns['collective_email'] = __('Contact', 'lemur');
            }
        }

        return $new_columns;
    }

    public static function renderAdminColumns(string $column, int $post_id): void
    {
        if ($column === 'collective_email') {
            $email = carbon_get_post_meta($post_id, self::FIELD_EMAIL);
            if ($email) {
                echo esc_html($email);
            }
        }
    }
}
