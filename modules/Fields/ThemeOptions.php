<?php
/**
 * Theme Options - Carbon Fields container
 *
 * Provides admin interface for theme configuration including identity,
 * contact information, opening hours, external links, and social media.
 *
 * @package Lemur\Fields
 */

declare(strict_types=1);

namespace Lemur\Fields;

use Carbon_Fields\Container;
use Carbon_Fields\Field;

/**
 * Register theme options using Carbon Fields
 */
class ThemeOptions
{
    // Identity fields
    public const FIELD_LOGO = 'lemur_logo';
    public const FIELD_LOGO_FOOTER = 'lemur_logo_footer';
    public const FIELD_FAVICON = 'lemur_favicon';

    // Contact fields
    public const FIELD_PHONE = 'lemur_phone';
    public const FIELD_EMAIL = 'lemur_email';
    public const FIELD_ADDRESS = 'lemur_address';
    public const FIELD_MAPS_URL = 'lemur_maps_url';
    public const FIELD_MAP_EMBED = 'lemur_map_embed';
    public const FIELD_MAP_IMAGE = 'lemur_map_image';
    public const FIELD_CONTACT_ADDITIONAL = 'lemur_contact_additional';
    public const FIELD_TRANSPORT_LINES = 'lemur_transport_lines';

    // Hours fields
    public const FIELD_OPENING_HOURS = 'lemur_opening_hours';
    public const FIELD_HOURS_NOTE = 'lemur_hours_note';
    public const FIELD_SCHEDULE = 'lemur_schedule';
    public const FIELD_PLANNING_SHEET_URL = 'lemur_planning_sheet_url';

    // External links fields
    public const FIELD_ADHESION_LINK = 'lemur_adhesion_link';
    public const FIELD_ADHESION_TEXT = 'lemur_adhesion_text';
    public const FIELD_GALETTE_URL = 'lemur_galette_url';
    public const FIELD_EXTERNAL_LINKS = 'lemur_external_links';

    // Social fields
    public const FIELD_FACEBOOK = 'lemur_facebook';
    public const FIELD_INSTAGRAM = 'lemur_instagram';
    public const FIELD_YOUTUBE = 'lemur_youtube';
    public const FIELD_TWITTER_HANDLE = 'lemur_twitter_handle';

    // SEO fields
    public const FIELD_SITE_DESCRIPTION = 'lemur_site_description';
    public const FIELD_SEO_DEFAULT_IMAGE = 'lemur_seo_default_image';

    // Schema.org / Club info fields
    public const FIELD_CLUB_NAME = 'lemur_club_name';
    public const FIELD_CLUB_DESCRIPTION = 'lemur_club_description';
    public const FIELD_CITY = 'lemur_city';
    public const FIELD_POSTAL_CODE = 'lemur_postal_code';
    public const FIELD_LATITUDE = 'lemur_latitude';
    public const FIELD_LONGITUDE = 'lemur_longitude';
    public const FIELD_SCHEMA_HOURS = 'lemur_schema_hours';

    // Homepage fields
    public const FIELD_HOME_HERO_IMAGE = 'home_hero_image';
    public const FIELD_HOME_HERO_SLOGAN = 'home_hero_slogan';
    public const FIELD_HOME_HERO_SUBTITLE = 'home_hero_subtitle';
    public const FIELD_HOME_HERO_TEXT_COLOR = 'home_hero_text_color';
    public const FIELD_HOME_VALUES_TITLE = 'home_values_title';
    public const FIELD_HOME_VALUES_INTRO = 'home_values_intro';
    public const FIELD_HOME_VALUES_LIST = 'home_values_list';
    public const FIELD_HOME_GRIMPER_TITLE = 'home_grimper_title';
    public const FIELD_HOME_GRIMPER_INTRO = 'home_grimper_intro';
    public const FIELD_HOME_GRIMPER_MAIN_GYM = 'home_grimper_main_gym';
    public const FIELD_HOME_GRIMPER_OTHER_GYMS = 'home_grimper_other_gyms';
    public const FIELD_HOME_ACTIVITIES_TITLE = 'home_activities_title';
    public const FIELD_HOME_ACTIVITIES_INTRO = 'home_activities_intro';
    public const FIELD_HOME_ACTIVITIES_LIST = 'home_activities_list';
    public const FIELD_HOME_CTA_TITLE = 'home_cta_title';
    public const FIELD_HOME_CTA_DESCRIPTION = 'home_cta_description';

    // FSGT fields
    public const FIELD_FSGT_LOGO = 'fsgt_logo';
    public const FIELD_FSGT_TEXT = 'fsgt_text';

    // Adhesion fields
    public const FIELD_ADHESION_LICENCE_FSGT = 'adhesion_licence_fsgt';
    public const FIELD_ADHESION_ADULTE_PALIERS = 'adhesion_adulte_paliers';
    public const FIELD_ADHESION_FAMILLE_PALIERS = 'adhesion_famille_paliers';
    public const FIELD_ADHESION_DOUBLE_PALIERS = 'adhesion_double_paliers';

    // Gallery fields
    public const FIELD_GALLERY_ALBUMS = 'lemur_gallery_albums';

    /**
     * Initialize theme options
     */
    public static function init(): void
    {
        add_action('carbon_fields_register_fields', [self::class, 'registerFields']);
        add_filter('carbon_fields_should_save_field_value', [self::class, 'validateFieldValue'], 10, 3);
        add_action('admin_head', [self::class, 'addAdminStyles']);
    }

    /**
     * Add custom admin styles for Carbon Fields
     */
    public static function addAdminStyles(): void
    {
        $screen = get_current_screen();
        if (!$screen || $screen->id !== 'toplevel_page_lemur-options') {
            return;
        }

        echo '<style>
            .lemur-intro {
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                color: #fff;
                padding: 16px 20px;
                border-radius: 8px;
                margin-bottom: 20px;
                font-size: 14px;
                line-height: 1.5;
            }
            .lemur-intro strong {
                display: block;
                font-size: 15px;
                margin-bottom: 4px;
            }
            .lemur-tip {
                background: #f0f6fc;
                border-left: 4px solid #2271b1;
                padding: 12px 16px;
                margin-bottom: 16px;
                font-size: 13px;
                color: #1d2327;
            }
            .cf-container .cf-field.cf-separator h3 {
                background: #f6f7f7;
                padding: 12px 16px;
                margin: 24px -16px 16px;
                border-top: 1px solid #dcdcde;
                border-bottom: 1px solid #dcdcde;
                font-size: 14px;
                font-weight: 600;
                color: #1d2327;
            }
            .cf-container .cf-complex__inserter-button {
                background: #2271b1;
                color: #fff;
                border: none;
                border-radius: 4px;
            }
            .cf-container .cf-complex__inserter-button:hover {
                background: #135e96;
            }
        </style>';
    }

    /**
     * Generate intro HTML block
     *
     * @param string $title Title text
     * @param string $description Description text
     * @return string HTML markup
     */
    private static function makeIntro(string $title, string $description): string
    {
        return sprintf(
            '<div class="lemur-intro"><strong>%s</strong>%s</div>',
            esc_html($title),
            esc_html($description)
        );
    }

    /**
     * Generate tip HTML block
     *
     * @param string $text Tip text
     * @return string HTML markup
     */
    private static function makeTip(string $text): string
    {
        return sprintf('<div class="lemur-tip">%s</div>', esc_html($text));
    }

    /**
     * Validate field values before saving
     *
     * @param bool                          $save  Whether to save the value
     * @param mixed                         $value The field value
     * @param \Carbon_Fields\Field\Field    $field The field object
     * @return bool
     */
    public static function validateFieldValue(bool $save, mixed $value, $field): bool
    {
        if (!$save) {
            return false;
        }

        $fieldName = $field->get_base_name();

        // Validate email field
        if ($fieldName === self::FIELD_EMAIL && !empty($value) && !is_email($value)) {
            return false;
        }

        // Validate URL fields
        $urlFields = [
            self::FIELD_MAPS_URL,
            self::FIELD_ADHESION_LINK,
            self::FIELD_GALETTE_URL,
            self::FIELD_FACEBOOK,
            self::FIELD_INSTAGRAM,
            self::FIELD_YOUTUBE,
        ];

        if (in_array($fieldName, $urlFields, true) && !empty($value) && !filter_var($value, FILTER_VALIDATE_URL)) {
            return false;
        }

        return true;
    }

    /**
     * Register theme options container and fields
     */
    public static function registerFields(): void
    {
        Container::make('theme_options', __('Options Lemur', 'lemur'))
            ->set_page_file('lemur-options')
            ->set_page_menu_title(__('Lemur', 'lemur'))
            ->set_page_menu_position(61)
            ->set_icon('dashicons-location-alt')
            ->add_tab(__('Identité', 'lemur'), self::getIdentityFields())
            ->add_tab(__('Contact', 'lemur'), self::getContactFields())
            ->add_tab(__('Horaires', 'lemur'), self::getHoursFields())
            ->add_tab(__('Accueil', 'lemur'), self::getHomepageFields())
            ->add_tab(__('Adhésion', 'lemur'), self::getAdhesionFields())
            ->add_tab(__('Liens', 'lemur'), self::getLinksFields())
            ->add_tab(__('Galerie', 'lemur'), self::getGalleryFields());
    }

    /**
     * Get identity fields
     *
     * @return array<int, \Carbon_Fields\Field\Field>
     */
    private static function getIdentityFields(): array
    {
        return [
            Field::make('html', 'identity_intro')
                ->set_html(self::makeIntro(
                    __('Identité visuelle', 'lemur'),
                    __('Logos et description affichés sur l\'ensemble du site.', 'lemur')
                )),

            Field::make('image', self::FIELD_LOGO, __('Logo principal', 'lemur'))
                ->set_value_type('id')
                ->set_width(33)
                ->set_help_text(__('PNG transparent, ~80px de haut', 'lemur')),

            Field::make('image', self::FIELD_LOGO_FOOTER, __('Logo footer', 'lemur'))
                ->set_value_type('id')
                ->set_width(33)
                ->set_help_text(__('Optionnel, version alternative', 'lemur')),

            Field::make('image', self::FIELD_FAVICON, __('Favicon', 'lemur'))
                ->set_value_type('id')
                ->set_width(33)
                ->set_help_text(__('512×512px, PNG ou ICO', 'lemur')),
        ];
    }

    /**
     * Get contact fields
     *
     * @return array<int, \Carbon_Fields\Field\Field>
     */
    private static function getContactFields(): array
    {
        return [
            Field::make('html', 'contact_intro')
                ->set_html(self::makeIntro(
                    __('Coordonnées du club', 'lemur'),
                    __('Ces informations apparaissent sur la page Contact et dans le footer.', 'lemur')
                )),

            Field::make('text', self::FIELD_EMAIL, __('Email', 'lemur'))
                ->set_attribute('type', 'email')
                ->set_attribute('placeholder', 'contact@example.org')
                ->set_width(50),

            Field::make('text', self::FIELD_PHONE, __('Téléphone', 'lemur'))
                ->set_attribute('placeholder', '01 23 45 67 89')
                ->set_attribute('type', 'tel')
                ->set_attribute('pattern', '[0-9 +]+')
                ->set_width(50),

            Field::make('textarea', self::FIELD_ADDRESS, __('Adresse', 'lemur'))
                ->set_rows(2)
                ->set_attribute('placeholder', "Nom du lieu\nAdresse, Code postal Ville"),

            Field::make('text', self::FIELD_MAPS_URL, __('Lien Google Maps', 'lemur'))
                ->set_attribute('placeholder', 'https://maps.google.com/...'),

            Field::make('separator', 'sep_contact_map', __('Carte sur la page Contact', 'lemur')),

            Field::make('textarea', self::FIELD_MAP_EMBED, __('Code iframe', 'lemur'))
                ->set_rows(3)
                ->set_width(60)
                ->set_help_text(__('Google Maps > Partager > Intégrer', 'lemur')),

            Field::make('image', self::FIELD_MAP_IMAGE, __('Ou image statique', 'lemur'))
                ->set_value_type('id')
                ->set_width(40),

            Field::make('separator', 'sep_contact_transport', __('Accès en transports', 'lemur')),

            Field::make('complex', self::FIELD_TRANSPORT_LINES, __('Lignes', 'lemur'))
                ->set_header_template('<%- type.toUpperCase() %> <%- line %> — <%- station || "..." %>')
                ->add_fields([
                    Field::make('select', 'type', __('Type', 'lemur'))
                        ->set_options([
                            'metro' => __('Métro', 'lemur'),
                            'rer'   => __('RER', 'lemur'),
                            'bus'   => __('Bus', 'lemur'),
                            'tram'  => __('Tram', 'lemur'),
                        ])
                        ->set_width(20),

                    Field::make('text', 'line', __('Ligne', 'lemur'))
                        ->set_attribute('placeholder', '11')
                        ->set_width(20),

                    Field::make('text', 'station', __('Station', 'lemur'))
                        ->set_attribute('placeholder', 'Nom de la station')
                        ->set_width(60),
                ]),

            Field::make('separator', 'sep_contact_extra', __('Informations complémentaires', 'lemur')),

            Field::make('rich_text', self::FIELD_CONTACT_ADDITIONAL, __('Infos additionnelles', 'lemur'))
                ->set_help_text(__('Parking, accessibilité PMR, code d\'entrée...', 'lemur')),
        ];
    }

    /**
     * Get hours fields
     *
     * @return array<int, \Carbon_Fields\Field\Field>
     */
    private static function getHoursFields(): array
    {
        return [
            Field::make('html', 'hours_intro')
                ->set_html(self::makeIntro(
                    __('Planning et horaires', 'lemur'),
                    __('Configurez les créneaux d\'escalade et les horaires affichés sur le site.', 'lemur')
                )),

            Field::make('rich_text', self::FIELD_OPENING_HOURS, __('Horaires (footer)', 'lemur'))
                ->set_help_text(__('Texte libre affiché dans le pied de page', 'lemur')),

            Field::make('text', self::FIELD_HOURS_NOTE, __('Note', 'lemur'))
                ->set_attribute('placeholder', __('Fermé pendant les vacances scolaires', 'lemur')),

            Field::make('separator', 'sep_planning', __('Planning interactif', 'lemur')),

            Field::make('html', 'planning_tip')
                ->set_html(self::makeTip(__('Vous pouvez intégrer un Google Sheet publié ou saisir les créneaux manuellement ci-dessous.', 'lemur'))),

            Field::make('text', self::FIELD_PLANNING_SHEET_URL, __('URL Google Sheet', 'lemur'))
                ->set_attribute('placeholder', 'https://docs.google.com/spreadsheets/d/.../pubhtml')
                ->set_help_text(__('Fichier > Partager > Publier sur le web', 'lemur')),

            Field::make('separator', 'sep_schedule', __('Créneaux manuels', 'lemur')),

            Field::make('complex', self::FIELD_SCHEDULE, __('Planning', 'lemur'))
                ->set_header_template('<%- day.charAt(0).toUpperCase() + day.slice(1) %> <%- hours %> — <%- activity || location || "..." %>')
                ->add_fields([
                    Field::make('select', 'day', __('Jour', 'lemur'))
                        ->set_options([
                            'lundi'    => __('Lundi', 'lemur'),
                            'mardi'    => __('Mardi', 'lemur'),
                            'mercredi' => __('Mercredi', 'lemur'),
                            'jeudi'    => __('Jeudi', 'lemur'),
                            'vendredi' => __('Vendredi', 'lemur'),
                            'samedi'   => __('Samedi', 'lemur'),
                            'dimanche' => __('Dimanche', 'lemur'),
                        ])
                        ->set_width(20),

                    Field::make('text', 'hours', __('Horaire', 'lemur'))
                        ->set_attribute('placeholder', '19h-21h30')
                        ->set_width(20),

                    Field::make('text', 'location', __('Lieu', 'lemur'))
                        ->set_attribute('placeholder', 'Antrebloc')
                        ->set_width(30),

                    Field::make('text', 'activity', __('Activité', 'lemur'))
                        ->set_attribute('placeholder', 'Bloc tous niveaux')
                        ->set_width(30),
                ]),
        ];
    }

    /**
     * Get homepage fields
     *
     * @return array<int, \Carbon_Fields\Field\Field>
     */
    private static function getHomepageFields(): array
    {
        return [
            Field::make('html', 'homepage_intro')
                ->set_html(self::makeIntro(
                    __('Page d\'accueil', 'lemur'),
                    __('Personnalisez le contenu de la page d\'accueil du site.', 'lemur')
                )),

            // Hero Banner
            Field::make('separator', 'sep_hero', __('Bannière hero', 'lemur')),

            Field::make('image', self::FIELD_HOME_HERO_IMAGE, __('Image de fond', 'lemur'))
                ->set_value_type('id')
                ->set_width(50)
                ->set_help_text(__('1920×1080px minimum', 'lemur')),

            Field::make('select', self::FIELD_HOME_HERO_TEXT_COLOR, __('Couleur texte', 'lemur'))
                ->set_options([
                    'light' => __('Blanc', 'lemur'),
                    'dark'  => __('Noir', 'lemur'),
                ])
                ->set_default_value('light')
                ->set_width(25),

            Field::make('text', self::FIELD_HOME_HERO_SLOGAN, __('Slogan', 'lemur'))
                ->set_default_value(__('L\'escalade pour tous', 'lemur'))
                ->set_width(50),

            Field::make('text', self::FIELD_HOME_HERO_SUBTITLE, __('Sous-titre', 'lemur'))
                ->set_attribute('placeholder', __('Club associatif à...', 'lemur'))
                ->set_width(50),

            // Values section
            Field::make('separator', 'sep_values', __('Nos valeurs', 'lemur')),

            Field::make('text', self::FIELD_HOME_VALUES_TITLE, __('Titre', 'lemur'))
                ->set_default_value(__('Nos valeurs', 'lemur'))
                ->set_width(40),

            Field::make('textarea', self::FIELD_HOME_VALUES_INTRO, __('Texte intro', 'lemur'))
                ->set_rows(2)
                ->set_width(60),

            Field::make('complex', self::FIELD_HOME_VALUES_LIST, __('Valeurs', 'lemur'))
                ->set_header_template('<%- title || "Nouvelle valeur" %>')
                ->add_fields([
                    Field::make('text', 'icon', __('Icone', 'lemur'))
                        ->set_attribute('placeholder', 'users')
                        ->set_width(15),
                    Field::make('text', 'title', __('Titre', 'lemur'))
                        ->set_width(25),
                    Field::make('textarea', 'description', __('Description', 'lemur'))
                        ->set_rows(2)
                        ->set_width(60),
                ]),

            // Grimper section
            Field::make('separator', 'sep_grimper', __('Où grimper', 'lemur')),

            Field::make('text', self::FIELD_HOME_GRIMPER_TITLE, __('Titre', 'lemur'))
                ->set_default_value(__('Où grimper ?', 'lemur'))
                ->set_width(40),

            Field::make('textarea', self::FIELD_HOME_GRIMPER_INTRO, __('Texte intro', 'lemur'))
                ->set_rows(2)
                ->set_width(60),

            Field::make('complex', self::FIELD_HOME_GRIMPER_MAIN_GYM, __('Salle principale', 'lemur'))
                ->set_max(1)
                ->set_header_template('<%- name || "Configurer la salle" %>')
                ->add_fields([
                    Field::make('text', 'name', __('Nom', 'lemur'))
                        ->set_attribute('placeholder', 'Antrebloc')
                        ->set_width(50),
                    Field::make('text', 'address', __('Adresse', 'lemur'))
                        ->set_width(50),
                    Field::make('complex', 'schedule', __('Créneaux', 'lemur'))
                        ->set_header_template('<%- day %> <%- time %>')
                        ->add_fields([
                            Field::make('text', 'day', __('Jour', 'lemur'))
                                ->set_attribute('placeholder', 'Mardi')
                                ->set_width(30),
                            Field::make('text', 'time', __('Horaire', 'lemur'))
                                ->set_attribute('placeholder', '19h-21h30')
                                ->set_width(30),
                            Field::make('text', 'audience', __('Public', 'lemur'))
                                ->set_attribute('placeholder', 'Tous niveaux')
                                ->set_width(40),
                        ]),
                ]),

            Field::make('complex', self::FIELD_HOME_GRIMPER_OTHER_GYMS, __('Autres salles', 'lemur'))
                ->set_header_template('<%- name || "Nouvelle salle" %>')
                ->add_fields([
                    Field::make('text', 'name', __('Nom', 'lemur'))
                        ->set_width(50),
                    Field::make('text', 'info', __('Info', 'lemur'))
                        ->set_attribute('placeholder', 'Accès libre')
                        ->set_width(50),
                ]),

            // Activities section
            Field::make('separator', 'sep_activities', __('Nos activités', 'lemur')),

            Field::make('text', self::FIELD_HOME_ACTIVITIES_TITLE, __('Titre', 'lemur'))
                ->set_default_value(__('Nos activités', 'lemur'))
                ->set_width(40),

            Field::make('textarea', self::FIELD_HOME_ACTIVITIES_INTRO, __('Texte intro', 'lemur'))
                ->set_rows(2)
                ->set_width(60),

            Field::make('complex', self::FIELD_HOME_ACTIVITIES_LIST, __('Activités', 'lemur'))
                ->set_header_template('<%- title || "Nouvelle activité" %>')
                ->add_fields([
                    Field::make('text', 'icon', __('Icone', 'lemur'))
                        ->set_attribute('placeholder', 'climbing')
                        ->set_width(15),
                    Field::make('text', 'title', __('Titre', 'lemur'))
                        ->set_width(25),
                    Field::make('text', 'description', __('Description', 'lemur'))
                        ->set_width(60),
                ]),

            // CTA section
            Field::make('separator', 'sep_cta', __('Bandeau d\'appel à l\'action', 'lemur')),

            Field::make('text', self::FIELD_HOME_CTA_TITLE, __('Titre', 'lemur'))
                ->set_default_value(__('Envie de grimper avec nous ?', 'lemur'))
                ->set_width(40),

            Field::make('textarea', self::FIELD_HOME_CTA_DESCRIPTION, __('Texte', 'lemur'))
                ->set_default_value(__('Rejoignez notre association et découvrez l\'escalade dans une ambiance conviviale.', 'lemur'))
                ->set_rows(2)
                ->set_width(60),
        ];
    }

    /**
     * Get adhesion fields (tiered pricing configuration)
     *
     * @return array<int, \Carbon_Fields\Field\Field>
     */
    private static function getAdhesionFields(): array
    {
        return [
            Field::make('html', 'adhesion_intro')
                ->set_html(self::makeIntro(
                    __('Cotisations à prix libre', 'lemur'),
                    __('Configurez les paliers affichés sur la page Adhésion.', 'lemur')
                )),

            Field::make('text', self::FIELD_ADHESION_LICENCE_FSGT, __('Licence FSGT', 'lemur'))
                ->set_attribute('type', 'number')
                ->set_attribute('min', '0')
                ->set_default_value('40')
                ->set_width(30)
                ->set_help_text(__('Part fédérale en euros', 'lemur')),

            Field::make('separator', 'sep_adhesion_adulte', __('Formule Adulte', 'lemur')),

            Field::make('text', self::FIELD_ADHESION_ADULTE_PALIERS, __('Paliers', 'lemur'))
                ->set_default_value('50,80,110,140,170,200')
                ->set_help_text(__('Montants séparés par virgule (sans espaces)', 'lemur')),

            Field::make('separator', 'sep_adhesion_famille', __('Formule Famille', 'lemur')),

            Field::make('text', self::FIELD_ADHESION_FAMILLE_PALIERS, __('Paliers', 'lemur'))
                ->set_default_value('80,110,140,170,200,230')
                ->set_help_text(__('Montants séparés par virgule', 'lemur')),

            Field::make('separator', 'sep_adhesion_double', __('Double licence FSGT', 'lemur')),

            Field::make('text', self::FIELD_ADHESION_DOUBLE_PALIERS, __('Paliers', 'lemur'))
                ->set_default_value('10,40,70,100,130,160')
                ->set_help_text(__('Pour membres déjà licenciés FSGT ailleurs', 'lemur')),

            Field::make('separator', 'sep_fsgt', __('Affiliation FSGT', 'lemur')),

            Field::make('image', self::FIELD_FSGT_LOGO, __('Logo FSGT', 'lemur'))
                ->set_value_type('id')
                ->set_width(30)
                ->set_help_text(__('Pour le préfooter', 'lemur')),

            Field::make('text', self::FIELD_FSGT_TEXT, __('Texte FSGT', 'lemur'))
                ->set_default_value(__('Club affilié à la Fédération Sportive et Gymnique du Travail', 'lemur'))
                ->set_width(70),
        ];
    }

    /**
     * Get links fields (external links + social media)
     *
     * @return array<int, \Carbon_Fields\Field\Field>
     */
    private static function getLinksFields(): array
    {
        return [
            Field::make('html', 'links_intro')
                ->set_html(self::makeIntro(
                    __('Liens externes', 'lemur'),
                    __('Réseaux sociaux, espace membre et liens partenaires.', 'lemur')
                )),

            Field::make('separator', 'sep_cta_main', __('Bouton principal (CTA)', 'lemur')),

            Field::make('text', self::FIELD_ADHESION_LINK, __('URL inscription', 'lemur'))
                ->set_attribute('placeholder', 'https://docs.google.com/forms/...')
                ->set_width(60)
                ->set_help_text(__('Liste d\'attente ou formulaire', 'lemur')),

            Field::make('text', self::FIELD_ADHESION_TEXT, __('Texte bouton', 'lemur'))
                ->set_default_value(__('Nous rejoindre', 'lemur'))
                ->set_width(40),

            Field::make('separator', 'sep_member_area', __('Espace membre', 'lemur')),

            Field::make('text', self::FIELD_GALETTE_URL, __('URL Galette', 'lemur'))
                ->set_attribute('placeholder', 'https://galette.example.org')
                ->set_help_text(__('Gestion des adhérents', 'lemur')),

            Field::make('separator', 'sep_social', __('Réseaux sociaux', 'lemur')),

            Field::make('text', self::FIELD_FACEBOOK, __('Facebook', 'lemur'))
                ->set_attribute('placeholder', 'https://facebook.com/...')
                ->set_width(33),

            Field::make('text', self::FIELD_INSTAGRAM, __('Instagram', 'lemur'))
                ->set_attribute('placeholder', 'https://instagram.com/...')
                ->set_width(33),

            Field::make('text', self::FIELD_YOUTUBE, __('YouTube', 'lemur'))
                ->set_attribute('placeholder', 'https://youtube.com/...')
                ->set_width(33),

            Field::make('separator', 'sep_partners', __('Liens partenaires', 'lemur')),

            Field::make('complex', self::FIELD_EXTERNAL_LINKS, __('Autres liens', 'lemur'))
                ->set_header_template('<%- label || "Nouveau lien" %>')
                ->set_help_text(__('Liens affichés dans le footer', 'lemur'))
                ->add_fields([
                    Field::make('text', 'label', __('Libellé', 'lemur'))
                        ->set_required(true)
                        ->set_width(40),

                    Field::make('text', 'url', __('URL', 'lemur'))
                        ->set_attribute('placeholder', 'https://')
                        ->set_required(true)
                        ->set_width(45),

                    Field::make('checkbox', 'new_tab', __('Nouvel onglet', 'lemur'))
                        ->set_option_value('yes')
                        ->set_default_value(true)
                        ->set_width(15),
                ]),
        ];
    }

    /**
     * Get gallery fields
     *
     * @return array<int, \Carbon_Fields\Field\Field>
     */
    private static function getGalleryFields(): array
    {
        return [
            Field::make('html', 'gallery_intro')
                ->set_html(self::makeIntro(
                    __('Galerie photo', 'lemur'),
                    __('Organisez vos photos en albums pour la page Galerie.', 'lemur')
                )),

            Field::make('complex', self::FIELD_GALLERY_ALBUMS, __('Albums', 'lemur'))
                ->set_layout('tabbed-vertical')
                ->set_header_template('<%- name || "Nouvel album" %>')
                ->add_fields([
                    Field::make('text', 'name', __('Nom', 'lemur'))
                        ->set_required(true)
                        ->set_width(50),

                    Field::make('textarea', 'description', __('Description', 'lemur'))
                        ->set_rows(2)
                        ->set_width(50),

                    Field::make('media_gallery', 'images', __('Photos', 'lemur'))
                        ->set_type(['image']),
                ]),
        ];
    }
}
