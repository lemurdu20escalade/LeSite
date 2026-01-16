<?php

/**
 * Secure Database Operations
 *
 * @package Lemur\Security
 */

declare(strict_types=1);

namespace Lemur\Security;

/**
 * Secure helpers for database queries
 */
class Database
{
    /**
     * WordPress database instance
     */
    private \wpdb $wpdb;

    /**
     * Constructor
     */
    public function __construct()
    {
        global $wpdb;
        $this->wpdb = $wpdb;
    }

    /**
     * Get a singleton instance
     */
    public static function getInstance(): self
    {
        static $instance = null;
        if ($instance === null) {
            $instance = new self();
        }
        return $instance;
    }

    /**
     * Execute a prepared SELECT query
     *
     * @param string $query Query with placeholders (%s, %d, %f)
     * @param array $args Arguments to insert
     * @return array Results as associative arrays
     */
    public function select(string $query, array $args = []): array
    {
        $prepared = empty($args)
            ? $query
            : $this->wpdb->prepare($query, ...$args);

        $results = $this->wpdb->get_results($prepared, ARRAY_A);
        return $results ?: [];
    }

    /**
     * Execute SELECT and return single row
     */
    public function selectRow(string $query, array $args = []): ?array
    {
        $prepared = empty($args)
            ? $query
            : $this->wpdb->prepare($query, ...$args);

        $result = $this->wpdb->get_row($prepared, ARRAY_A);
        return $result ?: null;
    }

    /**
     * Execute SELECT and return single value
     *
     * @return mixed
     */
    public function selectVar(string $query, array $args = [])
    {
        $prepared = empty($args)
            ? $query
            : $this->wpdb->prepare($query, ...$args);

        return $this->wpdb->get_var($prepared);
    }

    /**
     * Execute SELECT and return column
     */
    public function selectCol(string $query, array $args = [], int $column = 0): array
    {
        $prepared = empty($args)
            ? $query
            : $this->wpdb->prepare($query, ...$args);

        $results = $this->wpdb->get_col($prepared, $column);
        return $results ?: [];
    }

    /**
     * Secure INSERT
     *
     * @param string $table Table name (without prefix)
     * @param array $data Data to insert
     * @param array $format Formats (%s, %d, %f). Auto-detected if empty.
     * @return int|false Insert ID or false on error
     */
    public function insert(string $table, array $data, array $format = []): int|false
    {
        $sanitized = $this->sanitizeData($data);

        if (empty($format)) {
            $format = $this->detectFormats($sanitized);
        }

        $result = $this->wpdb->insert(
            $this->wpdb->prefix . $table,
            $sanitized,
            $format
        );

        if ($result === false) {
            $this->logError('INSERT', $table);
            return false;
        }

        return $this->wpdb->insert_id;
    }

    /**
     * Secure UPDATE
     *
     * @param string $table Table name (without prefix)
     * @param array $data Data to update
     * @param array $where Where conditions
     * @param array $format Data formats
     * @param array $where_format Where formats
     * @return int|false Number of rows updated or false
     */
    public function update(string $table, array $data, array $where, array $format = [], array $where_format = []): int|false
    {
        $sanitized = $this->sanitizeData($data);
        $where_sanitized = $this->sanitizeData($where);

        if (empty($format)) {
            $format = $this->detectFormats($sanitized);
        }

        if (empty($where_format)) {
            $where_format = $this->detectFormats($where_sanitized);
        }

        $result = $this->wpdb->update(
            $this->wpdb->prefix . $table,
            $sanitized,
            $where_sanitized,
            $format,
            $where_format
        );

        if ($result === false) {
            $this->logError('UPDATE', $table);
            return false;
        }

        return $result;
    }

    /**
     * Secure DELETE
     *
     * @param string $table Table name (without prefix)
     * @param array $where Where conditions
     * @param array $where_format Where formats
     * @return int|false Number of rows deleted or false
     */
    public function delete(string $table, array $where, array $where_format = []): int|false
    {
        $where_sanitized = $this->sanitizeData($where);

        if (empty($where_format)) {
            $where_format = $this->detectFormats($where_sanitized);
        }

        $result = $this->wpdb->delete(
            $this->wpdb->prefix . $table,
            $where_sanitized,
            $where_format
        );

        if ($result === false) {
            $this->logError('DELETE', $table);
            return false;
        }

        return $result;
    }

    /**
     * Execute a prepared query (INSERT, UPDATE, DELETE)
     *
     * @return int|false Number of affected rows or false
     */
    public function query(string $query, array $args = []): int|false
    {
        $prepared = empty($args)
            ? $query
            : $this->wpdb->prepare($query, ...$args);

        return $this->wpdb->query($prepared);
    }

    /**
     * Escape a value for LIKE queries
     */
    public function escapeLike(string $value): string
    {
        return $this->wpdb->esc_like($value);
    }

    /**
     * Prepare a list of IDs for IN() clause
     */
    public function prepareIn(array $ids): string
    {
        $ids = array_map('intval', $ids);
        $ids = array_filter($ids, function ($id) {
            return $id > 0;
        });

        if (empty($ids)) {
            return '0';
        }

        return implode(',', $ids);
    }

    /**
     * Prepare a list of strings for IN() clause
     */
    public function prepareInStrings(array $strings): string
    {
        $sanitized = array_map(function ($str) {
            return "'" . esc_sql(sanitize_text_field($str)) . "'";
        }, $strings);

        if (empty($sanitized)) {
            return "''";
        }

        return implode(',', $sanitized);
    }

    /**
     * Get table name with prefix
     */
    public function getTableName(string $table): string
    {
        return $this->wpdb->prefix . $table;
    }

    /**
     * Check if a table exists
     */
    public function tableExists(string $table): bool
    {
        $full_table = $this->wpdb->prefix . $table;
        $result = $this->wpdb->get_var(
            $this->wpdb->prepare(
                "SHOW TABLES LIKE %s",
                $full_table
            )
        );

        return $result === $full_table;
    }

    /**
     * Get last insert ID
     */
    public function getLastInsertId(): int
    {
        return $this->wpdb->insert_id;
    }

    /**
     * Get last error message
     */
    public function getLastError(): string
    {
        return $this->wpdb->last_error;
    }

    /**
     * Get last query
     */
    public function getLastQuery(): string
    {
        return $this->wpdb->last_query;
    }

    /**
     * Get wpdb instance for complex operations
     */
    public function getWpdb(): \wpdb
    {
        return $this->wpdb;
    }

    /**
     * Sanitize data before insertion
     */
    private function sanitizeData(array $data): array
    {
        $sanitized = [];

        foreach ($data as $key => $value) {
            // Key: only alphanumeric and underscores
            $key = (string) preg_replace('/[^a-zA-Z0-9_]/', '', $key);

            if ($key === '') {
                continue;
            }

            if ($value === null) {
                $sanitized[$key] = null;
            } elseif (is_int($value)) {
                $sanitized[$key] = intval($value);
            } elseif (is_float($value)) {
                $sanitized[$key] = floatval($value);
            } elseif (is_bool($value)) {
                $sanitized[$key] = $value ? 1 : 0;
            } elseif (is_array($value)) {
                // Serialize arrays
                $sanitized[$key] = maybe_serialize($value);
            } else {
                $sanitized[$key] = sanitize_text_field((string) $value);
            }
        }

        return $sanitized;
    }

    /**
     * Auto-detect formats from data types
     */
    private function detectFormats(array $data): array
    {
        $formats = [];

        foreach ($data as $value) {
            if ($value === null) {
                $formats[] = '%s';
            } elseif (is_int($value)) {
                $formats[] = '%d';
            } elseif (is_float($value)) {
                $formats[] = '%f';
            } else {
                $formats[] = '%s';
            }
        }

        return $formats;
    }

    /**
     * Log database errors
     */
    private function logError(string $operation, string $table): void
    {
        if (!defined('WP_DEBUG') || !WP_DEBUG) {
            return;
        }

        error_log(sprintf(
            '[Lemur DB Error] %s on %s: %s',
            $operation,
            $table,
            $this->wpdb->last_error
        ));
    }

    /**
     * Create a custom table with dbDelta
     */
    public static function createTable(string $table_name, string $sql): void
    {
        global $wpdb;

        $charset_collate = $wpdb->get_charset_collate();
        $full_table = $wpdb->prefix . $table_name;

        // Replace {table} placeholder with full table name
        $sql = str_replace('{table}', $full_table, $sql);

        // Add charset
        if (strpos($sql, $charset_collate) === false) {
            $sql .= " {$charset_collate}";
        }

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql);
    }
}
