<?php
/**
 * Events Custom Post Type
 *
 * Manages climbing events: outings, competitions, trainings, social events.
 *
 * @package Lemur\CustomPostTypes
 */

declare(strict_types=1);

namespace Lemur\CustomPostTypes;

use Carbon_Fields\Container;
use Carbon_Fields\Field;
use Lemur\CustomPostTypes\Traits\HasDefaultTerms;

/**
 * Events CPT with taxonomy and custom fields
 */
class Events
{
    use HasDefaultTerms;
    /**
     * Post type slug
     */
    public const POST_TYPE = 'evenements';

    /**
     * Taxonomy slug
     */
    public const TAXONOMY = 'type-evenement';

    /**
     * Field keys
     */
    public const FIELD_DATE_START = 'event_date_start';
    public const FIELD_DATE_END = 'event_date_end';
    public const FIELD_TIME_START = 'event_time_start';
    public const FIELD_TIME_END = 'event_time_end';
    public const FIELD_LOCATION = 'event_location';
    public const FIELD_ADDRESS = 'event_address';
    public const FIELD_MAP_LINK = 'event_map_link';
    public const FIELD_REGISTRATION_LINK = 'event_registration_link';
    public const FIELD_REGISTRATION_DEADLINE = 'event_registration_deadline';
    public const FIELD_MAX_PARTICIPANTS = 'event_max_participants';
    public const FIELD_CURRENT_PARTICIPANTS = 'event_current_participants';
    public const FIELD_DIFFICULTY = 'event_difficulty';
    public const FIELD_PRICE = 'event_price';
    public const FIELD_EQUIPMENT = 'event_equipment';
    public const FIELD_ORGANIZER = 'event_organizer';

    /**
     * Initialize the Events CPT
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

        // Modify archive query
        add_action('pre_get_posts', [self::class, 'modifyArchiveQuery']);

        // Clear cache on event save/update/delete
        add_action('save_post_' . self::POST_TYPE, [self::class, 'clearEventsCache'], 10);
        add_action('deleted_post', [self::class, 'clearEventsCacheOnDelete'], 10, 2);
    }

    /**
     * Clear events transient cache
     */
    public static function clearEventsCache(): void
    {
        global $wpdb;

        // Delete all upcoming events transients
        $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s",
                '_transient_lemur_upcoming_events_%'
            )
        );
        $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s",
                '_transient_timeout_lemur_upcoming_events_%'
            )
        );
    }

    /**
     * Clear cache when an event is deleted
     *
     * @param int      $post_id Post ID
     * @param \WP_Post $post    Post object
     */
    public static function clearEventsCacheOnDelete(int $post_id, \WP_Post $post): void
    {
        if ($post->post_type === self::POST_TYPE) {
            self::clearEventsCache();
        }
    }

    /**
     * Register the post type
     */
    public static function register(): void
    {
        $labels = [
            'name'                  => __('Événements', 'lemur'),
            'singular_name'         => __('Événement', 'lemur'),
            'menu_name'             => __('Événements', 'lemur'),
            'add_new'               => __('Ajouter', 'lemur'),
            'add_new_item'          => __('Ajouter un événement', 'lemur'),
            'edit_item'             => __('Modifier l\'événement', 'lemur'),
            'new_item'              => __('Nouvel événement', 'lemur'),
            'view_item'             => __('Voir l\'événement', 'lemur'),
            'view_items'            => __('Voir les événements', 'lemur'),
            'search_items'          => __('Rechercher un événement', 'lemur'),
            'not_found'             => __('Aucun événement trouvé', 'lemur'),
            'not_found_in_trash'    => __('Aucun événement dans la corbeille', 'lemur'),
            'all_items'             => __('Tous les événements', 'lemur'),
            'archives'              => __('Archives des événements', 'lemur'),
            'attributes'            => __('Attributs', 'lemur'),
            'insert_into_item'      => __('Insérer dans l\'événement', 'lemur'),
            'featured_image'        => __('Image de l\'événement', 'lemur'),
            'set_featured_image'    => __('Définir l\'image', 'lemur'),
            'remove_featured_image' => __('Supprimer l\'image', 'lemur'),
        ];

        $args = [
            'labels'              => $labels,
            'public'              => true,
            'publicly_queryable'  => true,
            'show_ui'             => true,
            'show_in_menu'        => true,
            'show_in_rest'        => true,
            'query_var'           => true,
            'rewrite'             => [
                'slug'       => 'evenements',
                'with_front' => false,
            ],
            'capability_type'     => 'post',
            'has_archive'         => true,
            'hierarchical'        => false,
            'menu_position'       => 5,
            'menu_icon'           => 'dashicons-calendar-alt',
            'supports'            => ['title', 'editor', 'thumbnail', 'excerpt'],
        ];

        register_post_type(self::POST_TYPE, $args);
    }

    /**
     * Register the event type taxonomy
     */
    public static function registerTaxonomy(): void
    {
        $labels = [
            'name'              => __('Types d\'événements', 'lemur'),
            'singular_name'     => __('Type d\'événement', 'lemur'),
            'search_items'      => __('Rechercher un type', 'lemur'),
            'all_items'         => __('Tous les types', 'lemur'),
            'parent_item'       => __('Type parent', 'lemur'),
            'parent_item_colon' => __('Type parent :', 'lemur'),
            'edit_item'         => __('Modifier le type', 'lemur'),
            'update_item'       => __('Mettre à jour le type', 'lemur'),
            'add_new_item'      => __('Ajouter un type', 'lemur'),
            'new_item_name'     => __('Nom du nouveau type', 'lemur'),
            'menu_name'         => __('Types', 'lemur'),
        ];

        $args = [
            'labels'            => $labels,
            'hierarchical'      => true,
            'public'            => true,
            'show_ui'           => true,
            'show_admin_column' => true,
            'show_in_rest'      => true,
            'rewrite'           => [
                'slug'       => 'type-evenement',
                'with_front' => false,
            ],
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
            'Sortie falaise',
            'Compétition',
            'Stage',
            'Formation',
            'Événement social',
        ]);
    }

    /**
     * Register Carbon Fields for events
     */
    public static function registerFields(): void
    {
        Container::make('post_meta', __('Détails de l\'événement', 'lemur'))
            ->where('post_type', '=', self::POST_TYPE)
            ->add_fields([
                // Dates & Times on same row
                Field::make('date', self::FIELD_DATE_START, __('Date début', 'lemur'))
                    ->set_storage_format('Y-m-d')
                    ->set_width(25)
                    ->set_required(true),

                Field::make('date', self::FIELD_DATE_END, __('Date fin', 'lemur'))
                    ->set_storage_format('Y-m-d')
                    ->set_width(25)
                    ->set_help_text(__('Si plusieurs jours', 'lemur')),

                Field::make('time', self::FIELD_TIME_START, __('Heure début', 'lemur'))
                    ->set_width(25),

                Field::make('time', self::FIELD_TIME_END, __('Heure fin', 'lemur'))
                    ->set_width(25),

                // Location
                Field::make('separator', 'sep_location', __('Lieu', 'lemur')),

                Field::make('text', self::FIELD_LOCATION, __('Nom du lieu', 'lemur'))
                    ->set_attribute('placeholder', 'Fontainebleau, Salle Antrebloc...')
                    ->set_width(40),

                Field::make('text', self::FIELD_ADDRESS, __('Adresse', 'lemur'))
                    ->set_width(40),

                Field::make('text', self::FIELD_MAP_LINK, __('Carte', 'lemur'))
                    ->set_attribute('placeholder', 'https://maps.google.com/...')
                    ->set_width(20),

                // Registration section
                Field::make('separator', 'sep_registration', __('Inscription', 'lemur')),

                Field::make('text', self::FIELD_REGISTRATION_LINK, __('Lien inscription', 'lemur'))
                    ->set_attribute('placeholder', 'https://...')
                    ->set_width(40),

                Field::make('date', self::FIELD_REGISTRATION_DEADLINE, __('Date limite', 'lemur'))
                    ->set_storage_format('Y-m-d')
                    ->set_width(20),

                Field::make('select', self::FIELD_MAX_PARTICIPANTS, __('Places max', 'lemur'))
                    ->set_options(self::getParticipantOptions())
                    ->set_width(20),

                Field::make('select', self::FIELD_CURRENT_PARTICIPANTS, __('Inscrits', 'lemur'))
                    ->set_options(self::getParticipantOptions())
                    ->set_width(20),

                // Details section
                Field::make('separator', 'sep_details', __('Infos pratiques', 'lemur')),

                Field::make('select', self::FIELD_DIFFICULTY, __('Niveau', 'lemur'))
                    ->set_options([
                        ''             => __('Tous niveaux', 'lemur'),
                        'beginner'     => __('Débutant', 'lemur'),
                        'intermediate' => __('Intermédiaire', 'lemur'),
                        'advanced'     => __('Confirmé', 'lemur'),
                        'expert'       => __('Expert', 'lemur'),
                    ])
                    ->set_width(25),

                Field::make('text', self::FIELD_PRICE, __('Tarif', 'lemur'))
                    ->set_attribute('placeholder', 'Gratuit, 15€, Prix libre...')
                    ->set_width(25),

                Field::make('textarea', self::FIELD_EQUIPMENT, __('Matériel à prévoir', 'lemur'))
                    ->set_rows(2)
                    ->set_width(50),

                Field::make('association', self::FIELD_ORGANIZER, __('Organisateur(s)', 'lemur'))
                    ->set_types([
                        ['type' => 'post', 'post_type' => 'membres'],
                    ]),
            ]);
    }

    /**
     * Get participant count options for select fields
     *
     * @return array<string, string>
     */
    private static function getParticipantOptions(): array
    {
        $options = ['' => __('Non défini', 'lemur')];

        for ($i = 1; $i <= 50; $i++) {
            $options[(string) $i] = (string) $i;
        }

        // Add larger options
        $options['60'] = '60';
        $options['80'] = '80';
        $options['100'] = '100';
        $options['150'] = '150';
        $options['200'] = '200';

        return $options;
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
                $new_columns['event_date'] = __('Date', 'lemur');
                $new_columns['event_location'] = __('Lieu', 'lemur');
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
            case 'event_date':
                $date = carbon_get_post_meta($post_id, self::FIELD_DATE_START);
                if ($date) {
                    echo esc_html(date_i18n('j F Y', strtotime($date)));
                } else {
                    echo '—';
                }
                break;

            case 'event_location':
                $location = carbon_get_post_meta($post_id, self::FIELD_LOCATION);
                echo esc_html($location ?: '—');
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
        $columns['event_date'] = 'event_date';
        return $columns;
    }

    /**
     * Modify archive query to sort by event date
     *
     * Handles both frontend archive sorting and admin column sorting.
     *
     * @param \WP_Query $query The query object
     */
    public static function modifyArchiveQuery(\WP_Query $query): void
    {
        if (!$query->is_main_query()) {
            return;
        }

        // Carbon Fields stores meta with underscore prefix
        $meta_key = '_' . self::FIELD_DATE_START;

        // Admin sorting by event_date column
        if (is_admin() && $query->get('post_type') === self::POST_TYPE) {
            if ($query->get('orderby') === 'event_date') {
                $query->set('meta_key', $meta_key);
                $query->set('orderby', 'meta_value');
            }
            return;
        }

        // Frontend archive
        if (!$query->is_post_type_archive(self::POST_TYPE)) {
            return;
        }

        // Sort by event date
        $query->set('meta_key', $meta_key);
        $query->set('orderby', 'meta_value');
        $query->set('order', 'ASC');

        // Filter past events unless explicitly requested
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        if (!isset($_GET['show_past'])) {
            $query->set('meta_query', [
                [
                    'key'     => $meta_key,
                    'value'   => current_time('Y-m-d'),
                    'compare' => '>=',
                    'type'    => 'DATE',
                ],
            ]);
        }
    }

    /**
     * Get event meta data
     *
     * @param int $post_id Event post ID
     * @return array<string, mixed>
     */
    public static function getEventMeta(int $post_id): array
    {
        return [
            'date_start'            => carbon_get_post_meta($post_id, self::FIELD_DATE_START),
            'date_end'              => carbon_get_post_meta($post_id, self::FIELD_DATE_END),
            'time_start'            => carbon_get_post_meta($post_id, self::FIELD_TIME_START),
            'time_end'              => carbon_get_post_meta($post_id, self::FIELD_TIME_END),
            'location'              => carbon_get_post_meta($post_id, self::FIELD_LOCATION),
            'address'               => carbon_get_post_meta($post_id, self::FIELD_ADDRESS),
            'map_link'              => carbon_get_post_meta($post_id, self::FIELD_MAP_LINK),
            'registration_link'     => carbon_get_post_meta($post_id, self::FIELD_REGISTRATION_LINK),
            'registration_deadline' => carbon_get_post_meta($post_id, self::FIELD_REGISTRATION_DEADLINE),
            'max_participants'      => carbon_get_post_meta($post_id, self::FIELD_MAX_PARTICIPANTS),
            'current_participants'  => carbon_get_post_meta($post_id, self::FIELD_CURRENT_PARTICIPANTS),
            'difficulty'            => carbon_get_post_meta($post_id, self::FIELD_DIFFICULTY),
            'price'                 => carbon_get_post_meta($post_id, self::FIELD_PRICE),
            'equipment'             => carbon_get_post_meta($post_id, self::FIELD_EQUIPMENT),
            'organizer'             => carbon_get_post_meta($post_id, self::FIELD_ORGANIZER),
        ];
    }
}
