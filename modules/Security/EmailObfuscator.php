<?php
/**
 * Email Obfuscator
 *
 * Protects email addresses from spam bots using a click-to-reveal pattern
 * with scramble animation.
 *
 * @package Lemur\Security
 */

declare(strict_types=1);

namespace Lemur\Security;

/**
 * Email obfuscation with click-to-reveal functionality
 */
class EmailObfuscator
{
    /**
     * Reveal delay in milliseconds
     */
    public const REVEAL_DELAY = 1500;

    /**
     * CSS class prefix
     */
    public const CLASS_PREFIX = 'lemur-email';

    /**
     * Regex pattern to match email addresses
     */
    private const EMAIL_PATTERN = '/[a-zA-Z0-9._%+\-]+@[a-zA-Z0-9.\-]+\.[a-zA-Z]{2,}/';

    /**
     * Initialize the module
     */
    public static function init(): void
    {
        // Filter content to auto-obfuscate emails
        add_filter('the_content', [self::class, 'filterContent'], 20, 1);
        add_filter('widget_text', [self::class, 'filterContent'], 20, 1);
        add_filter('widget_text_content', [self::class, 'filterContent'], 20, 1);

        // Enqueue assets
        add_action('wp_enqueue_scripts', [self::class, 'enqueueAssets'], 10);

        // Add inline config
        add_action('wp_footer', [self::class, 'outputConfig'], 5);
    }

    /**
     * Obfuscate an email address
     *
     * Returns HTML markup for a click-to-reveal email button.
     *
     * @param string      $email   Email address to obfuscate
     * @param string|null $label   Optional custom label (default: shows obfuscated email)
     * @param array       $options Options: 'class', 'copy_button', 'inline', 'compact'
     * @return string HTML markup
     */
    public static function obfuscate(string $email, ?string $label = null, array $options = []): string
    {
        $email = sanitize_email($email);

        if (empty($email) || !is_email($email)) {
            return '';
        }

        $defaults = [
            'class'        => '',
            'copy_button'  => true,
            'inline'       => false,
            'compact'      => false,
            'show_icon'    => null, // null = auto (hidden if label provided)
            'button_style' => null, // 'primary', 'secondary', or null for default
        ];

        $options = array_merge($defaults, $options);

        // Split email into user and domain
        $parts = explode('@', $email);
        $user = $parts[0];
        $domain = $parts[1] ?? '';

        if (empty($domain)) {
            return '';
        }

        // Encode parts in base64
        $encoded_user = base64_encode($user);
        $encoded_domain = base64_encode($domain);

        // Generate obfuscated display text
        $obfuscated_text = self::generateObfuscatedText($user, $domain);

        // Build CSS classes
        $classes = [self::CLASS_PREFIX];
        if ($options['inline']) {
            $classes[] = self::CLASS_PREFIX . '--inline';
        }
        if ($options['compact']) {
            $classes[] = self::CLASS_PREFIX . '--compact';
        }
        if ($options['button_style']) {
            $classes[] = self::CLASS_PREFIX . '--btn';
            $classes[] = self::CLASS_PREFIX . '--btn-' . sanitize_html_class($options['button_style']);
        }
        if ($options['class']) {
            // Support multiple classes (e.g., "btn btn--primary btn--full")
            $extra_classes = array_filter(array_map('sanitize_html_class', explode(' ', $options['class'])));
            $classes = array_merge($classes, $extra_classes);
        }

        // Determine if icon should be shown
        // Auto: show icon unless there's a label (button-style)
        $show_icon = $options['show_icon'];
        if ($show_icon === null) {
            $show_icon = ($label === null);
        }

        // Inline styles for button variants (backup for CSS specificity issues)
        $inline_style = '';
        if ($options['button_style'] === 'primary') {
            $inline_style = 'background-color:#2e7d32;border-color:#2e7d32;color:#fff;';
        } elseif ($options['button_style'] === 'secondary') {
            $inline_style = 'background-color:#f5f5f5;border-color:#d4d4d4;color:#262626;';
        }

        // Build HTML
        $html = sprintf(
            '<button type="button" class="%s" data-u="%s" data-d="%s" data-copy="%s" aria-label="%s" title="%s"%s>',
            esc_attr(implode(' ', $classes)),
            esc_attr($encoded_user),
            esc_attr($encoded_domain),
            $options['copy_button'] ? 'true' : 'false',
            esc_attr__('Cliquez pour révéler l\'adresse email', 'lemur'),
            $options['compact'] ? esc_attr__('Cliquez pour révéler l\'email', 'lemur') : '',
            $inline_style ? ' style="' . esc_attr($inline_style) . '"' : ''
        );

        // Icon (optional)
        if ($show_icon) {
            $html .= '<span class="' . self::CLASS_PREFIX . '__icon" aria-hidden="true">';
            $html .= self::getMailIcon();
            $html .= '</span>';
        }

        // Text container (hidden in compact mode)
        $html .= '<span class="' . self::CLASS_PREFIX . '__text">';

        if ($label !== null) {
            $html .= '<span class="' . self::CLASS_PREFIX . '__label">' . esc_html($label) . '</span>';
        } else {
            $html .= '<span class="' . self::CLASS_PREFIX . '__obfuscated">' . esc_html($obfuscated_text) . '</span>';
        }

        $html .= '</span>';

        // Hint (hidden in compact mode)
        $html .= '<span class="' . self::CLASS_PREFIX . '__hint">';
        $html .= $options['compact']
            ? esc_html__('Révéler', 'lemur')
            : esc_html__('Cliquez pour activer', 'lemur');
        $html .= '</span>';

        $html .= '</button>';

        return $html;
    }

    /**
     * Get obfuscated email from Carbon Fields option
     *
     * @param string $option_key Carbon Fields option key
     * @param array  $options    Obfuscation options
     * @return string HTML markup or empty string
     */
    public static function getObfuscatedOption(string $option_key, array $options = []): string
    {
        if (!function_exists('carbon_get_theme_option')) {
            return '';
        }

        $email = carbon_get_theme_option($option_key);

        if (empty($email)) {
            return '';
        }

        return self::obfuscate($email, null, $options);
    }

    /**
     * Filter content to auto-obfuscate email addresses
     *
     * @param string $content Content to filter
     * @return string Filtered content
     */
    public static function filterContent(string $content): string
    {
        if (empty($content)) {
            return $content;
        }

        // Don't process in admin
        if (is_admin()) {
            return $content;
        }

        // Find and replace email addresses
        return (string) preg_replace_callback(
            self::EMAIL_PATTERN,
            function ($matches) {
                $email = $matches[0];

                // Check if already inside our component (avoid double processing)
                // This is a simple check - the JS will handle the actual state
                return self::obfuscate($email, null, ['inline' => true]);
            },
            $content
        );
    }

    /**
     * Enqueue frontend assets
     */
    public static function enqueueAssets(): void
    {
        // Assets are bundled with main.js/css via Vite
        // This method is here for potential standalone usage
    }

    /**
     * Output JavaScript configuration
     */
    public static function outputConfig(): void
    {
        $config = [
            'delay'       => self::REVEAL_DELAY,
            'classPrefix' => self::CLASS_PREFIX,
            'i18n'        => [
                'decoding'   => __('Décodage...', 'lemur'),
                'copy'       => __('Copier', 'lemur'),
                'copied'     => __('Copié !', 'lemur'),
                'clickHint'  => __('Cliquez pour envoyer', 'lemur'),
                'copyFailed' => __('Erreur de copie', 'lemur'),
            ],
        ];

        printf(
            '<script>window.lemurEmailConfig = %s;</script>',
            wp_json_encode($config, JSON_UNESCAPED_UNICODE)
        );
    }

    /**
     * Generate obfuscated text display
     *
     * Converts "contact@domain.org" to "contact [at] domain [dot] org"
     *
     * @param string $user   User part of email
     * @param string $domain Domain part of email
     * @return string Obfuscated text
     */
    private static function generateObfuscatedText(string $user, string $domain): string
    {
        // Replace @ with [at]
        $text = $user . ' [at] ';

        // Replace dots in domain with [dot]
        $domain_parts = explode('.', $domain);
        $text .= implode(' [dot] ', $domain_parts);

        return $text;
    }

    /**
     * Get mail icon SVG
     *
     * @return string SVG markup
     */
    private static function getMailIcon(): string
    {
        return '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>';
    }

    /**
     * Get copy icon SVG
     *
     * @return string SVG markup
     */
    public static function getCopyIcon(): string
    {
        return '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="9" y="9" width="13" height="13" rx="2" ry="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg>';
    }

    /**
     * Get check icon SVG
     *
     * @return string SVG markup
     */
    public static function getCheckIcon(): string
    {
        return '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>';
    }
}
