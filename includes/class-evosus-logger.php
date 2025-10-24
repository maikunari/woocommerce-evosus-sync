<?php
/**
 * Evosus Logger
 * Comprehensive logging system for API calls, errors, and sync operations
 */

class Evosus_Logger {

    private static $instance = null;
    private $table_name;

    /**
     * Get singleton instance
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor
     */
    private function __construct() {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'evosus_logs';

        // Validate table name to prevent SQL injection
        $this->validate_table_name();
    }

    /**
     * Validate table name matches expected pattern
     */
    private function validate_table_name() {
        global $wpdb;
        $expected_table = $wpdb->prefix . 'evosus_logs';

        if ($this->table_name !== $expected_table) {
            wp_die('Invalid table name for logger');
        }
    }

    /**
     * Log an API request/response
     */
    public function log_api_call($endpoint, $method, $request_data, $response_data, $status_code = null, $execution_time = 0) {
        global $wpdb;

        $wpdb->insert(
            $this->table_name,
            [
                'log_type' => 'api_call',
                'severity' => $status_code >= 400 ? 'error' : 'info',
                'endpoint' => $endpoint,
                'method' => $method,
                'request_data' => $this->sanitize_for_log($request_data),
                'response_data' => $this->sanitize_for_log($response_data),
                'status_code' => $status_code,
                'execution_time' => $execution_time,
                'created_at' => current_time('mysql')
            ],
            ['%s', '%s', '%s', '%s', '%s', '%s', '%d', '%f', '%s']
        );

        return $wpdb->insert_id;
    }

    /**
     * Log an error
     */
    public function log_error($message, $context = [], $order_id = null) {
        global $wpdb;

        $wpdb->insert(
            $this->table_name,
            [
                'log_type' => 'error',
                'severity' => 'error',
                'message' => $message,
                'context' => maybe_serialize($context),
                'order_id' => $order_id,
                'created_at' => current_time('mysql')
            ],
            ['%s', '%s', '%s', '%s', '%d', '%s']
        );

        // Also log to PHP error log for critical errors
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('Evosus Sync Error: ' . $message . ' | Context: ' . print_r($context, true));
        }

        return $wpdb->insert_id;
    }

    /**
     * Log a sync operation
     */
    public function log_sync($order_id, $status, $message, $evosus_order_id = null, $details = []) {
        global $wpdb;

        $wpdb->insert(
            $this->table_name,
            [
                'log_type' => 'sync',
                'severity' => $status === 'success' ? 'info' : 'error',
                'order_id' => $order_id,
                'evosus_order_id' => $evosus_order_id,
                'message' => $message,
                'context' => maybe_serialize($details),
                'created_at' => current_time('mysql')
            ],
            ['%s', '%s', '%d', '%s', '%s', '%s', '%s']
        );

        return $wpdb->insert_id;
    }

    /**
     * Log a warning
     */
    public function log_warning($message, $context = [], $order_id = null) {
        global $wpdb;

        $wpdb->insert(
            $this->table_name,
            [
                'log_type' => 'warning',
                'severity' => 'warning',
                'message' => $message,
                'context' => maybe_serialize($context),
                'order_id' => $order_id,
                'created_at' => current_time('mysql')
            ],
            ['%s', '%s', '%s', '%s', '%d', '%s']
        );

        return $wpdb->insert_id;
    }

    /**
     * Log an info message
     */
    public function log_info($message, $context = [], $order_id = null) {
        global $wpdb;

        $wpdb->insert(
            $this->table_name,
            [
                'log_type' => 'info',
                'severity' => 'info',
                'message' => $message,
                'context' => maybe_serialize($context),
                'order_id' => $order_id,
                'created_at' => current_time('mysql')
            ],
            ['%s', '%s', '%s', '%s', '%d', '%s']
        );

        return $wpdb->insert_id;
    }

    /**
     * Get logs with filters (SQL injection safe)
     */
    public function get_logs($args = []) {
        global $wpdb;

        $defaults = [
            'limit' => 100,
            'offset' => 0,
            'log_type' => null,
            'severity' => null,
            'order_id' => null,
            'start_date' => null,
            'end_date' => null
        ];

        $args = wp_parse_args($args, $defaults);

        // Sanitize limit and offset
        $limit = absint($args['limit']);
        $offset = absint($args['offset']);

        if ($limit > 1000) {
            $limit = 1000; // Max limit for safety
        }

        $where = ['1=1'];
        $prepare_values = [];

        if ($args['log_type']) {
            $where[] = 'log_type = %s';
            $prepare_values[] = $args['log_type'];
        }

        if ($args['severity']) {
            $where[] = 'severity = %s';
            $prepare_values[] = $args['severity'];
        }

        if ($args['order_id']) {
            $where[] = 'order_id = %d';
            $prepare_values[] = absint($args['order_id']);
        }

        if ($args['start_date']) {
            $where[] = 'created_at >= %s';
            $prepare_values[] = $args['start_date'];
        }

        if ($args['end_date']) {
            $where[] = 'created_at <= %s';
            $prepare_values[] = $args['end_date'];
        }

        $where_clause = implode(' AND ', $where);

        // Add limit and offset to prepared values
        $prepare_values[] = $limit;
        $prepare_values[] = $offset;

        // Build and prepare query - table name is validated in constructor
        $query = $wpdb->prepare(
            "SELECT * FROM {$this->table_name} WHERE {$where_clause} ORDER BY created_at DESC LIMIT %d OFFSET %d",
            $prepare_values
        );

        return $wpdb->get_results($query);
    }

    /**
     * Clean old logs (older than X days)
     */
    public function cleanup_old_logs($days = 90) {
        global $wpdb;

        $date = date('Y-m-d H:i:s', strtotime("-{$days} days"));

        return $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM {$this->table_name} WHERE created_at < %s",
                $date
            )
        );
    }

    /**
     * Sanitize data for logging (remove sensitive info)
     */
    private function sanitize_for_log($data) {
        // Handle arrays and objects
        if (is_array($data) || is_object($data)) {
            $data = $this->redact_sensitive_array($data);
            $data = json_encode($data, JSON_PRETTY_PRINT);
        }

        // Additional pattern-based redaction for string data
        $sensitive_patterns = [
            '/("ticket"|"password"|"api_key"|"token"|"secret"|"webhook_secret"):\s*"[^"]*"/i',
            '/("CompanySN"|"company_sn"):\s*"[^"]*"/i',
            '/("authorization"|"auth"):\s*"[^"]*"/i',
            '/(ticket=)[^&\s]*/i',
            '/(CompanySN=)[^&\s]*/i',
        ];

        foreach ($sensitive_patterns as $pattern) {
            $data = preg_replace($pattern, '$1"[REDACTED]"', $data);
        }

        return $data;
    }

    /**
     * Recursively redact sensitive keys from arrays
     */
    private function redact_sensitive_array($data) {
        $sensitive_keys = [
            'ticket', 'password', 'api_key', 'token', 'secret',
            'webhook_secret', 'CompanySN', 'company_sn',
            'authorization', 'auth', 'api_token'
        ];

        if (is_array($data)) {
            foreach ($data as $key => $value) {
                if (in_array(strtolower($key), array_map('strtolower', $sensitive_keys))) {
                    $data[$key] = '[REDACTED]';
                } elseif (is_array($value) || is_object($value)) {
                    $data[$key] = $this->redact_sensitive_array($value);
                }
            }
        } elseif (is_object($data)) {
            foreach ($data as $key => $value) {
                if (in_array(strtolower($key), array_map('strtolower', $sensitive_keys))) {
                    $data->$key = '[REDACTED]';
                } elseif (is_array($value) || is_object($value)) {
                    $data->$key = $this->redact_sensitive_array($value);
                }
            }
        }

        return $data;
    }

    /**
     * Get log statistics
     */
    public function get_stats($days = 30) {
        global $wpdb;

        $date = date('Y-m-d H:i:s', strtotime("-{$days} days"));

        $stats = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT
                    log_type,
                    severity,
                    COUNT(*) as count
                FROM {$this->table_name}
                WHERE created_at >= %s
                GROUP BY log_type, severity",
                $date
            )
        );

        return $stats;
    }
}
