<?php

/**
 * Meta Tags (Open Graph, Twitter Cards, SEO)
 *
 * @package Lemur\SEO
 */

declare(strict_types=1);

namespace Lemur\SEO;

use Lemur\Fields\ThemeOptions;

/**
 * Generates meta tags for SEO and social sharing
 */
class MetaTags
{
    /**
     * Initialize the module
     */
    public static function init(): void
    {
        $instance = new self();
        add_action('wp_head', [$instance, 'outputMetaTags'], 1);
        add_filter('document_title_parts', [$instance, 'customizeTitle'], 10);
        add_filter('pre_get_document_title', [$instance, 'getDocumentTitle'], 10);

        // Disable WordPress native robots meta (WP 5.7+) - we handle it ourselves
        remove_filter('wp_robots', 'wp_robots_max_image_preview_large');
        add_filter('wp_robots', [$instance, 'disableWpRobots'], 999);
    }

    /**
     * Disable WordPress native robots meta output
     *
     * We use our own robots meta tag via outputBasicMeta() which respects
     * Carbon Fields seo_robots setting per page.
     *
     * @param array<string, bool|string> $robots WordPress robots directives
     * @return array<string, bool|string> Empty array to prevent output
     */
    public function disableWpRobots(array $robots): array
    {
        // Return empty array to prevent WordPress from outputting robots meta
        return [];
    }

    /**
     * Output all meta tags
     */
    public function outputMetaTags(): void
    {
        $this->outputBasicMeta();
        $this->outputOpenGraph();
        $this->outputTwitterCards();
        $this->outputCanonical();
    }

    /**
     * Output basic meta tags
     */
    private function outputBasicMeta(): void
    {
        $description = $this->getDescription();
        $robots = $this->getRobots();

        if ($description) {
            printf(
                '<meta name="description" content="%s">' . "\n",
                esc_attr($description)
            );
        }

        printf(
            '<meta name="robots" content="%s">' . "\n",
            esc_attr($robots)
        );

        // Author for articles
        if (is_singular('post')) {
            printf(
                '<meta name="author" content="%s">' . "\n",
                esc_attr(get_the_author())
            );
        }

        // Locale
        echo '<meta property="og:locale" content="fr_FR">' . "\n";
    }

    /**
     * Output Open Graph tags
     */
    private function outputOpenGraph(): void
    {
        $og = [
            'og:type' => $this->getOgType(),
            'og:title' => $this->getTitle(),
            'og:description' => $this->getDescription(),
            'og:url' => $this->getCanonicalUrl(),
            'og:site_name' => get_bloginfo('name'),
            'og:image' => $this->getImage(),
        ];

        $image = $this->getImage();
        if ($image) {
            $og['og:image'] = $image;
            $og['og:image:width'] = '1200';
            $og['og:image:height'] = '630';
            $og['og:image:alt'] = $this->getTitle();
        }

        // Event specific
        if (is_singular('evenements') && function_exists('carbon_get_post_meta')) {
            $start_date = carbon_get_post_meta(get_the_ID(), 'event_start_date');
            $location = carbon_get_post_meta(get_the_ID(), 'event_location');

            if ($start_date) {
                $og['event:start_time'] = $start_date;
            }
            if ($location) {
                $og['event:location'] = $location;
            }
        }

        // Article specific
        if (is_singular('post')) {
            $og['article:published_time'] = get_the_date('c');
            $og['article:modified_time'] = get_the_modified_date('c');
            $og['article:author'] = get_the_author();

            // Categories
            $categories = get_the_category();
            if ($categories) {
                $og['article:section'] = $categories[0]->name;
            }

            // Tags
            $tags = get_the_tags();
            if ($tags) {
                foreach ($tags as $tag) {
                    printf(
                        '<meta property="article:tag" content="%s">' . "\n",
                        esc_attr($tag->name)
                    );
                }
            }
        }

        foreach ($og as $property => $content) {
            if ($content !== '' && $content !== null) {
                printf(
                    '<meta property="%s" content="%s">' . "\n",
                    esc_attr($property),
                    esc_attr($content)
                );
            }
        }
    }

    /**
     * Output Twitter Card tags
     */
    private function outputTwitterCards(): void
    {
        $twitter = [
            'twitter:card' => 'summary_large_image',
            'twitter:title' => $this->getTitle(),
            'twitter:description' => $this->getDescription(),
            'twitter:image' => $this->getImage(),
            'twitter:image:alt' => $this->getTitle(),
        ];

        // Twitter handle if configured
        if (function_exists('carbon_get_theme_option')) {
            $twitter_handle = carbon_get_theme_option(ThemeOptions::FIELD_TWITTER_HANDLE);
            if ($twitter_handle) {
                $twitter['twitter:site'] = '@' . ltrim($twitter_handle, '@');
            }
        }

        foreach ($twitter as $name => $content) {
            if ($content !== '' && $content !== null) {
                printf(
                    '<meta name="%s" content="%s">' . "\n",
                    esc_attr($name),
                    esc_attr($content)
                );
            }
        }
    }

    /**
     * Output canonical URL
     */
    private function outputCanonical(): void
    {
        $canonical = $this->getCanonicalUrl();

        if ($canonical) {
            printf(
                '<link rel="canonical" href="%s">' . "\n",
                esc_url($canonical)
            );
        }
    }

    /**
     * Get optimized title
     *
     * Cascade: post meta → auto-generated
     */
    private function getTitle(): string
    {
        // 1. Custom SEO title from post meta
        if (is_singular() && function_exists('carbon_get_post_meta')) {
            $custom_title = carbon_get_post_meta(get_the_ID(), 'seo_title');
            if ($custom_title !== null && $custom_title !== '') {
                return sanitize_text_field($custom_title);
            }
        }

        // 2. Auto-generated based on context
        if (is_front_page()) {
            return get_bloginfo('name') . ' - ' . get_bloginfo('description');
        }

        if (is_singular()) {
            return get_the_title();
        }

        if (is_post_type_archive('evenements')) {
            return 'Événements - ' . get_bloginfo('name');
        }

        if (is_archive()) {
            return get_the_archive_title() . ' - ' . get_bloginfo('name');
        }

        if (is_search()) {
            return sprintf(
                'Recherche : %s - %s',
                get_search_query(),
                get_bloginfo('name')
            );
        }

        if (is_404()) {
            return 'Page non trouvée - ' . get_bloginfo('name');
        }

        return get_bloginfo('name');
    }

    /**
     * Get optimized description
     *
     * Cascade: post meta → auto-generated → site description fallback
     */
    private function getDescription(): string
    {
        $max_length = 160;

        // 1. Custom SEO description from post meta
        if (is_singular() && function_exists('carbon_get_post_meta')) {
            $custom_desc = carbon_get_post_meta(get_the_ID(), 'seo_description');
            if ($custom_desc !== null && $custom_desc !== '') {
                return $this->truncate(sanitize_text_field($custom_desc), $max_length);
            }
        }

        // 2. Auto-generated based on context
        if (is_front_page()) {
            return $this->truncate($this->getSiteDescription(), $max_length);
        }

        if (is_singular()) {
            // Excerpt
            $excerpt = get_the_excerpt();
            if ($excerpt) {
                return $this->truncate($excerpt, $max_length);
            }

            // Content start
            $content = get_the_content();
            $stripped = wp_strip_all_tags($content);
            if ($stripped !== '') {
                return $this->truncate($stripped, $max_length);
            }
        }

        if (is_post_type_archive('evenements')) {
            return \__('Découvrez tous les événements et sorties du club.', 'lemur');
        }

        // Taxonomy archive
        if (is_category() || is_tag() || is_tax()) {
            $term = get_queried_object();
            if ($term && !empty($term->description)) {
                return $this->truncate($term->description, $max_length);
            }
        }

        // 3. Final fallback: site description from theme options
        return $this->truncate($this->getSiteDescription(), $max_length);
    }

    /**
     * Get site description from theme options or WordPress settings
     *
     * Source: Lemur > Identité > Description courte
     */
    private function getSiteDescription(): string
    {
        if (function_exists('carbon_get_theme_option')) {
            $site_desc = carbon_get_theme_option(ThemeOptions::FIELD_SITE_DESCRIPTION);
            if ($site_desc !== null && $site_desc !== '') {
                return sanitize_text_field($site_desc);
            }
        }

        // Fallback to WordPress tagline
        return get_bloginfo('description');
    }

    /**
     * Get image for social sharing
     */
    private function getImage(): string
    {
        // Custom SEO image from post meta
        if (is_singular() && function_exists('carbon_get_post_meta')) {
            $custom_image = carbon_get_post_meta(get_the_ID(), 'seo_image');
            if ($custom_image) {
                $url = wp_get_attachment_url($custom_image);
                if ($url) {
                    return $url;
                }
            }

            // Featured image
            if (has_post_thumbnail()) {
                $url = get_the_post_thumbnail_url(get_the_ID(), 'large');
                if ($url) {
                    return $url;
                }
            }
        }

        // Default image from theme options
        if (function_exists('carbon_get_theme_option')) {
            $default_image = carbon_get_theme_option(ThemeOptions::FIELD_SEO_DEFAULT_IMAGE);
            if ($default_image) {
                $url = wp_get_attachment_url($default_image);
                if ($url) {
                    return $url;
                }
            }

            // Logo as fallback
            $logo = carbon_get_theme_option(ThemeOptions::FIELD_LOGO);
            if ($logo) {
                $url = wp_get_attachment_url($logo);
                if ($url) {
                    return $url;
                }
            }
        }

        return '';
    }

    /**
     * Get canonical URL
     */
    private function getCanonicalUrl(): string
    {
        // Custom canonical from post meta
        if (is_singular() && function_exists('carbon_get_post_meta')) {
            $custom_canonical = carbon_get_post_meta(get_the_ID(), 'seo_canonical');
            if ($custom_canonical) {
                return esc_url_raw($custom_canonical);
            }
            return get_permalink();
        }

        if (is_front_page()) {
            return home_url('/');
        }

        if (is_post_type_archive()) {
            $link = get_post_type_archive_link(get_post_type());
            return $link ? $link : '';
        }

        if (is_category() || is_tag() || is_tax()) {
            $term = get_queried_object();
            if ($term) {
                $link = get_term_link($term);
                return is_wp_error($link) ? '' : $link;
            }
        }

        if (is_author()) {
            return get_author_posts_url(get_queried_object_id());
        }

        // Current URL
        global $wp;
        $current_url = home_url(add_query_arg([], $wp->request ?? ''));

        return trailingslashit($current_url);
    }

    /**
     * Get Open Graph type
     */
    private function getOgType(): string
    {
        if (is_front_page()) {
            return 'website';
        }

        if (is_singular('post')) {
            return 'article';
        }

        if (is_singular('evenements')) {
            return 'event';
        }

        return 'website';
    }

    /**
     * Get robots directives
     */
    private function getRobots(): string
    {
        $robots = ['index', 'follow'];

        // Search and paginated archives
        if (is_search() || is_paged()) {
            $robots = ['noindex', 'follow'];
        }

        // Non-published posts
        if (is_singular() && get_post_status() !== 'publish') {
            $robots = ['noindex', 'nofollow'];
        }

        // 404
        if (is_404()) {
            $robots = ['noindex', 'follow'];
        }

        // Custom per page
        if (is_singular() && function_exists('carbon_get_post_meta')) {
            $custom_robots = carbon_get_post_meta(get_the_ID(), 'seo_robots');
            if ($custom_robots) {
                return sanitize_text_field($custom_robots);
            }
        }

        return implode(', ', $robots);
    }

    /**
     * Customize document title parts
     */
    public function customizeTitle(array $title_parts): array
    {
        $title_parts['site'] = get_bloginfo('name');
        return $title_parts;
    }

    /**
     * Get full document title
     */
    public function getDocumentTitle(): string
    {
        $title = $this->getTitle();
        $site_name = get_bloginfo('name');

        if (is_front_page()) {
            return $title;
        }

        return $title . ' | ' . $site_name;
    }

    /**
     * Truncate text cleanly
     */
    private function truncate(string $text, int $max_length): string
    {
        $text = wp_strip_all_tags($text);
        $text = (string) preg_replace('/\s+/', ' ', $text);
        $text = trim($text);

        if (mb_strlen($text) <= $max_length) {
            return $text;
        }

        $text = mb_substr($text, 0, $max_length - 3);
        $last_space = mb_strrpos($text, ' ');

        if ($last_space !== false) {
            $text = mb_substr($text, 0, $last_space);
        }

        return $text . '...';
    }
}
