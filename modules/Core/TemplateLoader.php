<?php

declare(strict_types=1);

namespace Lemur\Core;

/**
 * Custom template loader for templates in /templates/ folder.
 *
 * Handles:
 * - Page templates (templates/pages/)
 * - CPT single templates (templates/singles/)
 * - CPT archive templates (templates/archives/)
 *
 * @package Lemur
 */
class TemplateLoader
{
    private const TEMPLATES_DIR = 'templates';

    /**
     * CPT templates mapping: type => [slug => filename]
     */
    private const CPT_TEMPLATES = [
        'single' => [
            'evenements' => 'singles/single-evenements.php',
            'collectifs' => 'singles/single-collectifs.php',
        ],
        'archive' => [
            'evenements' => 'archives/archive-evenements.php',
            'collectifs' => 'archives/archive-collectifs.php',
        ],
    ];

    /**
     * Page templates mapping: filename => label
     */
    private const PAGE_TEMPLATES = [
        'templates/pages/page-actu.php' => 'Actualités',
        'templates/pages/page-adhesion.php' => 'Adhésion',
        'templates/pages/page-annuaire.php' => 'Annuaire',
        'templates/pages/page-calendrier-membres.php' => 'Calendrier Membres',
        'templates/pages/page-club.php' => 'Le Club',
        'templates/pages/page-contact.php' => 'Contact',
        'templates/pages/page-documents.php' => 'Documents',
        'templates/pages/page-espace-membre.php' => 'Espace Membre',
        'templates/pages/page-faq.php' => 'FAQ',
        'templates/pages/page-galerie.php' => 'Galerie',
        'templates/pages/page-grimper.php' => 'Grimper',
        'templates/pages/page-lasso.php' => 'Le Club (Lasso)',
        'templates/pages/page-les-collectifs.php' => 'Les Collectifs',
        'templates/pages/page-planning.php' => 'Planning',
        'templates/pages/page-todo-list.php' => 'Tâches',
    ];

    public static function init(): void
    {
        // CPT templates
        add_filter('single_template', [self::class, 'loadSingleTemplate'], 10);
        add_filter('archive_template', [self::class, 'loadArchiveTemplate'], 10);

        // Page templates from subdirectory
        add_filter('theme_page_templates', [self::class, 'registerPageTemplates'], 10);
        add_filter('page_template', [self::class, 'loadPageTemplate'], 10);
    }

    /**
     * Register page templates from /templates/pages/ folder.
     *
     * @param array<string, string> $templates
     * @return array<string, string>
     */
    public static function registerPageTemplates(array $templates): array
    {
        return array_merge($templates, self::PAGE_TEMPLATES);
    }

    /**
     * Load page template from /templates/pages/ folder.
     * Handles both new paths (templates/pages/xxx.php) and legacy paths (page-xxx.php).
     */
    public static function loadPageTemplate(string $template): string
    {
        $page_template = get_page_template_slug();

        if (empty($page_template)) {
            return $template;
        }

        // Check if it's one of our templates (new path)
        if (isset(self::PAGE_TEMPLATES[$page_template])) {
            $custom_template = get_theme_file_path($page_template);

            if (file_exists($custom_template)) {
                return $custom_template;
            }
        }

        // Handle legacy template slugs (page-xxx.php -> templates/pages/page-xxx.php)
        if (strpos($page_template, 'page-') === 0) {
            $new_path = self::TEMPLATES_DIR . '/pages/' . $page_template;
            $custom_template = get_theme_file_path($new_path);

            if (file_exists($custom_template)) {
                return $custom_template;
            }
        }

        return $template;
    }

    /**
     * Load single CPT templates from /templates/ folder.
     */
    public static function loadSingleTemplate(string $template): string
    {
        return self::resolveTemplate('single', $template);
    }

    /**
     * Load archive CPT templates from /templates/ folder.
     */
    public static function loadArchiveTemplate(string $template): string
    {
        return self::resolveTemplate('archive', $template);
    }

    /**
     * Resolve template path from /templates/ folder.
     */
    private static function resolveTemplate(string $type, string $default): string
    {
        $post_type = get_query_var('post_type');

        if (is_array($post_type)) {
            $post_type = reset($post_type);
        }

        if (empty($post_type) || !isset(self::CPT_TEMPLATES[$type][$post_type])) {
            return $default;
        }

        $custom_template = get_theme_file_path(
            self::TEMPLATES_DIR . '/' . self::CPT_TEMPLATES[$type][$post_type]
        );

        if (file_exists($custom_template)) {
            return $custom_template;
        }

        return $default;
    }
}
