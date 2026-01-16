<?php

/**
 * SEO Fields for Posts, Pages, and Theme Options
 *
 * @package Lemur\Fields
 */

declare(strict_types=1);

namespace Lemur\Fields;

use Carbon_Fields\Container;
use Carbon_Fields\Field;

/**
 * Registers SEO meta fields for content and global SEO settings
 */
class SEOFields
{
    /**
     * Post types that should have SEO fields
     */
    private const POST_TYPES = ['post', 'page', 'evenements', 'faq', 'collectifs'];

    // Field constants for SEO options
    public const FIELD_SITE_DESCRIPTION = 'lemur_site_description';
    public const FIELD_DEFAULT_IMAGE = 'lemur_seo_default_image';
    public const FIELD_TWITTER_HANDLE = 'lemur_twitter_handle';
    public const FIELD_CLUB_NAME = 'lemur_club_name';
    public const FIELD_CLUB_DESCRIPTION = 'lemur_club_description';
    public const FIELD_LATITUDE = 'lemur_latitude';
    public const FIELD_LONGITUDE = 'lemur_longitude';
    public const FIELD_SCHEMA_HOURS = 'lemur_schema_hours';

    /**
     * Initialize the module
     */
    public static function init(): void
    {
        add_action('carbon_fields_register_fields', [self::class, 'registerFields'], 10);
    }

    /**
     * Register all SEO fields
     */
    public static function registerFields(): void
    {
        self::registerPostMeta();
        self::registerThemeOptions();
    }

    /**
     * Register SEO meta box for posts/pages
     */
    private static function registerPostMeta(): void
    {
        Container::make('post_meta', __('SEO', 'lemur'))
            ->where('post_type', 'IN', self::POST_TYPES)
            ->set_priority('low')
            ->add_fields([
                Field::make('text', 'seo_title', __('Titre SEO', 'lemur'))
                    ->set_attribute('maxLength', 60)
                    ->set_help_text(__('Laissez vide pour utiliser le titre de la page. Max 60 caractères.', 'lemur')),

                Field::make('textarea', 'seo_description', __('Description SEO', 'lemur'))
                    ->set_attribute('maxLength', 160)
                    ->set_rows(3)
                    ->set_help_text(__('Laissez vide pour utiliser l\'extrait. Max 160 caractères.', 'lemur')),

                Field::make('image', 'seo_image', __('Image de partage', 'lemur'))
                    ->set_help_text(__('Image pour les réseaux sociaux (1200x630px recommandé).', 'lemur')),

                Field::make('text', 'seo_canonical', __('URL canonique', 'lemur'))
                    ->set_help_text(__('Laissez vide pour utiliser l\'URL de la page.', 'lemur')),

                Field::make('select', 'seo_robots', __('Robots', 'lemur'))
                    ->set_options([
                        '' => __('Par défaut (index, follow)', 'lemur'),
                        'noindex, follow' => __('Ne pas indexer (noindex, follow)', 'lemur'),
                        'index, nofollow' => __('Ne pas suivre les liens (index, nofollow)', 'lemur'),
                        'noindex, nofollow' => __('Ne pas indexer ni suivre', 'lemur'),
                    ])
                    ->set_help_text(__('Contrôle l\'indexation par les moteurs de recherche.', 'lemur')),
            ]);
    }

    /**
     * Register SEO theme options (top-level menu)
     */
    private static function registerThemeOptions(): void
    {
        Container::make('theme_options', __('SEO', 'lemur'))
            ->set_page_file('lemur-seo')
            ->set_page_menu_position(62)
            ->set_icon('dashicons-search')
            ->add_tab(__('Général', 'lemur'), self::getGeneralFields())
            ->add_tab(__('Schema.org', 'lemur'), self::getSchemaFields());
    }

    /**
     * Get general SEO fields
     *
     * @return array<int, \Carbon_Fields\Field\Field>
     */
    private static function getGeneralFields(): array
    {
        return [
            Field::make('html', 'seo_general_intro')
                ->set_html(self::makeIntro(
                    __('Métadonnées globales', 'lemur'),
                    __('Ces paramètres s\'appliquent à l\'ensemble du site comme valeurs par défaut.', 'lemur')
                )),

            Field::make('separator', 'sep_meta', __('Description du site', 'lemur')),

            Field::make('textarea', self::FIELD_SITE_DESCRIPTION, __('Description SEO par défaut', 'lemur'))
                ->set_rows(3)
                ->set_attribute('maxLength', 160)
                ->set_attribute('placeholder', 'Votre club d\'escalade associatif...')
                ->set_help_text(__('Utilisée comme meta description par défaut quand une page n\'a pas de description personnalisée. Max 160 caractères.', 'lemur')),

            Field::make('separator', 'sep_social', __('Réseaux sociaux', 'lemur')),

            Field::make('image', self::FIELD_DEFAULT_IMAGE, __('Image de partage par défaut', 'lemur'))
                ->set_help_text(__('Utilisée pour Open Graph/Twitter si aucune image n\'est définie sur le contenu (1200x630px recommandé).', 'lemur')),

            Field::make('text', self::FIELD_TWITTER_HANDLE, __('Compte Twitter', 'lemur'))
                ->set_attribute('placeholder', '@monclub')
                ->set_help_text(__('Handle Twitter pour les Twitter Cards (avec ou sans @).', 'lemur')),
        ];
    }

    /**
     * Get Schema.org fields
     *
     * @return array<int, \Carbon_Fields\Field\Field>
     */
    private static function getSchemaFields(): array
    {
        return [
            Field::make('html', 'schema_intro')
                ->set_html(self::makeIntro(
                    __('Données structurées', 'lemur'),
                    __('Ces informations sont utilisées pour générer le JSON-LD Schema.org (rich snippets Google).', 'lemur')
                )),

            Field::make('separator', 'sep_org', __('Organisation', 'lemur')),

            Field::make('text', self::FIELD_CLUB_NAME, __('Nom du club', 'lemur'))
                ->set_attribute('placeholder', 'Mon Club Escalade')
                ->set_help_text(__('Nom officiel pour les données structurées.', 'lemur')),

            Field::make('textarea', self::FIELD_CLUB_DESCRIPTION, __('Description du club', 'lemur'))
                ->set_rows(3)
                ->set_help_text(__('Description courte du club pour Schema.org.', 'lemur')),

            Field::make('separator', 'sep_geo', __('Géolocalisation', 'lemur')),

            Field::make('text', self::FIELD_LATITUDE, __('Latitude', 'lemur'))
                ->set_attribute('placeholder', '48.8566')
                ->set_width(50)
                ->set_help_text(__('Coordonnées GPS du club.', 'lemur')),

            Field::make('text', self::FIELD_LONGITUDE, __('Longitude', 'lemur'))
                ->set_attribute('placeholder', '2.3522')
                ->set_width(50)
                ->set_help_text(__('Coordonnées GPS du club.', 'lemur')),

            Field::make('separator', 'sep_hours', __('Horaires (Schema.org)', 'lemur')),

            Field::make('complex', self::FIELD_SCHEMA_HOURS, __('Horaires d\'ouverture', 'lemur'))
                ->add_fields([
                    Field::make('select', 'day', __('Jour', 'lemur'))
                        ->set_options([
                            'lundi' => __('Lundi', 'lemur'),
                            'mardi' => __('Mardi', 'lemur'),
                            'mercredi' => __('Mercredi', 'lemur'),
                            'jeudi' => __('Jeudi', 'lemur'),
                            'vendredi' => __('Vendredi', 'lemur'),
                            'samedi' => __('Samedi', 'lemur'),
                            'dimanche' => __('Dimanche', 'lemur'),
                        ])
                        ->set_width(40),
                    Field::make('text', 'open', __('Ouverture', 'lemur'))
                        ->set_attribute('type', 'time')
                        ->set_width(30),
                    Field::make('text', 'close', __('Fermeture', 'lemur'))
                        ->set_attribute('type', 'time')
                        ->set_width(30),
                ])
                ->set_header_template('<%- day %> : <%- open %> - <%- close %>')
                ->set_help_text(__('Horaires pour Schema.org LocalBusiness (différents des horaires affichés sur le site).', 'lemur')),
        ];
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
            '<div style="background:linear-gradient(135deg,#2271b1 0%%,#135e96 100%%);color:#fff;padding:16px 20px;border-radius:8px;margin-bottom:20px;">
                <strong style="display:block;font-size:15px;margin-bottom:4px;">%s</strong>
                <span style="opacity:0.9;">%s</span>
            </div>',
            esc_html($title),
            esc_html($description)
        );
    }
}
