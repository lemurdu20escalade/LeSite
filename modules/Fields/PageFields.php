<?php
/**
 * Page Fields - Page Builder with Carbon Fields Complex
 *
 * Provides a drag & drop page builder with 26 block types.
 *
 * @package Lemur\Fields
 */

declare(strict_types=1);

namespace Lemur\Fields;

use Carbon_Fields\Container;
use Carbon_Fields\Field;

/**
 * Register page builder fields using Carbon Fields
 */
class PageFields
{
    /**
     * Initialize page fields
     */
    public static function init(): void
    {
        add_action('carbon_fields_register_fields', [self::class, 'registerFields']);
    }

    /**
     * Register page builder container and fields
     */
    public static function registerFields(): void
    {
        Container::make('post_meta', __('Sections de la page', 'lemur'))
            ->where('post_type', '=', 'page')
            ->set_priority('high')
            ->add_fields([
                Field::make('complex', 'page_sections', __('Sections', 'lemur'))
                    ->set_layout('tabbed-vertical')
                    ->set_collapsed(true)
                    ->setup_labels([
                        'plural_name' => __('Sections', 'lemur'),
                        'singular_name' => __('Section', 'lemur'),
                    ])
                    ->add_fields('hero', __('Hero', 'lemur'), self::getHeroFields())
                    ->add_fields('text_image', __('Texte + Image', 'lemur'), self::getTextImageFields())
                    ->add_fields('features', __('Features', 'lemur'), self::getFeaturesFields())
                    ->add_fields('cta', __('Call to Action', 'lemur'), self::getCtaFields())
                    ->add_fields('gallery', __('Galerie', 'lemur'), self::getGalleryFields())
                    ->add_fields('pricing', __('Tarifs', 'lemur'), self::getPricingFields())
                    ->add_fields('faq_inline', __('FAQ', 'lemur'), self::getFaqFields())
                    ->add_fields('testimonials', __('Témoignages', 'lemur'), self::getTestimonialsFields())
                    ->add_fields('timeline', __('Chronologie', 'lemur'), self::getTimelineFields())
                    ->add_fields('team', __('Équipe', 'lemur'), self::getTeamFields())
                    ->add_fields('stats', __('Chiffres clés', 'lemur'), self::getStatsFields())
                    ->add_fields('values', __('Valeurs', 'lemur'), self::getValuesFields())
                    ->add_fields('partners', __('Partenaires', 'lemur'), self::getPartnersFields())
                    ->add_fields('activities', __('Activités', 'lemur'), self::getActivitiesFields())
                    ->add_fields('levels', __('Niveaux', 'lemur'), self::getLevelsFields())
                    ->add_fields('locations', __('Lieux', 'lemur'), self::getLocationsFields())
                    ->add_fields('equipment', __('Matériel', 'lemur'), self::getEquipmentFields())
                    ->add_fields('events_list', __('Événements', 'lemur'), self::getEventsListFields())
                    ->add_fields('contact_info', __('Contact', 'lemur'), self::getContactInfoFields())
                    ->add_fields('adhesion_formules', __('Formules adhésion', 'lemur'), self::getAdhesionFormulesFields())
                    ->add_fields('transparence', __('Transparence', 'lemur'), self::getTransparenceFields())
                    ->add_fields('whats_included', __('Ce qui est inclus', 'lemur'), self::getWhatsIncludedFields())
                    ->add_fields('licence_decouverte', __('Licence découverte', 'lemur'), self::getLicenceDecouverteFields())
                    ->add_fields('process', __('Processus/Étapes', 'lemur'), self::getProcessFields())
                    ->add_fields('spacer', __('Espacement', 'lemur'), self::getSpacerFields())
                    ->add_fields('html_custom', __('HTML personnalisé', 'lemur'), self::getHtmlCustomFields()),
            ]);
    }

    /**
     * Get Hero block fields
     *
     * @return array<int, \Carbon_Fields\Field\Field>
     */
    private static function getHeroFields(): array
    {
        return [
            Field::make('text', 'title', __('Titre', 'lemur'))
                ->set_width(50),

            Field::make('text', 'subtitle', __('Sous-titre', 'lemur'))
                ->set_width(50),

            Field::make('image', 'background_image', __('Image de fond', 'lemur'))
                ->set_value_type('url')
                ->set_width(50),

            Field::make('file', 'background_video', __('Vidéo de fond', 'lemur'))
                ->set_type(['video'])
                ->set_value_type('url')
                ->set_width(50),

            Field::make('text', 'cta_text', __('Texte du bouton', 'lemur'))
                ->set_width(50),

            Field::make('text', 'cta_link', __('Lien du bouton', 'lemur'))
                ->set_attribute('placeholder', 'https://')
                ->set_width(50),

            Field::make('color', 'overlay_color', __('Couleur de superposition', 'lemur'))
                ->set_alpha_enabled(true)
                ->set_default_value('rgba(0,0,0,0.4)')
                ->set_width(33),

            Field::make('select', 'height', __('Hauteur', 'lemur'))
                ->set_options([
                    'full' => __('Plein écran (100vh)', 'lemur'),
                    'large' => __('Grand (80vh)', 'lemur'),
                    'medium' => __('Moyen (60vh)', 'lemur'),
                    'small' => __('Petit (40vh)', 'lemur'),
                ])
                ->set_default_value('large')
                ->set_width(33),

            Field::make('select', 'text_align', __('Alignement du texte', 'lemur'))
                ->set_options([
                    'left' => __('Gauche', 'lemur'),
                    'center' => __('Centre', 'lemur'),
                    'right' => __('Droite', 'lemur'),
                ])
                ->set_default_value('center')
                ->set_width(33),
        ];
    }

    /**
     * Get Text + Image block fields
     *
     * @return array<int, \Carbon_Fields\Field\Field>
     */
    private static function getTextImageFields(): array
    {
        return [
            Field::make('text', 'title', __('Titre', 'lemur')),

            Field::make('rich_text', 'content', __('Contenu', 'lemur')),

            Field::make('image', 'image', __('Image', 'lemur'))
                ->set_value_type('id'),

            Field::make('select', 'layout', __('Disposition', 'lemur'))
                ->set_options([
                    'image_left' => __('Image à gauche', 'lemur'),
                    'image_right' => __('Image à droite', 'lemur'),
                ])
                ->set_default_value('image_right')
                ->set_width(50),

            Field::make('color', 'background_color', __('Couleur de fond', 'lemur'))
                ->set_width(50),
        ];
    }

    /**
     * Get Features block fields
     *
     * @return array<int, \Carbon_Fields\Field\Field>
     */
    private static function getFeaturesFields(): array
    {
        return [
            Field::make('text', 'title', __('Titre de section', 'lemur'))
                ->set_width(50),

            Field::make('select', 'columns', __('Nombre de colonnes', 'lemur'))
                ->set_options([
                    '2' => __('2 colonnes', 'lemur'),
                    '3' => __('3 colonnes', 'lemur'),
                    '4' => __('4 colonnes', 'lemur'),
                ])
                ->set_default_value('3')
                ->set_width(50),

            Field::make('textarea', 'subtitle', __('Sous-titre', 'lemur'))
                ->set_rows(2),

            Field::make('complex', 'items', __('Features', 'lemur'))
                ->set_collapsed(true)
                ->set_header_template('<%- title || "Nouvelle feature" %>')
                ->add_fields([
                    Field::make('text', 'icon', __('Icône', 'lemur'))
                        ->set_help_text(__('Emoji ou nom d\'icône (ex: climbing, calendar)', 'lemur'))
                        ->set_width(20),

                    Field::make('text', 'title', __('Titre', 'lemur'))
                        ->set_width(80),

                    Field::make('textarea', 'description', __('Description', 'lemur'))
                        ->set_rows(3),
                ]),
        ];
    }

    /**
     * Get CTA block fields
     *
     * @return array<int, \Carbon_Fields\Field\Field>
     */
    private static function getCtaFields(): array
    {
        return [
            Field::make('text', 'title', __('Titre', 'lemur')),

            Field::make('textarea', 'description', __('Description', 'lemur'))
                ->set_rows(3),

            Field::make('text', 'button_text', __('Texte du bouton', 'lemur'))
                ->set_width(50),

            Field::make('text', 'button_link', __('Lien du bouton', 'lemur'))
                ->set_attribute('placeholder', 'https://')
                ->set_width(50),

            Field::make('color', 'background_color', __('Couleur de fond', 'lemur'))
                ->set_width(50),

            Field::make('image', 'background_image', __('Image de fond', 'lemur'))
                ->set_value_type('url')
                ->set_width(50),
        ];
    }

    /**
     * Get Gallery block fields
     *
     * @return array<int, \Carbon_Fields\Field\Field>
     */
    private static function getGalleryFields(): array
    {
        return [
            Field::make('text', 'title', __('Titre', 'lemur'))
                ->set_width(50),

            Field::make('select', 'columns', __('Colonnes', 'lemur'))
                ->set_options([
                    '2' => __('2 colonnes', 'lemur'),
                    '3' => __('3 colonnes', 'lemur'),
                    '4' => __('4 colonnes', 'lemur'),
                ])
                ->set_default_value('3')
                ->set_width(25),

            Field::make('checkbox', 'lightbox_enabled', __('Lightbox', 'lemur'))
                ->set_option_value('yes')
                ->set_default_value(true)
                ->set_width(25),

            Field::make('media_gallery', 'images', __('Images', 'lemur'))
                ->set_type(['image']),
        ];
    }

    /**
     * Get Pricing block fields
     *
     * @return array<int, \Carbon_Fields\Field\Field>
     */
    private static function getPricingFields(): array
    {
        return [
            Field::make('text', 'title', __('Titre', 'lemur')),

            Field::make('complex', 'plans', __('Formules', 'lemur'))
                ->set_collapsed(true)
                ->set_header_template('<%- name || "Nouvelle formule" %>')
                ->add_fields([
                    Field::make('text', 'name', __('Nom de la formule', 'lemur'))
                        ->set_width(50),

                    Field::make('checkbox', 'highlighted', __('Mettre en avant', 'lemur'))
                        ->set_option_value('yes')
                        ->set_width(50),

                    Field::make('text', 'price', __('Prix', 'lemur'))
                        ->set_width(50),

                    Field::make('text', 'period', __('Période', 'lemur'))
                        ->set_help_text(__('Ex: /an, /mois', 'lemur'))
                        ->set_width(50),

                    Field::make('textarea', 'features', __('Inclus (un par ligne)', 'lemur'))
                        ->set_rows(5),

                    Field::make('text', 'cta_text', __('Texte du bouton', 'lemur'))
                        ->set_width(50),

                    Field::make('text', 'cta_link', __('Lien du bouton', 'lemur'))
                        ->set_attribute('placeholder', 'https://')
                        ->set_width(50),
                ]),
        ];
    }

    /**
     * Get FAQ block fields
     *
     * @return array<int, \Carbon_Fields\Field\Field>
     */
    private static function getFaqFields(): array
    {
        return [
            Field::make('text', 'title', __('Titre', 'lemur'))
                ->set_width(50),

            Field::make('checkbox', 'use_cpt', __('Utiliser les FAQ du CPT', 'lemur'))
                ->set_option_value('yes')
                ->set_width(50),

            Field::make('association', 'faq_items', __('Questions sélectionnées', 'lemur'))
                ->set_types([['type' => 'post', 'post_type' => 'faq']])
                ->set_conditional_logic([[
                    'field' => 'use_cpt',
                    'value' => true,
                ]]),

            Field::make('complex', 'custom_questions', __('Questions personnalisées', 'lemur'))
                ->set_conditional_logic([[
                    'field' => 'use_cpt',
                    'value' => false,
                ]])
                ->set_collapsed(true)
                ->set_header_template('<%- question || "Nouvelle question" %>')
                ->add_fields([
                    Field::make('text', 'question', __('Question', 'lemur')),

                    Field::make('rich_text', 'answer', __('Réponse', 'lemur')),
                ]),
        ];
    }

    /**
     * Get Testimonials block fields
     *
     * @return array<int, \Carbon_Fields\Field\Field>
     */
    private static function getTestimonialsFields(): array
    {
        return [
            Field::make('text', 'title', __('Titre', 'lemur'))
                ->set_width(50),

            Field::make('select', 'layout', __('Disposition', 'lemur'))
                ->set_options([
                    'grid' => __('Grille', 'lemur'),
                    'slider' => __('Carrousel', 'lemur'),
                ])
                ->set_default_value('grid')
                ->set_width(50),

            Field::make('complex', 'testimonials', __('Témoignages', 'lemur'))
                ->set_collapsed(true)
                ->set_header_template('<%- author || "Nouveau témoignage" %>')
                ->add_fields([
                    Field::make('textarea', 'quote', __('Citation', 'lemur'))
                        ->set_rows(4),

                    Field::make('text', 'author', __('Auteur', 'lemur'))
                        ->set_width(50),

                    Field::make('text', 'role', __('Rôle/Fonction', 'lemur'))
                        ->set_width(50),

                    Field::make('image', 'photo', __('Photo', 'lemur'))
                        ->set_value_type('id'),
                ]),
        ];
    }

    /**
     * Get Timeline block fields
     *
     * @return array<int, \Carbon_Fields\Field\Field>
     */
    private static function getTimelineFields(): array
    {
        return [
            Field::make('text', 'title', __('Titre', 'lemur')),

            Field::make('complex', 'events', __('Événements', 'lemur'))
                ->set_collapsed(true)
                ->set_header_template('<%- date %> - <%- title || "Nouvel événement" %>')
                ->add_fields([
                    Field::make('text', 'date', __('Date', 'lemur'))
                        ->set_help_text(__('Ex: 1987, Janvier 2020', 'lemur'))
                        ->set_width(30),

                    Field::make('text', 'title', __('Titre', 'lemur'))
                        ->set_width(70),

                    Field::make('textarea', 'description', __('Description', 'lemur'))
                        ->set_rows(3),

                    Field::make('image', 'image', __('Image', 'lemur'))
                        ->set_value_type('id'),
                ]),
        ];
    }

    /**
     * Get Team block fields
     *
     * @return array<int, \Carbon_Fields\Field\Field>
     */
    private static function getTeamFields(): array
    {
        return [
            Field::make('text', 'title', __('Titre', 'lemur'))
                ->set_width(50),

            Field::make('checkbox', 'use_cpt', __('Utiliser les membres du CPT', 'lemur'))
                ->set_option_value('yes')
                ->set_default_value(true)
                ->set_width(50),

            Field::make('association', 'members', __('Membres sélectionnés', 'lemur'))
                ->set_types([['type' => 'post', 'post_type' => 'membre']])
                ->set_conditional_logic([[
                    'field' => 'use_cpt',
                    'value' => true,
                ]]),
        ];
    }

    /**
     * Get Events List block fields
     *
     * @return array<int, \Carbon_Fields\Field\Field>
     */
    private static function getEventsListFields(): array
    {
        return [
            Field::make('text', 'title', __('Titre', 'lemur'))
                ->set_width(50),

            Field::make('select', 'count', __('Nombre d\'événements', 'lemur'))
                ->set_options([
                    '2' => '2',
                    '3' => '3',
                    '4' => '4',
                    '6' => '6',
                    '8' => '8',
                    '12' => '12',
                ])
                ->set_default_value('4')
                ->set_width(25),

            Field::make('checkbox', 'show_past', __('Événements passés', 'lemur'))
                ->set_option_value('yes')
                ->set_default_value(false)
                ->set_width(25),

            Field::make('association', 'category_filter', __('Filtrer par catégorie', 'lemur'))
                ->set_types([['type' => 'term', 'taxonomy' => 'type-evenement']]),
        ];
    }

    /**
     * Get Contact Info block fields
     *
     * @return array<int, \Carbon_Fields\Field\Field>
     */
    private static function getContactInfoFields(): array
    {
        return [
            Field::make('text', 'title', __('Titre', 'lemur')),

            Field::make('checkbox', 'show_map', __('Afficher la carte', 'lemur'))
                ->set_option_value('yes')
                ->set_default_value(true)
                ->set_width(33),

            Field::make('checkbox', 'show_hours', __('Afficher les horaires', 'lemur'))
                ->set_option_value('yes')
                ->set_default_value(true)
                ->set_width(33),

            Field::make('checkbox', 'show_transport', __('Afficher les transports', 'lemur'))
                ->set_option_value('yes')
                ->set_default_value(true)
                ->set_width(33),

            Field::make('rich_text', 'custom_content', __('Contenu additionnel', 'lemur')),
        ];
    }

    /**
     * Get Spacer block fields
     *
     * @return array<int, \Carbon_Fields\Field\Field>
     */
    private static function getSpacerFields(): array
    {
        return [
            Field::make('select', 'height', __('Hauteur', 'lemur'))
                ->set_options([
                    'sm' => __('Petit (2rem)', 'lemur'),
                    'md' => __('Moyen (4rem)', 'lemur'),
                    'lg' => __('Grand (6rem)', 'lemur'),
                    'xl' => __('Très grand (8rem)', 'lemur'),
                ])
                ->set_default_value('md'),
        ];
    }

    /**
     * Get HTML Custom block fields
     *
     * @return array<int, \Carbon_Fields\Field\Field>
     */
    private static function getHtmlCustomFields(): array
    {
        return [
            Field::make('html', 'warning')
                ->set_html(sprintf(
                    '<div style="background:#fcf0f0;border:1px solid #d63638;border-radius:4px;padding:12px;margin-bottom:16px;">
                        <strong style="color:#d63638;">%s</strong>
                        <p style="margin:8px 0 0;color:#50575e;">%s</p>
                    </div>',
                    __('Ce bloc est réservé aux administrateurs techniques.', 'lemur'),
                    __('Le code HTML sera injecté tel quel. Vérifiez la sécurité de votre code.', 'lemur')
                )),

            Field::make('textarea', 'content', __('Code HTML', 'lemur'))
                ->set_rows(12)
                ->set_help_text(__('Code HTML personnalisé. Utilisez avec précaution.', 'lemur')),
        ];
    }

    /**
     * Get Stats block fields
     *
     * @return array<int, \Carbon_Fields\Field\Field>
     */
    private static function getStatsFields(): array
    {
        return [
            Field::make('text', 'title', __('Titre', 'lemur'))
                ->set_default_value(__('Le club en chiffres', 'lemur')),

            Field::make('complex', 'stats', __('Statistiques', 'lemur'))
                ->set_collapsed(true)
                ->set_header_template('<%- label || "Nouvelle statistique" %>')
                ->add_fields([
                    Field::make('text', 'icon', __('Icône (emoji)', 'lemur'))
                        ->set_width(15),

                    Field::make('text', 'number', __('Nombre', 'lemur'))
                        ->set_width(20),

                    Field::make('text', 'suffix', __('Suffixe', 'lemur'))
                        ->set_help_text(__('Ex: +, ans, etc.', 'lemur'))
                        ->set_width(15),

                    Field::make('text', 'label', __('Label', 'lemur'))
                        ->set_width(50),
                ]),
        ];
    }

    /**
     * Get Values block fields
     *
     * @return array<int, \Carbon_Fields\Field\Field>
     */
    private static function getValuesFields(): array
    {
        return [
            Field::make('text', 'title', __('Titre', 'lemur'))
                ->set_default_value(__('Nos valeurs', 'lemur')),

            Field::make('textarea', 'intro', __('Introduction', 'lemur'))
                ->set_rows(2),

            Field::make('complex', 'values', __('Valeurs', 'lemur'))
                ->set_collapsed(true)
                ->set_header_template('<%- title || "Nouvelle valeur" %>')
                ->add_fields([
                    Field::make('text', 'icon', __('Icône (emoji)', 'lemur'))
                        ->set_width(20),

                    Field::make('text', 'title', __('Titre', 'lemur'))
                        ->set_width(80),

                    Field::make('textarea', 'description', __('Description', 'lemur'))
                        ->set_rows(3),
                ]),
        ];
    }

    /**
     * Get Partners block fields
     *
     * @return array<int, \Carbon_Fields\Field\Field>
     */
    private static function getPartnersFields(): array
    {
        return [
            Field::make('text', 'title', __('Titre', 'lemur'))
                ->set_default_value(__('Nos partenaires', 'lemur')),

            Field::make('complex', 'partners', __('Partenaires', 'lemur'))
                ->set_collapsed(true)
                ->set_header_template('<%- name || "Nouveau partenaire" %>')
                ->add_fields([
                    Field::make('text', 'name', __('Nom', 'lemur'))
                        ->set_width(50),

                    Field::make('image', 'logo', __('Logo', 'lemur'))
                        ->set_value_type('id')
                        ->set_width(50),

                    Field::make('text', 'url', __('Site web', 'lemur'))
                        ->set_attribute('placeholder', 'https://'),
                ]),
        ];
    }

    /**
     * Get Activities block fields
     *
     * @return array<int, \Carbon_Fields\Field\Field>
     */
    private static function getActivitiesFields(): array
    {
        return [
            Field::make('text', 'title', __('Titre', 'lemur'))
                ->set_default_value(__('Nos activités', 'lemur')),

            Field::make('complex', 'activities', __('Activités', 'lemur'))
                ->set_collapsed(true)
                ->set_header_template('<%- title || "Nouvelle activité" %>')
                ->add_fields([
                    Field::make('text', 'icon', __('Icône (emoji)', 'lemur'))
                        ->set_width(15),

                    Field::make('text', 'title', __('Titre', 'lemur'))
                        ->set_width(85),

                    Field::make('image', 'image', __('Image', 'lemur'))
                        ->set_value_type('id'),

                    Field::make('rich_text', 'description', __('Description', 'lemur')),

                    Field::make('textarea', 'features', __('Points clés (un par ligne)', 'lemur'))
                        ->set_rows(4),

                    Field::make('text', 'level', __('Niveau requis', 'lemur'))
                        ->set_help_text(__('Ex: Tous niveaux, Intermédiaire, Confirmé', 'lemur')),
                ]),
        ];
    }

    /**
     * Get Levels block fields
     *
     * @return array<int, \Carbon_Fields\Field\Field>
     */
    private static function getLevelsFields(): array
    {
        return [
            Field::make('text', 'title', __('Titre', 'lemur'))
                ->set_default_value(__('Niveaux de pratique', 'lemur')),

            Field::make('textarea', 'intro', __('Introduction', 'lemur'))
                ->set_rows(2),

            Field::make('complex', 'levels', __('Niveaux', 'lemur'))
                ->set_collapsed(true)
                ->set_header_template('<%- title || "Nouveau niveau" %>')
                ->add_fields([
                    Field::make('text', 'icon', __('Icône (emoji)', 'lemur'))
                        ->set_width(15),

                    Field::make('text', 'title', __('Titre', 'lemur'))
                        ->set_width(60),

                    Field::make('color', 'color', __('Couleur', 'lemur'))
                        ->set_width(25),

                    Field::make('textarea', 'description', __('Description', 'lemur'))
                        ->set_rows(3),

                    Field::make('text', 'requirements', __('Prérequis', 'lemur')),
                ]),
        ];
    }

    /**
     * Get Locations block fields
     *
     * @return array<int, \Carbon_Fields\Field\Field>
     */
    private static function getLocationsFields(): array
    {
        return [
            Field::make('text', 'title', __('Titre', 'lemur'))
                ->set_default_value(__('Où grimper ?', 'lemur')),

            Field::make('complex', 'locations', __('Lieux', 'lemur'))
                ->set_collapsed(true)
                ->set_header_template('<%- name || "Nouveau lieu" %>')
                ->add_fields([
                    Field::make('text', 'name', __('Nom', 'lemur'))
                        ->set_width(50),

                    Field::make('select', 'type', __('Type', 'lemur'))
                        ->set_options([
                            'indoor' => __('Indoor (salle)', 'lemur'),
                            'outdoor' => __('Outdoor (falaise)', 'lemur'),
                            'bloc' => __('Bloc', 'lemur'),
                        ])
                        ->set_default_value('indoor')
                        ->set_width(50),

                    Field::make('image', 'image', __('Image', 'lemur'))
                        ->set_value_type('id'),

                    Field::make('text', 'address', __('Adresse', 'lemur')),

                    Field::make('textarea', 'description', __('Description', 'lemur'))
                        ->set_rows(3),

                    Field::make('text', 'map_link', __('Lien Google Maps', 'lemur'))
                        ->set_attribute('placeholder', 'https://maps.google.com/...'),
                ]),
        ];
    }

    /**
     * Get Equipment block fields
     *
     * @return array<int, \Carbon_Fields\Field\Field>
     */
    private static function getEquipmentFields(): array
    {
        return [
            Field::make('text', 'title', __('Titre', 'lemur'))
                ->set_default_value(__('Matériel', 'lemur')),

            Field::make('complex', 'provided', __('Fourni par le club', 'lemur'))
                ->set_collapsed(true)
                ->set_header_template('<%- item || "Nouvel équipement" %>')
                ->add_fields([
                    Field::make('text', 'item', __('Équipement', 'lemur'))
                        ->set_width(60),

                    Field::make('text', 'note', __('Note', 'lemur'))
                        ->set_help_text(__('Ex: sur demande, pour débutants', 'lemur'))
                        ->set_width(40),
                ]),

            Field::make('complex', 'required', __('À apporter', 'lemur'))
                ->set_collapsed(true)
                ->set_header_template('<%- item || "Nouvel équipement" %>')
                ->add_fields([
                    Field::make('text', 'item', __('Équipement', 'lemur'))
                        ->set_width(60),

                    Field::make('text', 'note', __('Note', 'lemur'))
                        ->set_help_text(__('Ex: obligatoire, recommandé', 'lemur'))
                        ->set_width(40),
                ]),
        ];
    }

    /**
     * Get Adhesion Formules block fields
     *
     * @return array<int, \Carbon_Fields\Field\Field>
     */
    private static function getAdhesionFormulesFields(): array
    {
        return [
            Field::make('text', 'title', __('Titre', 'lemur'))
                ->set_default_value(__('Choisissez votre adhésion', 'lemur')),

            Field::make('textarea', 'intro', __('Introduction', 'lemur'))
                ->set_rows(2)
                ->set_help_text(__('Texte d\'introduction sous le titre', 'lemur')),

            Field::make('checkbox', 'show_prix_conscient_banner', __('Afficher la bannière "Prix conscient"', 'lemur'))
                ->set_option_value('yes')
                ->set_default_value(true),
        ];
    }

    /**
     * Get Transparence block fields
     *
     * @return array<int, \Carbon_Fields\Field\Field>
     */
    private static function getTransparenceFields(): array
    {
        return [
            Field::make('text', 'title', __('Titre', 'lemur'))
                ->set_default_value(__('Où va votre cotisation ?', 'lemur')),

            Field::make('complex', 'items', __('Répartition', 'lemur'))
                ->set_collapsed(true)
                ->set_header_template('<%- label || "Nouveau poste" %> (<%- percentage %>%)')
                ->add_fields([
                    Field::make('text', 'label', __('Libellé', 'lemur'))
                        ->set_width(40),

                    Field::make('text', 'percentage', __('Pourcentage', 'lemur'))
                        ->set_attribute('type', 'number')
                        ->set_attribute('min', '0')
                        ->set_attribute('max', '100')
                        ->set_width(20),

                    Field::make('select', 'color', __('Couleur', 'lemur'))
                        ->set_options([
                            'primary'   => __('Primaire', 'lemur'),
                            'secondary' => __('Secondaire', 'lemur'),
                            'success'   => __('Succès (vert)', 'lemur'),
                            'warning'   => __('Avertissement (orange)', 'lemur'),
                            'neutral'   => __('Neutre (gris)', 'lemur'),
                        ])
                        ->set_default_value('primary')
                        ->set_width(20),

                    Field::make('text', 'description', __('Description', 'lemur'))
                        ->set_width(20),
                ]),
        ];
    }

    /**
     * Get What's Included block fields
     *
     * @return array<int, \Carbon_Fields\Field\Field>
     */
    private static function getWhatsIncludedFields(): array
    {
        return [
            Field::make('text', 'title', __('Titre', 'lemur'))
                ->set_default_value(__('Votre adhésion comprend', 'lemur')),

            Field::make('select', 'columns', __('Colonnes', 'lemur'))
                ->set_options([
                    '2' => __('2 colonnes', 'lemur'),
                    '3' => __('3 colonnes', 'lemur'),
                    '4' => __('4 colonnes', 'lemur'),
                ])
                ->set_default_value('3'),

            Field::make('complex', 'items', __('Éléments', 'lemur'))
                ->set_collapsed(true)
                ->set_header_template('<%- title || "Nouvel élément" %>')
                ->add_fields([
                    Field::make('text', 'icon', __('Icône', 'lemur'))
                        ->set_help_text(__('Nom d\'icône (climbing, calendar, users, etc.)', 'lemur'))
                        ->set_width(20),

                    Field::make('text', 'title', __('Titre', 'lemur'))
                        ->set_width(80),

                    Field::make('textarea', 'description', __('Description', 'lemur'))
                        ->set_rows(2),
                ]),
        ];
    }

    /**
     * Get Licence Découverte block fields
     *
     * @return array<int, \Carbon_Fields\Field\Field>
     */
    private static function getLicenceDecouverteFields(): array
    {
        return [
            Field::make('text', 'title', __('Titre', 'lemur'))
                ->set_default_value(__('Envie d\'essayer ?', 'lemur')),

            Field::make('textarea', 'text', __('Texte', 'lemur'))
                ->set_rows(3)
                ->set_default_value(__('Venez découvrir le club avec une licence découverte. Le coût réel est de 3€ (assurance FSGT), mais vous donnez ce que vous voulez.', 'lemur')),

            Field::make('text', 'min_price', __('Prix minimum', 'lemur'))
                ->set_default_value('3€'),
        ];
    }

    /**
     * Get Process block fields
     *
     * @return array<int, \Carbon_Fields\Field\Field>
     */
    private static function getProcessFields(): array
    {
        return [
            Field::make('text', 'title', __('Titre', 'lemur'))
                ->set_default_value(__('Comment adhérer ?', 'lemur')),

            Field::make('complex', 'steps', __('Étapes', 'lemur'))
                ->set_collapsed(true)
                ->set_header_template('<%- title || "Nouvelle étape" %>')
                ->add_fields([
                    Field::make('text', 'title', __('Titre', 'lemur')),

                    Field::make('textarea', 'description', __('Description', 'lemur'))
                        ->set_rows(2),
                ]),
        ];
    }
}
