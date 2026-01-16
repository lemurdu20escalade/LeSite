<?php
/**
 * Image Optimizer Module
 *
 * Handles image optimization with lazy loading, custom sizes, and WebP support.
 *
 * @package Lemur\Images
 */

declare(strict_types=1);

namespace Lemur\Images;

/**
 * Optimize images for performance
 */
class Optimizer
{
    /**
     * Cached custom sizes (after filter applied)
     *
     * @var array<string, array{width: int, height: int, crop: bool, label: string}>|null
     */
    private static ?array $cached_sizes = null;

    /**
     * Custom image sizes for the theme
     *
     * @var array<string, array{width: int, height: int, crop: bool, label: string}>
     */
    private const CUSTOM_SIZES = [
        'lemur-card' => [
            'width' => 400,
            'height' => 300,
            'crop' => true,
            'label' => 'Card (400x300)',
        ],
        'lemur-card-large' => [
            'width' => 600,
            'height' => 450,
            'crop' => true,
            'label' => 'Card Large (600x450)',
        ],
        'lemur-hero' => [
            'width' => 1920,
            'height' => 800,
            'crop' => true,
            'label' => 'Hero (1920x800)',
        ],
        'lemur-hero-mobile' => [
            'width' => 768,
            'height' => 500,
            'crop' => true,
            'label' => 'Hero Mobile (768x500)',
        ],
        'lemur-gallery-thumb' => [
            'width' => 300,
            'height' => 300,
            'crop' => true,
            'label' => 'Gallery Thumbnail (300x300)',
        ],
        'lemur-gallery-full' => [
            'width' => 1200,
            'height' => 900,
            'crop' => false,
            'label' => 'Gallery Full (1200x900)',
        ],
        'lemur-team' => [
            'width' => 300,
            'height' => 300,
            'crop' => true,
            'label' => 'Team Member (300x300)',
        ],
    ];

    /**
     * Initialize the image optimizer
     */
    public static function init(): void
    {
        // Register custom image sizes
        add_action('after_setup_theme', [self::class, 'registerImageSizes']);

        // Add size names to media library selector
        add_filter('image_size_names_choose', [self::class, 'addImageSizeNames']);

        // Add lazy loading to images
        add_filter('wp_get_attachment_image_attributes', [self::class, 'addLazyLoading'], 10, 3);
        add_filter('the_content', [self::class, 'addLazyLoadingToContent']);
        add_filter('post_thumbnail_html', [self::class, 'addLazyLoadingToThumbnail'], 10, 5);

        // Enable WebP output (WordPress 5.8+)
        add_filter('image_editor_output_format', [self::class, 'enableWebpOutput']);
    }

    /**
     * Get custom image sizes (filterable, cached)
     *
     * @return array<string, array{width: int, height: int, crop: bool, label: string}>
     */
    public static function getCustomSizes(): array
    {
        if (self::$cached_sizes === null) {
            self::$cached_sizes = apply_filters('lemur_image_sizes', self::CUSTOM_SIZES);
        }

        return self::$cached_sizes;
    }

    /**
     * Register custom image sizes
     */
    public static function registerImageSizes(): void
    {
        // Register each custom size (filterable via lemur_image_sizes)
        foreach (self::getCustomSizes() as $name => $size) {
            add_image_size(
                $name,
                $size['width'],
                $size['height'],
                $size['crop']
            );
        }
    }

    /**
     * Add custom size names to the media library size selector
     *
     * @param array<string, string> $sizes Existing size names
     * @return array<string, string>
     */
    public static function addImageSizeNames(array $sizes): array
    {
        foreach (self::getCustomSizes() as $name => $size) {
            $sizes[$name] = $size['label'];
        }

        return $sizes;
    }

    /**
     * Add lazy loading attributes to attachment images
     *
     * @param array<string, string> $attr       Image attributes
     * @param \WP_Post              $attachment Attachment post object
     * @param string|int[]          $size       Image size
     * @return array<string, string>
     */
    public static function addLazyLoading(array $attr, \WP_Post $attachment, $size): array
    {
        // Don't override if already set
        if (!isset($attr['loading'])) {
            $attr['loading'] = 'lazy';
        }

        // Add async decoding for better performance
        if (!isset($attr['decoding'])) {
            $attr['decoding'] = 'async';
        }

        return $attr;
    }

    /**
     * Add lazy loading to images in post content
     *
     * @param string $content Post content
     * @return string
     */
    public static function addLazyLoadingToContent(string $content): string
    {
        if (empty($content)) {
            return $content;
        }

        // Add loading="lazy" and decoding="async" to images that don't have them
        return preg_replace_callback(
            '/<img([^>]+)>/i',
            function (array $matches): string {
                $img = $matches[0];

                // Skip if loading attribute already exists
                if (strpos($img, 'loading=') !== false) {
                    return $img;
                }

                // Add attributes
                return str_replace('<img', '<img loading="lazy" decoding="async"', $img);
            },
            $content
        ) ?? $content;
    }

    /**
     * Add lazy loading to post thumbnails
     *
     * @param string       $html              Thumbnail HTML
     * @param int          $post_id           Post ID
     * @param int          $post_thumbnail_id Thumbnail attachment ID
     * @param string|int[] $size              Image size
     * @param string|array $attr              Image attributes
     * @return string
     */
    public static function addLazyLoadingToThumbnail(
        string $html,
        int $post_id,
        int $post_thumbnail_id,
        $size,
        $attr
    ): string {
        if (empty($html)) {
            return $html;
        }

        // Add loading="lazy" if not present
        if (strpos($html, 'loading=') === false) {
            $html = str_replace('<img', '<img loading="lazy" decoding="async"', $html);
        }

        return $html;
    }

    /**
     * Enable WebP output for uploaded images
     *
     * Converts JPEG and PNG to WebP format when possible.
     * Requires WordPress 5.8+ and GD/Imagick with WebP support.
     *
     * @param array<string, string> $formats Output format mapping
     * @return array<string, string>
     */
    public static function enableWebpOutput(array $formats): array
    {
        // Only enable WebP if the server supports it
        if (!wp_image_editor_supports(['mime_type' => 'image/webp'])) {
            return $formats;
        }

        // Convert JPEG and PNG to WebP
        $formats['image/jpeg'] = 'image/webp';
        $formats['image/png'] = 'image/webp';

        return $formats;
    }

    /**
     * Get dimensions for a custom image size
     *
     * @param string $name Size name
     * @return array{width: int, height: int, crop: bool, label: string}|null
     */
    public static function getImageSize(string $name): ?array
    {
        $sizes = self::getCustomSizes();
        return $sizes[$name] ?? null;
    }

    /**
     * Get all custom image sizes
     *
     * @return array<string, array{width: int, height: int, crop: bool, label: string}>
     */
    public static function getAllImageSizes(): array
    {
        return self::getCustomSizes();
    }
}
