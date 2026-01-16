<?php
/**
 * Theme helper functions
 *
 * @package Lemur
 */

declare(strict_types=1);

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Get theme option value
 *
 * @param string $key     Option key
 * @param mixed  $default Default value
 * @return mixed
 */
function lemur_get_option(string $key, mixed $default = null): mixed
{
    // Carbon Fields must be booted before calling this
    if (!function_exists('carbon_get_theme_option')) {
        return $default;
    }

    return carbon_get_theme_option($key) ?? $default;
}

/**
 * Check if current user is a member
 *
 * @return bool
 */
function lemur_is_member(): bool
{
    if (!is_user_logged_in()) {
        return false;
    }

    $user = wp_get_current_user();
    return in_array('lemur_member', $user->roles, true);
}

/**
 * Get SVG icon markup
 *
 * @deprecated Use lemur_ui_icon() instead for inline SVG icons.
 *
 * @param string $name Icon name
 * @param string $class Additional CSS classes
 * @return string SVG markup
 */
function lemur_icon(string $name, string $class = ''): string
{
    // Try file-based icon first
    $iconPath = \Lemur\Core\Theme::getPath("assets/icons/{$name}.svg");

    if (file_exists($iconPath)) {
        // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
        $svg = file_get_contents($iconPath);

        if ($class && $svg) {
            $svg = str_replace('<svg', '<svg class="' . esc_attr($class) . '"', $svg);
        }

        return $svg ?: '';
    }

    // Fallback to inline UI icon
    return lemur_ui_icon($name, ['class' => $class]);
}

/**
 * Format a date in French
 *
 * @param string|int $date Date string or timestamp
 * @param string     $format Date format
 * @return string
 */
function lemur_format_date(string|int $date, string $format = 'j F Y'): string
{
    $timestamp = is_numeric($date) ? (int) $date : strtotime($date);

    if (!$timestamp) {
        return '';
    }

    return wp_date($format, $timestamp) ?: '';
}

/**
 * Truncate text to a maximum length
 *
 * @param string $text   Text to truncate
 * @param int    $length Maximum length
 * @param string $suffix Suffix to add if truncated
 * @return string
 */
function lemur_truncate(string $text, int $length = 150, string $suffix = '...'): string
{
    $text = wp_strip_all_tags($text);

    if (mb_strlen($text) <= $length) {
        return $text;
    }

    return mb_substr($text, 0, $length) . $suffix;
}

/**
 * Debug helper - only outputs in development
 *
 * @param mixed $data Data to debug
 */
function lemur_debug(mixed $data): void
{
    if (!defined('WP_DEBUG') || !WP_DEBUG) {
        return;
    }

    // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log, WordPress.PHP.DevelopmentFunctions.error_log_print_r
    error_log('[Lemur Debug] ' . print_r($data, true));
}

/**
 * Get the logo attachment ID
 *
 * @param string $type Logo type: 'main' or 'footer'
 * @return int|null Attachment ID or null if not set
 */
function lemur_get_logo_id(string $type = 'main'): ?int
{
    $key = $type === 'footer'
        ? \Lemur\Fields\ThemeOptions::FIELD_LOGO_FOOTER
        : \Lemur\Fields\ThemeOptions::FIELD_LOGO;

    $id = lemur_get_option($key);

    return $id ? (int) $id : null;
}

/**
 * Get the logo URL
 *
 * @param string $type Logo type: 'main' or 'footer'
 * @return string|null Logo URL or null if not set
 */
function lemur_get_logo_url(string $type = 'main'): ?string
{
    $id = lemur_get_logo_id($type);

    if (!$id) {
        return null;
    }

    $url = wp_get_attachment_url($id);

    return $url ? esc_url($url) : null;
}

/**
 * Get contact information
 *
 * @param string $field Field name: 'phone', 'email', 'address', 'maps_url'
 * @return string|null
 */
function lemur_get_contact(string $field): ?string
{
    $keys = [
        'phone' => \Lemur\Fields\ThemeOptions::FIELD_PHONE,
        'email' => \Lemur\Fields\ThemeOptions::FIELD_EMAIL,
        'address' => \Lemur\Fields\ThemeOptions::FIELD_ADDRESS,
        'maps_url' => \Lemur\Fields\ThemeOptions::FIELD_MAPS_URL,
    ];

    if (!isset($keys[$field])) {
        return null;
    }

    $value = lemur_get_option($keys[$field]);

    if ($value === null || $value === '') {
        return null;
    }

    return match ($field) {
        'email' => sanitize_email($value),
        'maps_url' => esc_url($value),
        'phone' => (string) preg_replace('/[^\d\s+\-\(\)]/', '', $value),
        'address' => sanitize_textarea_field($value),
        default => sanitize_text_field($value),
    };
}

/**
 * Get social media URL
 *
 * @param string $network Network name: 'facebook', 'instagram', 'youtube'
 * @return string|null
 */
function lemur_get_social_url(string $network): ?string
{
    $keys = [
        'facebook' => \Lemur\Fields\ThemeOptions::FIELD_FACEBOOK,
        'instagram' => \Lemur\Fields\ThemeOptions::FIELD_INSTAGRAM,
        'youtube' => \Lemur\Fields\ThemeOptions::FIELD_YOUTUBE,
    ];

    if (!isset($keys[$network])) {
        return null;
    }

    $url = lemur_get_option($keys[$network]);

    return $url ? esc_url($url) : null;
}

/**
 * Get all social media URLs
 *
 * @return array<string, string> Array of network => url
 */
function lemur_get_social_urls(): array
{
    $networks = ['facebook', 'instagram', 'youtube'];
    $urls = [];

    foreach ($networks as $network) {
        $url = lemur_get_social_url($network);
        if ($url) {
            $urls[$network] = $url;
        }
    }

    return $urls;
}

/**
 * Format phone number for tel: link
 *
 * Handles international format (+33) and local numbers.
 *
 * @param string $phone Phone number
 * @return string Formatted phone for tel: attribute
 */
function lemur_format_phone_link(string $phone): string
{
    $phone = trim($phone);

    // Check if starts with + (international format)
    $hasPlus = str_starts_with($phone, '+');

    // Remove all non-digit characters
    $digits = preg_replace('/[^\d]/', '', $phone);

    // Re-add + prefix if it was international
    return $hasPlus ? '+' . $digits : $digits;
}

/**
 * Get page by path with static cache
 *
 * Prevents multiple DB queries for the same page path.
 *
 * @param string $path Page path/slug
 * @return \WP_Post|null
 */
function lemur_get_page_by_path(string $path): ?\WP_Post
{
    static $cache = [];

    if (!isset($cache[$path])) {
        $cache[$path] = get_page_by_path($path);
    }

    return $cache[$path];
}

/**
 * Get transport type icon SVG
 *
 * Returns official RATP/IDFM-style SVG icons for transport types.
 * Icons optimized for 24px display with proper legibility.
 *
 * @param string $type Transport type: 'metro', 'bus', 'tram', 'rer'
 * @return string SVG markup
 *
 * @see https://www.ratp.fr/ (official reference)
 */
function lemur_transport_icon(string $type): string
{
    $icons = [
        // Metro: Official RATP circle with M - optimized for 24px
        'metro' => '<svg width="24" height="24" viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="11" fill="#fff" stroke="#1d1d1b" stroke-width="1.5"/><path fill="#1d1d1b" d="M17.5 17V7.5c0-.6-.3-1.2-1.3-1.2-.7 0-1 .3-1.3 1l-3.2 6.6h0l-3.2-6.6c-.3-.7-.6-1-1.3-1-1 0-1.3.6-1.3 1.2V17c0 .6.4.9 1 .9s1-.3 1-.9V10h0l2.8 6c.2.4.5.6 1 .6s.7-.2 1-.6l2.8-6h0v7c0 .6.5.9 1 .9s1-.3 1-.9"/></svg>',

        // Bus: Official IDFM green badge with BUS text
        'bus' => '<svg width="36" height="24" viewBox="0 0 36 24" aria-hidden="true"><rect width="36" height="24" rx="4" fill="#00814f"/><text x="18" y="16.5" text-anchor="middle" fill="#fff" font-family="Arial,Helvetica,sans-serif" font-size="13" font-weight="bold">BUS</text></svg>',

        // Tram: Official RATP style with T in circle
        'tram' => '<svg width="24" height="24" viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="11" fill="#fff" stroke="#1d1d1b" stroke-width="1.5"/><text x="12" y="17" text-anchor="middle" fill="#1d1d1b" font-family="Arial,Helvetica,sans-serif" font-size="15" font-weight="bold">T</text></svg>',

        // RER: Official RATP rounded rectangle with RER
        'rer' => '<svg width="36" height="24" viewBox="0 0 36 24" aria-hidden="true"><rect width="36" height="24" rx="4" fill="#fff" stroke="#1d1d1b" stroke-width="1.5"/><text x="18" y="16.5" text-anchor="middle" fill="#1d1d1b" font-family="Arial,Helvetica,sans-serif" font-size="12" font-weight="bold">RER</text></svg>',
    ];

    return $icons[$type] ?? '';
}

/**
 * Get transport lines
 *
 * Returns structured array of nearby public transport lines.
 * Handles comma-separated line numbers by creating separate entries.
 *
 * @return array<int, array{type: string, line: string, station: string}>
 */
function lemur_get_transport_lines(): array
{
    $lines = lemur_get_option(\Lemur\Fields\ThemeOptions::FIELD_TRANSPORT_LINES, []);

    if (!is_array($lines)) {
        return [];
    }

    $result = [];

    foreach ($lines as $item) {
        $type = sanitize_text_field($item['type'] ?? '');
        $lineValue = sanitize_text_field($item['line'] ?? '');
        $station = sanitize_text_field($item['station'] ?? '');

        // Skip incomplete entries
        if (empty($type) || empty($lineValue)) {
            continue;
        }

        // Handle comma-separated line numbers (e.g., "3bis, 11" becomes two entries)
        $lineNumbers = array_map('trim', explode(',', $lineValue));

        foreach ($lineNumbers as $line) {
            if (empty($line)) {
                continue;
            }

            $result[] = [
                'type'    => $type,
                'line'    => $line,
                'station' => $station,
            ];
        }
    }

    return $result;
}

/**
 * Get favicon URL
 *
 * @return string|null
 */
function lemur_get_favicon_url(): ?string
{
    $id = lemur_get_option(\Lemur\Fields\ThemeOptions::FIELD_FAVICON);

    if (!$id) {
        return null;
    }

    $url = wp_get_attachment_url((int) $id);

    return $url ? esc_url($url) : null;
}

/**
 * Get site description
 *
 * Returns custom site description from theme options, falls back to WordPress tagline.
 *
 * @return string
 */
function lemur_get_site_description(): string
{
    $description = lemur_get_option(\Lemur\Fields\ThemeOptions::FIELD_SITE_DESCRIPTION);

    if ($description) {
        return sanitize_textarea_field($description);
    }

    return get_bloginfo('description');
}

/**
 * Get hours note
 *
 * @return string|null
 */
function lemur_get_hours_note(): ?string
{
    $note = lemur_get_option(\Lemur\Fields\ThemeOptions::FIELD_HOURS_NOTE);

    return $note ? sanitize_text_field($note) : null;
}

/**
 * Get schedule data
 *
 * Returns structured schedule array from complex field.
 *
 * @return array<int, array{day: string, hours: string, location: string, activity: string}>
 */
function lemur_get_schedule(): array
{
    $schedule = lemur_get_option(\Lemur\Fields\ThemeOptions::FIELD_SCHEDULE, []);

    if (!is_array($schedule)) {
        return [];
    }

    return array_map(function ($slot) {
        return [
            'day'      => sanitize_text_field($slot['day'] ?? ''),
            'hours'    => sanitize_text_field($slot['hours'] ?? ''),
            'location' => sanitize_text_field($slot['location'] ?? ''),
            'activity' => sanitize_text_field($slot['activity'] ?? ''),
        ];
    }, $schedule);
}

/**
 * Get adhesion link URL
 *
 * @return string|null
 */
function lemur_get_adhesion_link(): ?string
{
    $url = lemur_get_option(\Lemur\Fields\ThemeOptions::FIELD_ADHESION_LINK);

    return $url ? esc_url($url) : null;
}

/**
 * Get adhesion button text
 *
 * @return string
 */
function lemur_get_adhesion_text(): string
{
    $text = lemur_get_option(\Lemur\Fields\ThemeOptions::FIELD_ADHESION_TEXT);

    return $text ? sanitize_text_field($text) : __('Nous rejoindre', 'lemur');
}

/**
 * Get Galette URL for member area
 *
 * @return string|null
 */
function lemur_get_galette_url(): ?string
{
    $url = lemur_get_option(\Lemur\Fields\ThemeOptions::FIELD_GALETTE_URL);

    return $url ? esc_url($url) : null;
}

/**
 * Get external links
 *
 * @return array<int, array{label: string, url: string, new_tab: bool}>
 */
function lemur_get_external_links(): array
{
    $links = lemur_get_option(\Lemur\Fields\ThemeOptions::FIELD_EXTERNAL_LINKS, []);

    if (!is_array($links)) {
        return [];
    }

    return array_map(function ($link) {
        return [
            'label'   => sanitize_text_field($link['label'] ?? ''),
            'url'     => esc_url($link['url'] ?? ''),
            'new_tab' => !empty($link['new_tab']),
        ];
    }, $links);
}

/**
 * Check if any social links are configured
 *
 * @return bool
 */
function lemur_has_social_links(): bool
{
    return !empty(lemur_get_social_urls());
}

/**
 * Get all social links with labels
 *
 * @return array<string, array{url: string, label: string}>
 */
function lemur_get_social_links(): array
{
    $urls = lemur_get_social_urls();
    $links = [];

    $labels = [
        'facebook'  => __('Facebook', 'lemur'),
        'instagram' => __('Instagram', 'lemur'),
        'youtube'   => __('YouTube', 'lemur'),
    ];

    foreach ($urls as $network => $url) {
        $links[$network] = [
            'url'   => $url,
            'label' => $labels[$network] ?? ucfirst($network),
        ];
    }

    return $links;
}

// ==========================================================================
// Page Builder Helpers
// ==========================================================================

/**
 * Render all page sections
 *
 * Iterates through the page_sections complex field and includes
 * the appropriate template part for each section type.
 *
 * @param int|null $post_id Optional post ID, defaults to current post
 */
function lemur_render_page_sections(?int $post_id = null): void
{
    if (!function_exists('carbon_get_post_meta')) {
        return;
    }

    if ($post_id === null) {
        $post_id = get_the_ID();
    }

    if (!$post_id) {
        return;
    }

    $sections = carbon_get_post_meta($post_id, 'page_sections');

    if (empty($sections) || !is_array($sections)) {
        return;
    }

    foreach ($sections as $index => $section) {
        $type = $section['_type'] ?? '';

        if (empty($type)) {
            continue;
        }

        // Sanitize type for template filename
        $type_safe = sanitize_file_name($type);
        $template_path = get_template_directory() . "/templates/parts/blocks/block-{$type_safe}.php";

        if (!file_exists($template_path)) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
                error_log("[Lemur] Block template not found: block-{$type_safe}.php");
            }
            continue;
        }

        // Pass section data to template
        set_query_var('block_data', $section);
        set_query_var('block_index', $index);

        /**
         * Fires before a block is rendered
         *
         * @param string $type    Block type name
         * @param array  $section Block data
         * @param int    $index   Block index
         */
        do_action('lemur_before_block', $type, $section, $index);

        get_template_part('templates/parts/blocks/block', $type_safe);

        /**
         * Fires after a block is rendered
         *
         * @param string $type    Block type name
         * @param array  $section Block data
         * @param int    $index   Block index
         */
        do_action('lemur_after_block', $type, $section, $index);
    }
}

/**
 * Get current block data in a template
 *
 * Call this function at the top of block templates to retrieve
 * the data passed by lemur_render_page_sections().
 *
 * @return array<string, mixed>
 */
function lemur_get_block_data(): array
{
    $data = get_query_var('block_data', []);

    return is_array($data) ? $data : [];
}

/**
 * Get current block index
 *
 * Returns the index of the current block in the sections array.
 * Useful for unique IDs or alternate styling.
 *
 * @return int
 */
function lemur_get_block_index(): int
{
    return (int) get_query_var('block_index', 0);
}

/**
 * Check if page has any sections
 *
 * @param int|null $post_id Optional post ID, defaults to current post
 * @return bool
 */
function lemur_has_page_sections(?int $post_id = null): bool
{
    if (!function_exists('carbon_get_post_meta')) {
        return false;
    }

    if ($post_id === null) {
        $post_id = get_the_ID();
    }

    if (!$post_id) {
        return false;
    }

    $sections = carbon_get_post_meta($post_id, 'page_sections');

    return !empty($sections) && is_array($sections);
}

/**
 * Get section count for a page
 *
 * @param int|null $post_id Optional post ID
 * @return int
 */
function lemur_get_section_count(?int $post_id = null): int
{
    if (!function_exists('carbon_get_post_meta')) {
        return 0;
    }

    if ($post_id === null) {
        $post_id = get_the_ID();
    }

    if (!$post_id) {
        return 0;
    }

    $sections = carbon_get_post_meta($post_id, 'page_sections');

    return is_array($sections) ? count($sections) : 0;
}

/**
 * Sanitize CSS color value
 *
 * Validates and returns only safe CSS color formats.
 * Prevents CSS injection attacks via inline styles.
 *
 * Supported formats:
 * - Hex: #fff, #ffffff, #ffffffff (with alpha)
 * - RGB: rgb(255, 128, 0), rgb(255 128 0), rgba(255, 128, 0, 0.5)
 * - HSL: hsl(120, 50%, 50%), hsla(120, 50%, 50%, 0.5)
 * - Keywords: transparent, currentColor, inherit
 *
 * @param string $color   Raw color value
 * @param string $default Default color if validation fails
 * @return string Sanitized color or default
 */
function lemur_sanitize_css_color(string $color, string $default = ''): string
{
    $color = trim($color);

    if (empty($color)) {
        return $default;
    }

    // Safe CSS color keywords
    $safe_keywords = [
        'transparent',
        'currentcolor',
        'inherit',
        'initial',
        'unset',
    ];

    if (in_array(strtolower($color), $safe_keywords, true)) {
        return $color;
    }

    // Hex colors: #fff, #ffffff, #ffffffff
    if (preg_match('/^#[0-9a-fA-F]{3,8}$/', $color)) {
        return $color;
    }

    // RGB/RGBA: rgb(255, 128, 0) or rgb(255 128 0) or rgba(255, 128, 0, 0.5)
    // Supports both comma and space syntax, percentages, and decimal alpha
    if (preg_match('/^rgba?\(\s*[\d.]+%?[\s,]+[\d.]+%?[\s,]+[\d.]+%?(\s*[,\/]\s*[\d.]+%?)?\s*\)$/i', $color)) {
        return $color;
    }

    // HSL/HSLA: hsl(120, 50%, 50%) or hsla(120, 50%, 50%, 0.5)
    if (preg_match('/^hsla?\(\s*[\d.]+\s*[,\s]\s*[\d.]+%\s*[,\s]\s*[\d.]+%(\s*[,\/]\s*[\d.]+%?)?\s*\)$/i', $color)) {
        return $color;
    }

    return $default;
}

/**
 * Sanitize CSS URL value
 *
 * Validates URL for use in CSS url() function.
 *
 * @param string $url Raw URL value
 * @return string|null Sanitized URL or null if invalid
 */
function lemur_sanitize_css_url(string $url): ?string
{
    $url = trim($url);

    if (empty($url)) {
        return null;
    }

    // Must be a valid URL
    if (!filter_var($url, FILTER_VALIDATE_URL)) {
        return null;
    }

    // Only allow http/https protocols
    $parsed = wp_parse_url($url);
    if (!isset($parsed['scheme']) || !in_array($parsed['scheme'], ['http', 'https'], true)) {
        return null;
    }

    return esc_url($url);
}

// =============================================================================
// Image Helper Functions
// =============================================================================

/**
 * Display a responsive image with lazy loading
 *
 * @param int    $attachment_id Attachment ID
 * @param string $size          Image size name
 * @param array  $attr          Additional HTML attributes
 * @param bool   $lazy          Enable lazy loading (default: true)
 */
function lemur_responsive_image(
    int $attachment_id,
    string $size = 'large',
    array $attr = [],
    bool $lazy = true
): void {
    if (!$attachment_id) {
        return;
    }

    // Default attributes
    $default_attr = [
        'class' => 'responsive-image',
    ];

    // Disable lazy loading for above-the-fold images
    if (!$lazy) {
        $default_attr['loading'] = 'eager';
        $default_attr['fetchpriority'] = 'high';
    }

    $attr = array_merge($default_attr, $attr);

    echo wp_get_attachment_image($attachment_id, $size, false, $attr);
}

/**
 * Display an image with art direction using <picture> element
 *
 * Allows different image sources for different viewport sizes.
 *
 * @param int   $attachment_id Attachment ID
 * @param array $sources       Sources by breakpoint (each with 'size' and 'media')
 * @param array $attr          HTML attributes for the <img> element
 * @param bool  $lazy          Enable lazy loading (default: true)
 */
function lemur_picture(int $attachment_id, array $sources = [], array $attr = [], bool $lazy = true): void
{
    if (!$attachment_id) {
        return;
    }

    // Default sources for hero images
    $default_sources = [
        'mobile' => [
            'size' => 'lemur-hero-mobile',
            'media' => '(max-width: 767px)',
        ],
        'desktop' => [
            'size' => 'lemur-hero',
            'media' => '(min-width: 768px)',
        ],
    ];

    $sources = array_merge($default_sources, $sources);

    // Get fallback image
    $img_src = wp_get_attachment_image_url($attachment_id, 'lemur-hero');
    $img_alt = get_post_meta($attachment_id, '_wp_attachment_image_alt', true);

    // Get dimensions for CLS prevention (use hero size, not original image)
    $hero_size = \Lemur\Images\Optimizer::getImageSize('lemur-hero');
    $width = $hero_size['width'] ?? 1920;
    $height = $hero_size['height'] ?? 800;

    // Loading attributes
    $loading_attr = $lazy ? 'lazy' : 'eager';
    $priority_attr = $lazy ? '' : ' fetchpriority="high"';

    // Build attribute string
    $attr_string = '';
    foreach ($attr as $key => $value) {
        $attr_string .= sprintf(' %s="%s"', esc_attr($key), esc_attr($value));
    }
    ?>
    <picture>
        <?php foreach ($sources as $source) : ?>
            <?php
            $srcset = wp_get_attachment_image_srcset($attachment_id, $source['size']);
            if ($srcset) :
            ?>
                <source
                    media="<?php echo esc_attr($source['media']); ?>"
                    srcset="<?php echo esc_attr($srcset); ?>"
                >
            <?php endif; ?>
        <?php endforeach; ?>

        <img
            src="<?php echo esc_url($img_src); ?>"
            alt="<?php echo esc_attr($img_alt); ?>"
            width="<?php echo (int) $width; ?>"
            height="<?php echo (int) $height; ?>"
            loading="<?php echo esc_attr($loading_attr); ?>"
            decoding="async"<?php echo $priority_attr; ?>
            <?php echo $attr_string; ?>
        >
    </picture>
    <?php
}

/**
 * Generate a placeholder SVG as a data URI
 *
 * @param int    $width  Width in pixels
 * @param int    $height Height in pixels
 * @param string $color  Background color (hex)
 * @return string Base64-encoded SVG data URI
 */
function lemur_placeholder_svg(int $width = 400, int $height = 300, string $color = '#e5e5e5'): string
{
    // Validate color to prevent injection
    $color = lemur_sanitize_css_color($color, '#e5e5e5');

    // Ensure positive dimensions
    $width = max(1, abs($width));
    $height = max(1, abs($height));

    $svg = sprintf(
        '<svg xmlns="http://www.w3.org/2000/svg" width="%d" height="%d" viewBox="0 0 %d %d"><rect width="100%%" height="100%%" fill="%s"/></svg>',
        $width,
        $height,
        $width,
        $height,
        esc_attr($color)
    );

    return 'data:image/svg+xml;base64,' . base64_encode($svg);
}

/**
 * Display an image with placeholder fallback
 *
 * Shows the actual image if available, or a placeholder SVG if not.
 *
 * @param int|null $attachment_id Attachment ID (or null for placeholder)
 * @param string   $size          Image size name
 * @param array    $attr          HTML attributes
 */
function lemur_image_or_placeholder(?int $attachment_id, string $size = 'large', array $attr = []): void
{
    if ($attachment_id) {
        lemur_responsive_image($attachment_id, $size, $attr);
        return;
    }

    // Get dimensions for the requested size
    $dimensions = \Lemur\Images\Optimizer::getImageSize($size);

    // Fallback to WordPress built-in sizes or defaults
    if (!$dimensions) {
        $wp_sizes = wp_get_registered_image_subsizes();
        if (isset($wp_sizes[$size])) {
            $dimensions = [
                'width' => $wp_sizes[$size]['width'],
                'height' => $wp_sizes[$size]['height'],
            ];
        }
    }

    $width = $dimensions['width'] ?? 400;
    $height = $dimensions['height'] ?? 300;

    $placeholder = lemur_placeholder_svg($width, $height);
    $class = $attr['class'] ?? 'responsive-image placeholder';

    printf(
        '<img src="%s" alt="" class="%s" width="%d" height="%d" loading="lazy">',
        esc_url($placeholder),
        esc_attr($class),
        $width,
        $height
    );
}

// =============================================================================
// Event Helper Functions
// =============================================================================

/**
 * Get upcoming events
 *
 * @param int   $limit Number of events to retrieve
 * @param array $args  Additional WP_Query arguments
 * @return \WP_Post[]
 */
function lemur_get_upcoming_events(int $limit = 4, array $args = []): array
{
    // Try to get cached results (cache key includes limit and date for daily refresh)
    $cache_key = 'lemur_upcoming_events_' . $limit . '_' . current_time('Y-m-d');

    // Only use cache if no custom args provided
    if (empty($args)) {
        $cached = get_transient($cache_key);
        if ($cached !== false) {
            return $cached;
        }
    }

    $default_args = [
        'post_type'      => \Lemur\CustomPostTypes\Events::POST_TYPE,
        'posts_per_page' => $limit,
        'meta_key'       => \Lemur\CustomPostTypes\Events::FIELD_DATE_START,
        'orderby'        => 'meta_value',
        'order'          => 'ASC',
        'meta_query'     => [
            [
                'key'     => \Lemur\CustomPostTypes\Events::FIELD_DATE_START,
                'value'   => current_time('Y-m-d'),
                'compare' => '>=',
                'type'    => 'DATE',
            ],
        ],
    ];

    $query = new \WP_Query(array_merge($default_args, $args));
    $posts = $query->posts;

    // Cache for 1 hour if no custom args
    if (empty($args)) {
        set_transient($cache_key, $posts, HOUR_IN_SECONDS);
    }

    return $posts;
}

/**
 * Get past events
 *
 * @param int $limit Number of events to retrieve
 * @return \WP_Post[]
 */
function lemur_get_past_events(int $limit = 10): array
{
    $args = [
        'post_type'      => \Lemur\CustomPostTypes\Events::POST_TYPE,
        'posts_per_page' => $limit,
        'meta_key'       => \Lemur\CustomPostTypes\Events::FIELD_DATE_START,
        'orderby'        => 'meta_value',
        'order'          => 'DESC',
        'meta_query'     => [
            [
                'key'     => \Lemur\CustomPostTypes\Events::FIELD_DATE_START,
                'value'   => current_time('Y-m-d'),
                'compare' => '<',
                'type'    => 'DATE',
            ],
        ],
    ];

    $query = new \WP_Query($args);

    return $query->posts;
}

/**
 * Format event date range
 *
 * @param int $event_id Event post ID
 * @return string Formatted date string
 */
function lemur_format_event_date(int $event_id): string
{
    $start = carbon_get_post_meta($event_id, \Lemur\CustomPostTypes\Events::FIELD_DATE_START);
    $end = carbon_get_post_meta($event_id, \Lemur\CustomPostTypes\Events::FIELD_DATE_END);

    if (!$start) {
        return '';
    }

    $start_timestamp = strtotime($start);
    $formatted = date_i18n('j F Y', $start_timestamp);

    if ($end && $end !== $start) {
        $end_timestamp = strtotime($end);

        // Same month and year?
        if (date('F Y', $start_timestamp) === date('F Y', $end_timestamp)) {
            $formatted = date_i18n('j', $start_timestamp) . ' - ' . date_i18n('j F Y', $end_timestamp);
        } else {
            $formatted .= ' - ' . date_i18n('j F Y', $end_timestamp);
        }
    }

    return $formatted;
}

/**
 * Format event time range
 *
 * @param int $event_id Event post ID
 * @return string Formatted time string
 */
function lemur_format_event_time(int $event_id): string
{
    $start = carbon_get_post_meta($event_id, \Lemur\CustomPostTypes\Events::FIELD_TIME_START);
    $end = carbon_get_post_meta($event_id, \Lemur\CustomPostTypes\Events::FIELD_TIME_END);

    if (!$start) {
        return '';
    }

    $formatted = esc_html($start);

    if ($end) {
        $formatted .= ' - ' . esc_html($end);
    }

    return $formatted;
}

/**
 * Check if event registrations are open
 *
 * @param int $event_id Event post ID
 * @return bool
 */
function lemur_event_registrations_open(int $event_id): bool
{
    $deadline = carbon_get_post_meta($event_id, \Lemur\CustomPostTypes\Events::FIELD_REGISTRATION_DEADLINE);
    $max = carbon_get_post_meta($event_id, \Lemur\CustomPostTypes\Events::FIELD_MAX_PARTICIPANTS);
    $current = carbon_get_post_meta($event_id, \Lemur\CustomPostTypes\Events::FIELD_CURRENT_PARTICIPANTS);
    $link = carbon_get_post_meta($event_id, \Lemur\CustomPostTypes\Events::FIELD_REGISTRATION_LINK);

    // No registration link
    if (empty($link)) {
        return false;
    }

    // Deadline passed
    if ($deadline && strtotime($deadline) < current_time('timestamp')) {
        return false;
    }

    // Event full
    if ($max && $current && (int) $current >= (int) $max) {
        return false;
    }

    return true;
}

/**
 * Get remaining spots for an event
 *
 * @param int $event_id Event post ID
 * @return int|null Null if no limit set
 */
function lemur_event_remaining_spots(int $event_id): ?int
{
    $max = carbon_get_post_meta($event_id, \Lemur\CustomPostTypes\Events::FIELD_MAX_PARTICIPANTS);
    $current = carbon_get_post_meta($event_id, \Lemur\CustomPostTypes\Events::FIELD_CURRENT_PARTICIPANTS);

    if (!$max) {
        return null;
    }

    return max(0, (int) $max - (int) $current);
}

/**
 * Check if an event is in the past
 *
 * @param int $event_id Event post ID
 * @return bool
 */
function lemur_event_is_past(int $event_id): bool
{
    $date = carbon_get_post_meta($event_id, \Lemur\CustomPostTypes\Events::FIELD_DATE_START);

    if (!$date) {
        return false;
    }

    return strtotime($date) < current_time('timestamp');
}

/**
 * Get event difficulty label
 *
 * @param int $event_id Event post ID
 * @return string
 */
function lemur_get_event_difficulty_label(int $event_id): string
{
    $difficulty = carbon_get_post_meta($event_id, \Lemur\CustomPostTypes\Events::FIELD_DIFFICULTY);

    $labels = [
        ''             => __('Tous niveaux', 'lemur'),
        'beginner'     => __('Débutant', 'lemur'),
        'intermediate' => __('Intermédiaire', 'lemur'),
        'advanced'     => __('Confirmé', 'lemur'),
        'expert'       => __('Expert', 'lemur'),
    ];

    return $labels[$difficulty] ?? $labels[''];
}

/**
 * Get event registration link
 *
 * @param int $event_id Event post ID
 * @return string|null
 */
function lemur_get_event_registration_link(int $event_id): ?string
{
    $link = carbon_get_post_meta($event_id, \Lemur\CustomPostTypes\Events::FIELD_REGISTRATION_LINK);

    return $link ? esc_url($link) : null;
}

/**
 * Get event map link
 *
 * @param int $event_id Event post ID
 * @return string|null
 */
function lemur_get_event_map_link(int $event_id): ?string
{
    $link = carbon_get_post_meta($event_id, \Lemur\CustomPostTypes\Events::FIELD_MAP_LINK);

    return $link ? esc_url($link) : null;
}

/**
 * Get related events
 *
 * Returns upcoming events of the same type, then fills with other types.
 *
 * @param int $event_id Current event ID to exclude
 * @param int $limit    Number of events to return
 * @return \WP_Post[]
 */
function lemur_get_related_events(int $event_id, int $limit = 3): array
{
    $event_types = get_the_terms($event_id, \Lemur\CustomPostTypes\Events::TAXONOMY);
    $type_slugs = ($event_types && !is_wp_error($event_types))
        ? wp_list_pluck($event_types, 'slug')
        : [];

    // First, try to get events of the same type
    $args = [
        'post_type'      => \Lemur\CustomPostTypes\Events::POST_TYPE,
        'posts_per_page' => $limit,
        'post__not_in'   => [$event_id],
        'meta_key'       => \Lemur\CustomPostTypes\Events::FIELD_DATE_START,
        'orderby'        => 'meta_value',
        'order'          => 'ASC',
        'meta_query'     => [
            [
                'key'     => \Lemur\CustomPostTypes\Events::FIELD_DATE_START,
                'value'   => current_time('Y-m-d'),
                'compare' => '>=',
                'type'    => 'DATE',
            ],
        ],
    ];

    if (!empty($type_slugs)) {
        $args['tax_query'] = [
            [
                'taxonomy' => \Lemur\CustomPostTypes\Events::TAXONOMY,
                'field'    => 'slug',
                'terms'    => $type_slugs,
            ],
        ];
    }

    $query = new \WP_Query($args);
    $related = $query->posts;

    // If not enough, get any upcoming events
    if (count($related) < $limit && !empty($type_slugs)) {
        $remaining = $limit - count($related);
        $exclude_ids = array_merge([$event_id], wp_list_pluck($related, 'ID'));

        $more_args = [
            'post_type'      => \Lemur\CustomPostTypes\Events::POST_TYPE,
            'posts_per_page' => $remaining,
            'post__not_in'   => $exclude_ids,
            'meta_key'       => \Lemur\CustomPostTypes\Events::FIELD_DATE_START,
            'orderby'        => 'meta_value',
            'order'          => 'ASC',
            'meta_query'     => [
                [
                    'key'     => \Lemur\CustomPostTypes\Events::FIELD_DATE_START,
                    'value'   => current_time('Y-m-d'),
                    'compare' => '>=',
                    'type'    => 'DATE',
                ],
            ],
        ];

        $more_query = new \WP_Query($more_args);
        $related = array_merge($related, $more_query->posts);
    }

    return $related;
}

// =============================================================================
// Member Helper Functions
// =============================================================================

/**
 * Get all members sorted by order
 *
 * @param array $args Additional WP_Query arguments
 * @return \WP_Post[]
 */
function lemur_get_members(array $args = []): array
{
    $default_args = [
        'post_type'      => \Lemur\CustomPostTypes\Members::POST_TYPE,
        'posts_per_page' => -1,
        'meta_key'       => \Lemur\CustomPostTypes\Members::FIELD_ORDER,
        'orderby'        => 'meta_value_num',
        'order'          => 'ASC',
    ];

    $query = new \WP_Query(array_merge($default_args, $args));

    return $query->posts;
}

/**
 * Get members by role
 *
 * @param string $role Role slug (e.g., 'bureau', 'encadrant')
 * @return \WP_Post[]
 */
function lemur_get_members_by_role(string $role): array
{
    return lemur_get_members([
        'tax_query' => [
            [
                'taxonomy' => \Lemur\CustomPostTypes\Members::TAXONOMY,
                'field'    => 'slug',
                'terms'    => $role,
            ],
        ],
    ]);
}

/**
 * Get board members
 *
 * @return \WP_Post[]
 */
function lemur_get_bureau_members(): array
{
    return lemur_get_members_by_role('bureau');
}

/**
 * Get instructors
 *
 * @return \WP_Post[]
 */
function lemur_get_encadrants(): array
{
    return lemur_get_members_by_role('encadrant');
}

/**
 * Get member info
 *
 * @param int $member_id Member post ID
 * @return array<string, mixed>
 */
function lemur_get_member_info(int $member_id): array
{
    return \Lemur\CustomPostTypes\Members::getMemberMeta($member_id);
}

/**
 * Display member roles as badges
 *
 * @param int   $member_id Member post ID
 * @param array $args      Display options
 */
function lemur_display_member_roles(int $member_id, array $args = []): void
{
    $roles = get_the_terms($member_id, \Lemur\CustomPostTypes\Members::TAXONOMY);

    if (!$roles || is_wp_error($roles)) {
        return;
    }

    $defaults = [
        'class'      => 'member-roles',
        'item_class' => 'member-role',
    ];

    $args = array_merge($defaults, $args);

    echo '<div class="' . esc_attr($args['class']) . '">';

    foreach ($roles as $role) {
        printf(
            '<span class="%s">%s</span>',
            esc_attr($args['item_class']),
            esc_html($role->name)
        );
    }

    echo '</div>';
}

// =============================================================================
// FAQ Helper Functions
// =============================================================================

/**
 * Get all FAQ questions sorted by order
 *
 * @param array $args Additional WP_Query arguments
 * @return \WP_Post[]
 */
function lemur_get_faq(array $args = []): array
{
    // Carbon Fields stores meta with underscore prefix
    $meta_key = '_' . \Lemur\CustomPostTypes\FAQ::FIELD_ORDER;

    $default_args = [
        'post_type'      => \Lemur\CustomPostTypes\FAQ::POST_TYPE,
        'posts_per_page' => -1,
        'orderby'        => 'date',
        'order'          => 'ASC',
    ];

    $query = new \WP_Query(array_merge($default_args, $args));

    return $query->posts;
}

/**
 * Get FAQ questions by category
 *
 * @param string|int $category Category slug or ID
 * @return \WP_Post[]
 */
function lemur_get_faq_by_category($category): array
{
    $field = is_numeric($category) ? 'term_id' : 'slug';

    return lemur_get_faq([
        'tax_query' => [
            [
                'taxonomy' => \Lemur\CustomPostTypes\FAQ::TAXONOMY,
                'field'    => $field,
                'terms'    => $category,
            ],
        ],
    ]);
}

/**
 * Get FAQ questions grouped by category
 *
 * @return array<string, array{term: \WP_Term, questions: \WP_Post[]}>
 */
function lemur_get_faq_grouped_by_category(): array
{
    $categories = get_terms([
        'taxonomy'   => \Lemur\CustomPostTypes\FAQ::TAXONOMY,
        'hide_empty' => true,
    ]);

    if (is_wp_error($categories)) {
        return [];
    }

    $grouped = [];

    foreach ($categories as $category) {
        $questions = lemur_get_faq_by_category($category->term_id);

        if (!empty($questions)) {
            $grouped[$category->slug] = [
                'term'      => $category,
                'questions' => $questions,
            ];
        }
    }

    return $grouped;
}

/**
 * Get FAQ answer
 *
 * @param int $faq_id FAQ post ID
 * @return string
 */
function lemur_get_faq_answer(int $faq_id): string
{
    return \Lemur\CustomPostTypes\FAQ::getAnswer($faq_id);
}

/**
 * Generate Schema.org FAQPage data
 *
 * @param \WP_Post[] $questions Array of FAQ posts
 * @return array<string, mixed> Schema.org data
 */
function lemur_generate_faq_schema(array $questions): array
{
    $schema = [
        '@context'   => 'https://schema.org',
        '@type'      => 'FAQPage',
        'mainEntity' => [],
    ];

    foreach ($questions as $question) {
        $answer = lemur_get_faq_answer($question->ID);

        if (empty($answer)) {
            continue;
        }

        $schema['mainEntity'][] = [
            '@type'          => 'Question',
            'name'           => get_the_title($question),
            'acceptedAnswer' => [
                '@type' => 'Answer',
                'text'  => wp_strip_all_tags($answer),
            ],
        ];
    }

    return $schema;
}

/**
 * Output FAQ Schema.org JSON-LD
 *
 * @param \WP_Post[] $questions Array of FAQ posts
 */
function lemur_output_faq_schema(array $questions): void
{
    $schema = lemur_generate_faq_schema($questions);

    if (empty($schema['mainEntity'])) {
        return;
    }

    printf(
        '<script type="application/ld+json">%s</script>',
        wp_json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
    );
}

// =============================================================================
// Navigation Helpers
// =============================================================================

/**
 * Get page permalink by path with caching
 *
 * Avoids multiple DB queries for the same page slug.
 *
 * @param string $path Page path/slug
 * @return string|null Permalink or null if page not found
 */
function lemur_get_page_permalink(string $path): ?string
{
    $page = lemur_get_page_by_path($path);

    return $page ? get_permalink($page) : null;
}

// =============================================================================
// UI Icons Helpers
// =============================================================================

/**
 * Get inline SVG icon for UI elements
 *
 * Returns optimized SVG icons for common UI elements.
 * All icons are 24x24 viewBox with currentColor stroke.
 *
 * Available icons:
 * - arrow-right, calendar, location, clock
 * - user, users, check, x
 * - chevron-right, chevron-down, external-link
 * - mail, phone, info, alert-triangle
 *
 * @param string $name Icon name
 * @param array{
 *     width?: int,
 *     height?: int,
 *     class?: string,
 *     aria-hidden?: string
 * } $attr Optional attributes
 * @return string SVG markup or empty string if icon not found
 */
function lemur_ui_icon(string $name, array $attr = []): string
{
    $defaults = [
        'width' => 20,
        'height' => 20,
        'class' => '',
        'aria-hidden' => 'true',
    ];

    $attr = array_merge($defaults, $attr);

    $icons = [
        'arrow-right' => '<path d="M5 12h14M12 5l7 7-7 7"/>',
        'calendar' => '<rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/>',
        'location' => '<path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/>',
        'clock' => '<circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>',
        'user' => '<path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/>',
        'users' => '<path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/>',
        'check' => '<polyline points="20 6 9 17 4 12"/>',
        'x' => '<line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>',
        'chevron-right' => '<polyline points="9 18 15 12 9 6"/>',
        'chevron-left' => '<polyline points="15 18 9 12 15 6"/>',
        'chevron-down' => '<polyline points="6 9 12 15 18 9"/>',
        'external-link' => '<path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/>',
        'mail' => '<path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/>',
        'phone' => '<path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/>',
        'info' => '<circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/>',
        'alert-triangle' => '<path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/>',
    ];

    if (!isset($icons[$name])) {
        return '';
    }

    $class_attr = $attr['class'] ? ' class="' . esc_attr($attr['class']) . '"' : '';

    return sprintf(
        '<svg width="%d" height="%d" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"%s aria-hidden="%s">%s</svg>',
        (int) $attr['width'],
        (int) $attr['height'],
        $class_attr,
        esc_attr($attr['aria-hidden']),
        $icons[$name]
    );
}

/**
 * Echo inline SVG icon
 *
 * @param string $name Icon name
 * @param array  $attr Optional attributes
 */
function lemur_the_ui_icon(string $name, array $attr = []): void
{
    // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- SVG is hardcoded
    echo lemur_ui_icon($name, $attr);
}

// =============================================================================
// Adhesion Helper Functions
// =============================================================================

/**
 * Parse paliers from CSV string to array of integers
 *
 * @param string $csv Comma-separated values (e.g., "50,80,110,140")
 * @return array<int> Array of integer values
 */
function lemur_parse_paliers(string $csv): array
{
    if (empty($csv)) {
        return [];
    }

    $values = array_map('trim', explode(',', $csv));
    $paliers = [];

    foreach ($values as $value) {
        if (is_numeric($value)) {
            $paliers[] = (int) $value;
        }
    }

    // Sort ascending
    sort($paliers, SORT_NUMERIC);

    return $paliers;
}

/**
 * Get adhesion formule paliers
 *
 * @param string $formule Formule type: 'adulte', 'famille', 'double'
 * @return array<int>
 */
function lemur_get_adhesion_paliers(string $formule): array
{
    $key = match ($formule) {
        'adulte' => \Lemur\Fields\ThemeOptions::FIELD_ADHESION_ADULTE_PALIERS,
        'famille' => \Lemur\Fields\ThemeOptions::FIELD_ADHESION_FAMILLE_PALIERS,
        'double' => \Lemur\Fields\ThemeOptions::FIELD_ADHESION_DOUBLE_PALIERS,
        default => '',
    };

    if (empty($key)) {
        return [];
    }

    $defaults = [
        'adulte' => '50,80,110,140,170,200',
        'famille' => '80,110,140,170,200,230',
        'double' => '10,40,70,100,130,160',
    ];

    $csv = lemur_get_option($key) ?: ($defaults[$formule] ?? '');

    return lemur_parse_paliers($csv);
}

/**
 * Get FSGT license cost
 *
 * @return int
 */
function lemur_get_licence_fsgt_cost(): int
{
    return (int) (lemur_get_option(\Lemur\Fields\ThemeOptions::FIELD_ADHESION_LICENCE_FSGT) ?: 40);
}

// =============================================================================
// Gallery Helper Functions
// =============================================================================

/**
 * Get gallery albums from theme options
 *
 * @return array<int, array{name: string, slug: string, description: string, images: array}>
 */
function lemur_get_gallery_albums(): array
{
    $albums = lemur_get_option(\Lemur\Fields\ThemeOptions::FIELD_GALLERY_ALBUMS, []);

    if (empty($albums) || !is_array($albums)) {
        return [];
    }

    $result = [];

    foreach ($albums as $album) {
        $name = sanitize_text_field($album['name'] ?? '');

        if (empty($name)) {
            continue;
        }

        $slug = sanitize_title($name);
        $images = [];

        if (!empty($album['images']) && is_array($album['images'])) {
            foreach ($album['images'] as $image_id) {
                $images[] = [
                    'id'         => (int) $image_id,
                    'album_slug' => $slug,
                ];
            }
        }

        $result[] = [
            'name'        => $name,
            'slug'        => $slug,
            'description' => sanitize_textarea_field($album['description'] ?? ''),
            'images'      => $images,
        ];
    }

    return $result;
}

/**
 * Get all gallery images from all albums
 *
 * @return array<int, array{id: int, album_slug: string}>
 */
function lemur_get_all_gallery_images(): array
{
    $albums = lemur_get_gallery_albums();
    $all_images = [];

    foreach ($albums as $album) {
        foreach ($album['images'] as $image) {
            $all_images[] = $image;
        }
    }

    return $all_images;
}

/**
 * Prepare gallery data for JSON (lightbox)
 *
 * @param array $images Array of image data
 * @return array<int, array{id: int, full: string, alt: string, caption: string, album: string}>
 */
function lemur_prepare_gallery_data(array $images): array
{
    $data = [];

    foreach ($images as $image) {
        $image_id = (int) ($image['id'] ?? 0);

        if (!$image_id) {
            continue;
        }

        $full = wp_get_attachment_image_src($image_id, 'full');
        $alt = get_post_meta($image_id, '_wp_attachment_image_alt', true);
        $caption = wp_get_attachment_caption($image_id);

        $data[] = [
            'id'      => $image_id,
            'full'    => $full[0] ?? '',
            'alt'     => $alt ?: '',
            'caption' => $caption ?: '',
            'album'   => $image['album_slug'] ?? 'all',
        ];
    }

    return $data;
}

// =============================================================================
// Email Obfuscation Helper Functions
// =============================================================================

/**
 * Obfuscate an email address with click-to-reveal protection
 *
 * Usage in templates:
 * ```php
 * // Basic usage
 * <?= lemur_email('contact@example.org') ?>
 *
 * // With custom label
 * <?= lemur_email('contact@example.org', 'Nous contacter') ?>
 *
 * // Inline in content
 * <?= lemur_email('contact@example.org', null, ['inline' => true]) ?>
 *
 * // Without copy button
 * <?= lemur_email('contact@example.org', null, ['copy_button' => false]) ?>
 * ```
 *
 * @param string      $email   Email address to obfuscate
 * @param string|null $label   Optional custom label (shows obfuscated email if null)
 * @param array       $options Options: 'class', 'copy_button', 'inline'
 * @return string HTML markup
 */
function lemur_email(string $email, ?string $label = null, array $options = []): string
{
    return \Lemur\Security\EmailObfuscator::obfuscate($email, $label, $options);
}

/**
 * Get obfuscated contact email from theme options
 *
 * @param array $options Obfuscation options
 * @return string HTML markup or empty string
 */
function lemur_get_contact_email_obfuscated(array $options = []): string
{
    $email = lemur_get_contact('email');

    if (empty($email)) {
        return '';
    }

    return lemur_email($email, null, $options);
}

/**
 * Echo obfuscated email
 *
 * @param string      $email   Email address
 * @param string|null $label   Optional label
 * @param array       $options Options
 */
function lemur_the_email(string $email, ?string $label = null, array $options = []): void
{
    // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Output is escaped in EmailObfuscator
    echo lemur_email($email, $label, $options);
}

/**
 * Echo obfuscated contact email from theme options
 *
 * @param array $options Obfuscation options
 */
function lemur_the_contact_email(array $options = []): void
{
    // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Output is escaped in EmailObfuscator
    echo lemur_get_contact_email_obfuscated($options);
}

// =============================================================================
// Contact Helper Functions
// =============================================================================

/**
 * Render Schema.org LocalBusiness/SportsClub JSON-LD for contact page
 *
 * Outputs structured data for SEO including address, contact info, and opening hours.
 */
function lemur_render_contact_schema(): void
{
    $name = get_bloginfo('name');
    $address = lemur_get_option(\Lemur\Fields\ThemeOptions::FIELD_ADDRESS);
    $email = lemur_get_option(\Lemur\Fields\ThemeOptions::FIELD_EMAIL);
    $phone = lemur_get_option(\Lemur\Fields\ThemeOptions::FIELD_PHONE);
    $schedule = lemur_get_option(\Lemur\Fields\ThemeOptions::FIELD_SCHEDULE);
    $logo_id = lemur_get_option(\Lemur\Fields\ThemeOptions::FIELD_LOGO);

    $schema = [
        '@context' => 'https://schema.org',
        '@type'    => 'SportsClub',
        'name'     => $name,
        'description' => get_bloginfo('description'),
        'url'      => home_url(),
        'sport'    => __('Climbing', 'lemur'),
    ];

    // Parse address (format: street\npostal_code city)
    if ($address) {
        $lines = array_map('trim', explode("\n", $address));
        $street = $lines[0] ?? '';
        $city_line = $lines[1] ?? '';

        // Extract postal code and city from "75020 Paris"
        preg_match('/^(\d{5})\s*(.*)$/', $city_line, $matches);
        $postal_code = $matches[1] ?? '';
        $city = $matches[2] ?? $city_line;

        $schema['address'] = [
            '@type'           => 'PostalAddress',
            'streetAddress'   => $street,
            'postalCode'      => $postal_code,
            'addressLocality' => $city,
            'addressCountry'  => 'FR',
        ];
    }

    if ($email) {
        $schema['email'] = $email;
    }

    if ($phone) {
        $schema['telephone'] = $phone;
    }

    // Opening hours from schedule
    if (!empty($schedule) && is_array($schedule)) {
        $days_map = [
            'lundi'    => 'Mo',
            'mardi'    => 'Tu',
            'mercredi' => 'We',
            'jeudi'    => 'Th',
            'vendredi' => 'Fr',
            'samedi'   => 'Sa',
            'dimanche' => 'Su',
        ];

        $opening_hours = [];

        foreach ($schedule as $slot) {
            $day_fr = strtolower($slot['day'] ?? '');
            $hours = $slot['hours'] ?? '';
            $day_en = $days_map[$day_fr] ?? '';

            if (!$day_en || empty($hours)) {
                continue;
            }

            // Skip "Fermé" or empty
            if (strtolower($hours) === 'fermé' || strtolower($hours) === 'ferme') {
                continue;
            }

            // Parse hours format: "19h-21h" or "19h30-21h00" to "19:00-21:00"
            // Match pattern: 19h, 19h30, 19:00, 19:30
            $time = preg_replace_callback(
                '/(\d{1,2})h?(\d{2})?/',
                function ($m) {
                    $hour = str_pad($m[1], 2, '0', STR_PAD_LEFT);
                    $min = $m[2] ?? '00';
                    return $hour . ':' . $min;
                },
                $hours
            );
            // Clean up any remaining non-time characters except : and -
            $time = preg_replace('/[^0-9:-]/', '', $time);

            // Validate format (should be HH:MM-HH:MM or HH:MM)
            if (!preg_match('/^\d{2}:\d{2}(-\d{2}:\d{2})?$/', $time)) {
                continue;
            }

            $opening_hours[] = $day_en . ' ' . $time;
        }

        if (!empty($opening_hours)) {
            $schema['openingHours'] = $opening_hours;
        }
    }

    // Logo
    if ($logo_id) {
        $logo_url = wp_get_attachment_url($logo_id);
        if ($logo_url) {
            $schema['logo'] = $logo_url;
        }
    }

    printf(
        '<script type="application/ld+json">%s</script>',
        wp_json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
    );
}

/**
 * Render an alert component
 *
 * @param string $type    Alert type: 'success', 'warning', 'error', 'info'
 * @param string $message Alert message (can contain HTML)
 * @param array  $args    Optional arguments
 * @return string HTML markup
 */
function lemur_alert(string $type, string $message, array $args = []): string
{
    $defaults = [
        'title'       => '',
        'dismissible' => false,
        'size'        => '',
        'inline'      => false,
        'class'       => '',
    ];

    $args = wp_parse_args($args, $defaults);

    // Validate type
    $validTypes = ['success', 'warning', 'error', 'info'];
    if (!in_array($type, $validTypes, true)) {
        $type = 'info';
    }

    // Build classes
    $classes = ['alert', "alert--{$type}"];

    if ($args['size'] && in_array($args['size'], ['sm', 'lg'], true)) {
        $classes[] = "alert--{$args['size']}";
    }

    if ($args['inline']) {
        $classes[] = 'alert--inline';
    }

    if ($args['class']) {
        $classes[] = sanitize_html_class($args['class']);
    }

    // Icon based on type
    $icons = [
        'success' => '<svg class="alert__icon" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>',
        'warning' => '<svg class="alert__icon" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" /></svg>',
        'error'   => '<svg class="alert__icon" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>',
        'info'    => '<svg class="alert__icon" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>',
    ];

    // ARIA role based on type
    $role = in_array($type, ['error', 'warning'], true) ? 'alert' : 'status';

    ob_start();
    ?>
    <div class="<?php echo esc_attr(implode(' ', $classes)); ?>" role="<?php echo esc_attr($role); ?>">
        <?php echo $icons[$type]; ?>
        <div class="alert__content">
            <?php if ($args['title']) : ?>
                <p class="alert__title"><?php echo esc_html($args['title']); ?></p>
            <?php endif; ?>
            <p class="alert__message"><?php echo wp_kses_post($message); ?></p>
        </div>
        <?php if ($args['dismissible']) : ?>
            <button type="button" class="alert__dismiss" aria-label="<?php esc_attr_e('Fermer', 'lemur'); ?>">
                <svg width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        <?php endif; ?>
    </div>
    <?php
    return ob_get_clean();
}

// =============================================================================
// Debug Helper Functions
// =============================================================================

/**
 * Console log helper - output PHP variables to browser console
 *
 * Only works when WP_DEBUG is enabled.
 *
 * Usage:
 * ```php
 * lc($variable);                    // Simple log
 * lc('Label', $data);               // Log with label
 * lc($var1, $var2, $var3);         // Multiple arguments
 * lc()->warn('Warning!');           // Warning level
 * lc()->error('Error!', $data);     // Error level
 * lc()->info('Info');               // Info level
 * lc()->table($array);              // Console table
 * lc()->group('Title', $data);      // Grouped output
 * ```
 *
 * @param mixed ...$args Arguments to log
 * @return \Lemur\Core\ConsoleLog Instance for chaining
 */
function lc(mixed ...$args): \Lemur\Core\ConsoleLog
{
    $instance = \Lemur\Core\ConsoleLog::getInstance();

    if (empty($args)) {
        return $instance;
    }

    return $instance->log(...$args);
}
