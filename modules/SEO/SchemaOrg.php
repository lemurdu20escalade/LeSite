<?php

/**
 * Schema.org JSON-LD Generator
 *
 * @package Lemur\SEO
 */

declare(strict_types=1);

namespace Lemur\SEO;

use Lemur\Fields\ThemeOptions;

/**
 * Generates structured data (Schema.org) in JSON-LD format
 */
class SchemaOrg
{
    /**
     * Collected schemas to output
     */
    private array $schemas = [];

    /**
     * Cached theme options
     */
    private static ?array $optionsCache = null;

    /**
     * Initialize the module
     */
    public static function init(): void
    {
        $instance = new self();
        add_action('wp', [$instance, 'buildSchemas'], 10);
        add_action('wp_head', [$instance, 'outputSchemas'], 1);

        // Admin bar debug link
        if (current_user_can('manage_options')) {
            add_action('admin_bar_menu', [$instance, 'addDebugLink'], 100);
        }
    }

    /**
     * Build schemas based on current context
     */
    public function buildSchemas(): void
    {
        // Base schemas on all pages
        $this->addLocalBusiness();
        $this->addWebSite();

        // Contextual schemas
        if (is_singular('evenements')) {
            $this->addEvent();
        }

        if (is_page('club') || is_page('le-club')) {
            $this->addOrganization();
        }

        if (is_page('faq')) {
            $this->addFAQPage();
        }

        if (is_page('contact')) {
            $this->addContactPage();
        }

        if (is_singular('post')) {
            $this->addArticle();
        }

        // Breadcrumbs on all pages except front page
        if (!is_front_page()) {
            $this->addBreadcrumbList();
        }
    }

    /**
     * Schema LocalBusiness (Sports Club)
     */
    private function addLocalBusiness(): void
    {
        $options = $this->getThemeOptions();

        $schema = [
            '@type' => 'SportsClub',
            '@id' => home_url('/#sportsclub'),
            'name' => $options['club_name'] ?: get_bloginfo('name'),
            'description' => $options['club_description'] ?: get_bloginfo('description'),
            'url' => home_url('/'),
            'telephone' => $options['phone'] ?: '',
            'email' => $options['email'] ?: '',
            'address' => [
                '@type' => 'PostalAddress',
                'streetAddress' => $options['address'] ?: '',
                'addressLocality' => $options['city'] ?: '',
                'postalCode' => $options['postal_code'] ?: '',
                'addressCountry' => 'FR',
            ],
            'sameAs' => array_values(array_filter([
                $options['facebook'] ?: '',
                $options['instagram'] ?: '',
                $options['youtube'] ?: '',
            ])),
            'sport' => 'Escalade',
            'priceRange' => '€€',
        ];

        // Geo coordinates if available
        if (!empty($options['latitude']) && !empty($options['longitude'])) {
            $schema['geo'] = [
                '@type' => 'GeoCoordinates',
                'latitude' => $options['latitude'],
                'longitude' => $options['longitude'],
            ];
        }

        // Logo
        $logo_id = $options['logo_id'];
        if ($logo_id) {
            $logo_url = wp_get_attachment_url($logo_id);
            if ($logo_url) {
                $schema['logo'] = [
                    '@type' => 'ImageObject',
                    'url' => $logo_url,
                    'width' => 300,
                    'height' => 300,
                ];
            }
        }

        // Opening hours
        $opening_hours = $options['opening_hours'];
        if (!empty($opening_hours) && is_array($opening_hours)) {
            $schema['openingHoursSpecification'] = $this->formatOpeningHours($opening_hours);
        }

        $this->schemas[] = $schema;
    }

    /**
     * Schema WebSite
     */
    private function addWebSite(): void
    {
        $schema = [
            '@type' => 'WebSite',
            '@id' => home_url('/#website'),
            'url' => home_url('/'),
            'name' => get_bloginfo('name'),
            'description' => get_bloginfo('description'),
            'publisher' => [
                '@id' => home_url('/#sportsclub'),
            ],
            'inLanguage' => 'fr-FR',
            'potentialAction' => [
                '@type' => 'SearchAction',
                'target' => [
                    '@type' => 'EntryPoint',
                    'urlTemplate' => home_url('/?s={search_term_string}'),
                ],
                'query-input' => 'required name=search_term_string',
            ],
        ];

        $this->schemas[] = $schema;
    }

    /**
     * Schema Event
     */
    private function addEvent(): void
    {
        $post = get_post();
        if (!$post) {
            return;
        }

        if (!function_exists('carbon_get_post_meta')) {
            return;
        }

        $start_date = carbon_get_post_meta($post->ID, 'event_start_date');
        $end_date = carbon_get_post_meta($post->ID, 'event_end_date');
        $location = carbon_get_post_meta($post->ID, 'event_location');
        $price = carbon_get_post_meta($post->ID, 'event_price');
        $max_participants = carbon_get_post_meta($post->ID, 'event_max_participants');

        $schema = [
            '@type' => 'Event',
            '@id' => get_permalink($post->ID) . '#event',
            'name' => get_the_title($post),
            'description' => wp_strip_all_tags(get_the_excerpt($post)),
            'url' => get_permalink($post->ID),
            'startDate' => $start_date ?: '',
            'endDate' => $end_date ?: $start_date,
            'eventStatus' => 'https://schema.org/EventScheduled',
            'eventAttendanceMode' => 'https://schema.org/OfflineEventAttendanceMode',
            'organizer' => [
                '@id' => home_url('/#sportsclub'),
            ],
        ];

        // Featured image
        if (has_post_thumbnail($post->ID)) {
            $schema['image'] = get_the_post_thumbnail_url($post->ID, 'large');
        }

        // Location
        if ($location) {
            $options = $this->getThemeOptions();
            $schema['location'] = [
                '@type' => 'Place',
                'name' => $location,
                'address' => [
                    '@type' => 'PostalAddress',
                    'addressLocality' => $options['city'] ?: '',
                    'addressCountry' => 'FR',
                ],
            ];
        }

        // Price/Offers
        if ($price !== null && $price !== '') {
            $schema['offers'] = [
                '@type' => 'Offer',
                'price' => floatval($price),
                'priceCurrency' => 'EUR',
                'availability' => 'https://schema.org/InStock',
                'url' => get_permalink($post->ID),
            ];

            if ($max_participants) {
                $schema['offers']['inventoryLevel'] = [
                    '@type' => 'QuantitativeValue',
                    'value' => intval($max_participants),
                ];
            }
        }

        $this->schemas[] = $schema;
    }

    /**
     * Schema Organization
     */
    private function addOrganization(): void
    {
        $options = $this->getThemeOptions();

        $schema = [
            '@type' => 'SportsOrganization',
            '@id' => home_url('/#organization'),
            'name' => $options['club_name'] ?: get_bloginfo('name'),
            'description' => $options['club_description'] ?: get_bloginfo('description'),
            'url' => home_url('/'),
            'sport' => 'Escalade',
            'memberOf' => [
                '@type' => 'SportsOrganization',
                'name' => 'FSGT',
                'url' => 'https://www.fsgt.org',
            ],
            'contactPoint' => [
                '@type' => 'ContactPoint',
                'telephone' => $options['phone'] ?: '',
                'email' => $options['email'] ?: '',
                'contactType' => 'customer service',
                'availableLanguage' => 'French',
            ],
        ];

        // Logo
        $logo_id = $options['logo_id'];
        if ($logo_id) {
            $logo_url = wp_get_attachment_url($logo_id);
            if ($logo_url) {
                $schema['logo'] = $logo_url;
            }
        }

        $this->schemas[] = $schema;
    }

    /**
     * Schema FAQPage
     */
    private function addFAQPage(): void
    {
        $faqs = get_posts([
            'post_type' => 'faq',
            'posts_per_page' => -1,
            'post_status' => 'publish',
            'orderby' => 'menu_order',
            'order' => 'ASC',
        ]);

        if (empty($faqs)) {
            return;
        }

        $mainEntity = [];

        foreach ($faqs as $faq) {
            $answer = '';
            if (function_exists('carbon_get_post_meta')) {
                $answer = carbon_get_post_meta($faq->ID, 'faq_answer');
            }
            if (empty($answer)) {
                $answer = $faq->post_content;
            }

            $mainEntity[] = [
                '@type' => 'Question',
                'name' => get_the_title($faq),
                'acceptedAnswer' => [
                    '@type' => 'Answer',
                    'text' => wp_strip_all_tags(apply_filters('the_content', $answer)),
                ],
            ];
        }

        $schema = [
            '@type' => 'FAQPage',
            '@id' => get_permalink() . '#faqpage',
            'mainEntity' => $mainEntity,
        ];

        $this->schemas[] = $schema;
    }

    /**
     * Schema ContactPage
     */
    private function addContactPage(): void
    {
        $schema = [
            '@type' => 'ContactPage',
            '@id' => get_permalink() . '#contactpage',
            'name' => get_the_title(),
            'url' => get_permalink(),
            'mainEntity' => [
                '@id' => home_url('/#sportsclub'),
            ],
        ];

        $this->schemas[] = $schema;
    }

    /**
     * Schema Article
     */
    private function addArticle(): void
    {
        $post = get_post();
        if (!$post) {
            return;
        }

        $schema = [
            '@type' => 'Article',
            '@id' => get_permalink($post->ID) . '#article',
            'headline' => get_the_title($post),
            'description' => wp_strip_all_tags(get_the_excerpt($post)),
            'url' => get_permalink($post->ID),
            'datePublished' => get_the_date('c', $post),
            'dateModified' => get_the_modified_date('c', $post),
            'author' => [
                '@type' => 'Person',
                'name' => get_the_author_meta('display_name', $post->post_author),
            ],
            'publisher' => [
                '@id' => home_url('/#sportsclub'),
            ],
            'mainEntityOfPage' => [
                '@type' => 'WebPage',
                '@id' => get_permalink($post->ID),
            ],
            'inLanguage' => 'fr-FR',
        ];

        // Featured image
        if (has_post_thumbnail($post->ID)) {
            $schema['image'] = [
                '@type' => 'ImageObject',
                'url' => get_the_post_thumbnail_url($post->ID, 'large'),
                'width' => 1200,
                'height' => 630,
            ];
        }

        $this->schemas[] = $schema;
    }

    /**
     * Schema BreadcrumbList
     */
    private function addBreadcrumbList(): void
    {
        $items = [];
        $position = 1;

        // Home
        $items[] = [
            '@type' => 'ListItem',
            'position' => $position++,
            'name' => 'Accueil',
            'item' => home_url('/'),
        ];

        // Archive for CPT
        if (is_singular('evenements')) {
            $items[] = [
                '@type' => 'ListItem',
                'position' => $position++,
                'name' => 'Événements',
                'item' => get_post_type_archive_link('evenements'),
            ];
        }

        // Parent page
        $post_id = get_the_ID();
        if (is_page() && $post_id && wp_get_post_parent_id($post_id)) {
            $parent = get_post(wp_get_post_parent_id($post_id));
            if ($parent) {
                $items[] = [
                    '@type' => 'ListItem',
                    'position' => $position++,
                    'name' => get_the_title($parent),
                    'item' => get_permalink($parent),
                ];
            }
        }

        // Current page/post (without item property for last element)
        $items[] = [
            '@type' => 'ListItem',
            'position' => $position,
            'name' => get_the_title(),
        ];

        $schema = [
            '@type' => 'BreadcrumbList',
            '@id' => get_permalink() . '#breadcrumb',
            'itemListElement' => $items,
        ];

        $this->schemas[] = $schema;
    }

    /**
     * Format opening hours for Schema.org
     */
    private function formatOpeningHours(array $hours): array
    {
        $daysMap = [
            'lundi' => 'Monday',
            'mardi' => 'Tuesday',
            'mercredi' => 'Wednesday',
            'jeudi' => 'Thursday',
            'vendredi' => 'Friday',
            'samedi' => 'Saturday',
            'dimanche' => 'Sunday',
        ];

        $specifications = [];

        foreach ($hours as $hour) {
            if (empty($hour['day']) || empty($hour['open']) || empty($hour['close'])) {
                continue;
            }

            $day = $hour['day'];
            $dayEnglish = $daysMap[$day] ?? $day;

            $specifications[] = [
                '@type' => 'OpeningHoursSpecification',
                'dayOfWeek' => $dayEnglish,
                'opens' => $hour['open'],
                'closes' => $hour['close'],
            ];
        }

        return $specifications;
    }

    /**
     * Get theme options with caching
     */
    private function getThemeOptions(): array
    {
        if (self::$optionsCache !== null) {
            return self::$optionsCache;
        }

        if (!function_exists('carbon_get_theme_option')) {
            self::$optionsCache = [
                'club_name' => '',
                'club_description' => '',
                'address' => '',
                'city' => '',
                'postal_code' => '',
                'phone' => '',
                'email' => '',
                'latitude' => '',
                'longitude' => '',
                'logo_id' => 0,
                'facebook' => '',
                'instagram' => '',
                'youtube' => '',
                'opening_hours' => [],
            ];
            return self::$optionsCache;
        }

        self::$optionsCache = [
            'club_name' => carbon_get_theme_option(ThemeOptions::FIELD_CLUB_NAME) ?: '',
            'club_description' => carbon_get_theme_option(ThemeOptions::FIELD_CLUB_DESCRIPTION) ?: '',
            'address' => carbon_get_theme_option(ThemeOptions::FIELD_ADDRESS) ?: '',
            'city' => carbon_get_theme_option(ThemeOptions::FIELD_CITY) ?: '',
            'postal_code' => carbon_get_theme_option(ThemeOptions::FIELD_POSTAL_CODE) ?: '',
            'phone' => carbon_get_theme_option(ThemeOptions::FIELD_PHONE) ?: '',
            'email' => carbon_get_theme_option(ThemeOptions::FIELD_EMAIL) ?: '',
            'latitude' => carbon_get_theme_option(ThemeOptions::FIELD_LATITUDE) ?: '',
            'longitude' => carbon_get_theme_option(ThemeOptions::FIELD_LONGITUDE) ?: '',
            'logo_id' => carbon_get_theme_option(ThemeOptions::FIELD_LOGO) ?: 0,
            'facebook' => carbon_get_theme_option(ThemeOptions::FIELD_FACEBOOK) ?: '',
            'instagram' => carbon_get_theme_option(ThemeOptions::FIELD_INSTAGRAM) ?: '',
            'youtube' => carbon_get_theme_option(ThemeOptions::FIELD_YOUTUBE) ?: '',
            'opening_hours' => carbon_get_theme_option(ThemeOptions::FIELD_SCHEMA_HOURS) ?: [],
        ];

        return self::$optionsCache;
    }

    /**
     * Output schemas as JSON-LD
     */
    public function outputSchemas(): void
    {
        if (empty($this->schemas)) {
            return;
        }

        $graph = [
            '@context' => 'https://schema.org',
            '@graph' => $this->schemas,
        ];

        $json = wp_json_encode($graph, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

        if ($json === false) {
            return;
        }

        echo '<script type="application/ld+json">' . "\n";
        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- JSON-LD is valid JSON
        echo $json . "\n";
        echo '</script>' . "\n";
    }

    /**
     * Add debug link to admin bar
     */
    public function addDebugLink(\WP_Admin_Bar $admin_bar): void
    {
        if (is_admin()) {
            return;
        }

        $current_url = is_singular() ? get_permalink() : home_url($_SERVER['REQUEST_URI'] ?? '/');

        $admin_bar->add_node([
            'id' => 'lemur-schema-test',
            'title' => 'Test Schema.org',
            'href' => 'https://validator.schema.org/#url=' . urlencode($current_url),
            'meta' => [
                'target' => '_blank',
                'title' => 'Tester les données structurées sur Schema.org',
            ],
        ]);
    }
}
