<?php

/**
 * Console Log Debug Helper
 *
 * PHP helper to output debug information to browser console.
 * Only works when WP_DEBUG is enabled.
 *
 * Usage:
 *   lc($variable);                    // Simple log
 *   lc('Label', $data);               // Log with label
 *   lc($var1, $var2, $var3);         // Multiple arguments
 *   lc()->warn('Warning!');           // Warning level
 *   lc()->error('Error!', $data);     // Error level
 *   lc()->info('Info');               // Info level
 *   lc()->table($array);              // Console table
 *   lc()->group('Title', $data);      // Grouped output
 *
 * @package Lemur\Core
 */

declare(strict_types=1);

namespace Lemur\Core;

/**
 * Console log helper class
 */
class ConsoleLog
{
    /**
     * Singleton instance
     *
     * @var self|null
     */
    private static ?self $instance = null;

    /**
     * Current console method
     */
    private string $method = 'log';

    /**
     * Output buffer for deferred rendering
     *
     * @var array<int, string>
     */
    private static array $buffer = [];

    /**
     * Whether to buffer outputs
     */
    private static bool $buffering = true;

    /**
     * Initialize the module
     */
    public static function init(): void
    {
        // Only enable in debug mode
        if (!defined('WP_DEBUG') || !WP_DEBUG) {
            return;
        }

        // Output buffered logs in footer
        add_action('wp_footer', [self::class, 'flushBuffer'], 999);
        add_action('admin_footer', [self::class, 'flushBuffer'], 999);
    }

    /**
     * Get singleton instance
     *
     * @return self
     */
    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * Output script tag to console
     *
     * @param mixed ...$args Arguments to log
     * @return self For chaining
     */
    private function output(mixed ...$args): self
    {
        if (!defined('WP_DEBUG') || !WP_DEBUG) {
            return $this;
        }

        $method = $this->method;
        $this->method = 'log'; // Reset for next call

        $jsArgs = array_map([$this, 'toJs'], $args);

        $script = sprintf(
            '<script>console.%s(%s);</script>',
            esc_js($method),
            implode(', ', $jsArgs)
        );

        if (self::$buffering) {
            self::$buffer[] = $script;
        } else {
            // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
            echo $script . "\n";
        }

        return $this;
    }

    /**
     * Flush buffer to output
     */
    public static function flushBuffer(): void
    {
        if (empty(self::$buffer)) {
            return;
        }

        foreach (self::$buffer as $script) {
            // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
            echo $script . "\n";
        }

        self::$buffer = [];
    }

    /**
     * Convert PHP value to JavaScript
     *
     * @param mixed $value Value to convert
     * @return string JavaScript representation
     */
    private function toJs(mixed $value): string
    {
        $type = gettype($value);

        switch ($type) {
            case 'boolean':
                return $value ? 'true' : 'false';

            case 'integer':
            case 'double':
                return (string) $value;

            case 'NULL':
                return 'null';

            case 'string':
                if ($this->isJson($value)) {
                    return $value;
                }
                return (string) wp_json_encode($value, JSON_UNESCAPED_UNICODE);

            case 'array':
            case 'object':
                return (string) wp_json_encode($value, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

            case 'resource':
            case 'resource (closed)':
                return (string) wp_json_encode('Resource: ' . get_resource_type($value));

            default:
                return (string) wp_json_encode($value, JSON_UNESCAPED_UNICODE);
        }
    }

    /**
     * Check if string is valid JSON
     *
     * @param mixed $string Value to check
     * @return bool
     */
    private function isJson(mixed $string): bool
    {
        if (!is_string($string)) {
            return false;
        }

        json_decode($string);
        return json_last_error() === JSON_ERROR_NONE;
    }

    /**
     * console.log()
     *
     * @param mixed ...$args Arguments to log
     * @return self
     */
    public function log(mixed ...$args): self
    {
        $this->method = 'log';
        return $this->output(...$args);
    }

    /**
     * console.warn()
     *
     * @param mixed ...$args Arguments to log
     * @return self
     */
    public function warn(mixed ...$args): self
    {
        $this->method = 'warn';
        return $this->output(...$args);
    }

    /**
     * console.error()
     *
     * @param mixed ...$args Arguments to log
     * @return self
     */
    public function error(mixed ...$args): self
    {
        $this->method = 'error';
        return $this->output(...$args);
    }

    /**
     * console.info()
     *
     * @param mixed ...$args Arguments to log
     * @return self
     */
    public function info(mixed ...$args): self
    {
        $this->method = 'info';
        return $this->output(...$args);
    }

    /**
     * console.table()
     *
     * @param mixed      $data    Data to display as table
     * @param array|null $columns Optional columns to show
     * @return self
     */
    public function table(mixed $data, ?array $columns = null): self
    {
        if (!defined('WP_DEBUG') || !WP_DEBUG) {
            return $this;
        }

        $jsData = $this->toJs($data);
        $script = $columns
            ? sprintf('<script>console.table(%s, %s);</script>', $jsData, $this->toJs($columns))
            : sprintf('<script>console.table(%s);</script>', $jsData);

        if (self::$buffering) {
            self::$buffer[] = $script;
        } else {
            // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
            echo $script . "\n";
        }

        return $this;
    }

    /**
     * console.trace()
     *
     * @param string|null $label Optional label
     * @return self
     */
    public function trace(?string $label = null): self
    {
        if (!defined('WP_DEBUG') || !WP_DEBUG) {
            return $this;
        }

        $script = $label
            ? sprintf('<script>console.trace(%s);</script>', wp_json_encode($label))
            : '<script>console.trace();</script>';

        if (self::$buffering) {
            self::$buffer[] = $script;
        } else {
            // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
            echo $script . "\n";
        }

        return $this;
    }

    /**
     * console.group()
     *
     * @param string $label Group label
     * @param mixed  ...$args Additional arguments to log in group
     * @return self
     */
    public function group(string $label, mixed ...$args): self
    {
        if (!defined('WP_DEBUG') || !WP_DEBUG) {
            return $this;
        }

        $script = sprintf('<script>console.group(%s);</script>', wp_json_encode($label));

        if (self::$buffering) {
            self::$buffer[] = $script;
        } else {
            // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
            echo $script . "\n";
        }

        if (!empty($args)) {
            $this->output(...$args);
        }

        return $this;
    }

    /**
     * console.groupEnd()
     *
     * @return self
     */
    public function groupEnd(): self
    {
        if (!defined('WP_DEBUG') || !WP_DEBUG) {
            return $this;
        }

        $script = '<script>console.groupEnd();</script>';

        if (self::$buffering) {
            self::$buffer[] = $script;
        } else {
            // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
            echo $script . "\n";
        }

        return $this;
    }

    /**
     * console.clear()
     *
     * @return self
     */
    public function clear(): self
    {
        if (!defined('WP_DEBUG') || !WP_DEBUG) {
            return $this;
        }

        $script = '<script>console.clear();</script>';

        if (self::$buffering) {
            self::$buffer[] = $script;
        } else {
            // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
            echo $script . "\n";
        }

        return $this;
    }

    /**
     * Log with timestamp
     *
     * @param mixed ...$args Arguments to log
     * @return self
     */
    public function time(mixed ...$args): self
    {
        array_unshift($args, '[' . current_time('H:i:s') . ']');
        return $this->output(...$args);
    }

    /**
     * Debug WordPress query
     *
     * @return self
     */
    public function query(): self
    {
        global $wp_query;
        return $this->group('WP_Query', $wp_query)->groupEnd();
    }

    /**
     * Debug global $post
     *
     * @return self
     */
    public function post(): self
    {
        global $post;
        return $this->group('Post', $post)->groupEnd();
    }
}
