<?php

/**
 * Sanitization and Escaping Utilities
 *
 * @package Lemur\Security
 */

declare(strict_types=1);

namespace Lemur\Security;

/**
 * Centralized sanitization for user input
 */
class Sanitization
{
    /**
     * Sanitize plain text
     */
    public static function text(?string $value): string
    {
        if ($value === null || $value === '') {
            return '';
        }
        return sanitize_text_field($value);
    }

    /**
     * Sanitize textarea (preserves line breaks)
     */
    public static function textarea(?string $value): string
    {
        if ($value === null || $value === '') {
            return '';
        }
        return sanitize_textarea_field($value);
    }

    /**
     * Sanitize email address
     */
    public static function email(?string $value): string
    {
        if ($value === null || $value === '') {
            return '';
        }
        return sanitize_email($value);
    }

    /**
     * Sanitize URL
     */
    public static function url(?string $value): string
    {
        if ($value === null || $value === '') {
            return '';
        }
        return esc_url_raw($value);
    }

    /**
     * Sanitize integer
     *
     * @param mixed $value
     */
    public static function int($value): int
    {
        return intval($value);
    }

    /**
     * Sanitize positive integer (absolute value)
     *
     * @param mixed $value
     */
    public static function absint($value): int
    {
        return absint($value);
    }

    /**
     * Sanitize float
     *
     * @param mixed $value
     */
    public static function float($value): float
    {
        return floatval($value);
    }

    /**
     * Sanitize boolean
     *
     * @param mixed $value
     */
    public static function bool($value): bool
    {
        return filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }

    /**
     * Sanitize filename
     */
    public static function filename(?string $value): string
    {
        if ($value === null || $value === '') {
            return '';
        }
        return sanitize_file_name($value);
    }

    /**
     * Sanitize slug/key
     */
    public static function slug(?string $value): string
    {
        if ($value === null || $value === '') {
            return '';
        }
        return sanitize_title($value);
    }

    /**
     * Sanitize key (lowercase alphanumeric with dashes and underscores)
     */
    public static function key(?string $value): string
    {
        if ($value === null || $value === '') {
            return '';
        }
        return sanitize_key($value);
    }

    /**
     * Sanitize HTML with allowed post tags
     */
    public static function html(?string $value): string
    {
        if ($value === null || $value === '') {
            return '';
        }
        return wp_kses_post($value);
    }

    /**
     * Sanitize HTML with custom allowed tags
     */
    public static function htmlCustom(?string $value, array $allowed_tags): string
    {
        if ($value === null || $value === '') {
            return '';
        }
        return wp_kses($value, $allowed_tags);
    }

    /**
     * Sanitize basic HTML (links, emphasis only)
     */
    public static function htmlBasic(?string $value): string
    {
        if ($value === null || $value === '') {
            return '';
        }
        return wp_kses($value, self::getAllowedHtmlBasic());
    }

    /**
     * Sanitize date
     */
    public static function date(?string $value, string $format = 'Y-m-d'): string
    {
        if ($value === null || $value === '') {
            return '';
        }

        $date = \DateTime::createFromFormat($format, $value);
        if ($date === false) {
            // Try other common formats
            $date = \DateTime::createFromFormat('Y-m-d', $value);
            if ($date === false) {
                $date = \DateTime::createFromFormat('d/m/Y', $value);
            }
        }

        if ($date === false) {
            return '';
        }

        return $date->format($format);
    }

    /**
     * Sanitize datetime
     */
    public static function datetime(?string $value): string
    {
        if ($value === null || $value === '') {
            return '';
        }

        $date = \DateTime::createFromFormat('Y-m-d H:i:s', $value);
        if ($date === false) {
            $date = \DateTime::createFromFormat('Y-m-d H:i', $value);
        }
        if ($date === false) {
            $date = \DateTime::createFromFormat('Y-m-d\TH:i', $value);
        }

        if ($date === false) {
            return '';
        }

        return $date->format('Y-m-d H:i:s');
    }

    /**
     * Sanitize phone number
     */
    public static function phone(?string $value): string
    {
        if ($value === null || $value === '') {
            return '';
        }
        // Keep only digits, +, spaces, dashes, and parentheses
        return (string) preg_replace('/[^0-9+\s\-()]/', '', $value);
    }

    /**
     * Sanitize CSS class name
     */
    public static function cssClass(?string $value): string
    {
        if ($value === null || $value === '') {
            return '';
        }
        return sanitize_html_class($value);
    }

    /**
     * Sanitize multiple CSS class names
     */
    public static function cssClasses(?string $value): string
    {
        if ($value === null || $value === '') {
            return '';
        }

        $classes = explode(' ', $value);
        $sanitized = array_map('sanitize_html_class', $classes);
        return implode(' ', array_filter($sanitized));
    }

    /**
     * Validate and sanitize value from allowed list
     *
     * @param mixed $value
     * @param array $allowed
     * @param mixed $default
     * @return mixed
     */
    public static function inArray($value, array $allowed, $default = null)
    {
        return in_array($value, $allowed, true) ? $value : $default;
    }

    /**
     * Sanitize array of values
     *
     * @param array $values
     * @param string $type Sanitization method to use
     */
    public static function array(array $values, string $type = 'text'): array
    {
        if (!method_exists(self::class, $type)) {
            $type = 'text';
        }

        return array_map(function ($value) use ($type) {
            return self::$type($value);
        }, $values);
    }

    /**
     * Sanitize array of integers
     */
    public static function intArray(array $values): array
    {
        return array_map('intval', $values);
    }

    /**
     * Sanitize associative array according to schema
     *
     * @param array $data Input data
     * @param array $schema Schema definition ['field' => 'type'] or ['field' => ['type' => 'text', 'default' => '']]
     */
    public static function schema(array $data, array $schema): array
    {
        $sanitized = [];

        foreach ($schema as $key => $type) {
            $value = $data[$key] ?? null;

            if (is_array($type)) {
                $method = $type['type'] ?? 'text';
                $default = $type['default'] ?? null;

                if (!method_exists(self::class, $method)) {
                    $method = 'text';
                }

                $sanitized[$key] = $value !== null ? self::$method($value) : $default;
            } else {
                if (!method_exists(self::class, $type)) {
                    $type = 'text';
                }

                $sanitized[$key] = $value !== null ? self::$type($value) : null;
            }
        }

        return $sanitized;
    }

    /**
     * Get allowed HTML tags for basic content
     */
    public static function getAllowedHtmlBasic(): array
    {
        return [
            'a' => [
                'href' => true,
                'title' => true,
                'target' => true,
                'rel' => true,
                'class' => true,
            ],
            'strong' => ['class' => true],
            'b' => [],
            'em' => ['class' => true],
            'i' => ['class' => true],
            'br' => [],
            'p' => ['class' => true],
            'span' => ['class' => true],
            'ul' => ['class' => true],
            'ol' => ['class' => true, 'start' => true],
            'li' => ['class' => true],
        ];
    }

    /**
     * Get allowed HTML tags for comments
     */
    public static function getAllowedHtmlComments(): array
    {
        return [
            'a' => [
                'href' => true,
                'rel' => true,
            ],
            'strong' => [],
            'em' => [],
            'br' => [],
            'p' => [],
        ];
    }

    /**
     * Strip all HTML tags and decode entities
     */
    public static function plainText(?string $value): string
    {
        if ($value === null || $value === '') {
            return '';
        }
        return wp_strip_all_tags(html_entity_decode($value, ENT_QUOTES, 'UTF-8'));
    }

    /**
     * Sanitize JSON string
     */
    public static function json(?string $value): string
    {
        if ($value === null || $value === '') {
            return '';
        }

        $decoded = json_decode($value, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return '';
        }

        $encoded = wp_json_encode($decoded);
        return $encoded !== false ? $encoded : '';
    }
}
